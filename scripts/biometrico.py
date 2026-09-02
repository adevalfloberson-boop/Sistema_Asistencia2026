"""Monitor ZKTeco readers, relay punches, and execute SDK commands from Laravel."""

from __future__ import annotations

import ipaddress
import hashlib
import json
import logging
import os
import re
import subprocess
import threading
import time
from concurrent.futures import ThreadPoolExecutor
from dataclasses import dataclass
from datetime import datetime, timedelta
from logging.handlers import RotatingFileHandler
from pathlib import Path
from typing import Any

import requests
from dotenv import load_dotenv
from zk import ZK


PROJECT_ROOT = Path(__file__).resolve().parent.parent
load_dotenv(PROJECT_ROOT / ".env")

ATTENDANCE_URL = os.getenv(
    "BIOMETRIC_ATTENDANCE_URL",
    "http://127.0.0.1:8000/api/asistencia",
)
DEFAULT_APP_URL = ATTENDANCE_URL.removesuffix("/api/asistencia")
AGENT_URL = os.getenv("BIOMETRIC_AGENT_URL", f"{DEFAULT_APP_URL}/api/biometric").rstrip("/")
API_TOKEN = os.getenv("BIOMETRIC_API_TOKEN", "")
RECONNECT_DELAY = max(1, int(os.getenv("BIOMETRIC_RECONNECT_DELAY", "10")))
ARP_CACHE_SECONDS = max(1, int(os.getenv("BIOMETRIC_ARP_CACHE_SECONDS", "15")))
DEVICE_SYNC_SECONDS = max(5, int(os.getenv("BIOMETRIC_DEVICE_SYNC_SECONDS", "15")))
HEARTBEAT_SECONDS = max(10, int(os.getenv("BIOMETRIC_HEARTBEAT_SECONDS", "30")))
HISTORY_SYNC_SECONDS = max(30, int(os.getenv("BIOMETRIC_HISTORY_SYNC_SECONDS", "300")))
STOP_EVENT = threading.Event()
ARP_SCAN_LOCK = threading.Lock()
ARP_SCAN_TIMES: dict[str, float] = {}


def configure_logging() -> logging.Logger:
    logger = logging.getLogger("biometric")
    logger.setLevel(logging.INFO)

    if logger.handlers:
        return logger

    formatter = logging.Formatter(
        "%(asctime)s | %(levelname)s | %(threadName)s | %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )
    console_handler = logging.StreamHandler()
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)

    configured_path = os.getenv("BIOMETRIC_LOG_FILE", "storage/logs/biometric.log")
    log_path = Path(configured_path)
    if not log_path.is_absolute():
        log_path = PROJECT_ROOT / log_path
    log_path.parent.mkdir(parents=True, exist_ok=True)

    file_handler = RotatingFileHandler(
        log_path,
        maxBytes=5 * 1024 * 1024,
        backupCount=5,
        encoding="utf-8",
    )
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    return logger


LOGGER = configure_logging()


@dataclass(frozen=True)
class ReaderConfig:
    key: str
    name: str
    school: str | None
    mac: str
    network: str
    preferred_ip: str | None
    port: int
    password: int
    timeout: int

    @property
    def label(self) -> str:
        school = f" / {self.school}" if self.school else ""
        return f"{self.name}{school} [{self.key}]"


@dataclass
class ReaderWorker:
    config: ReaderConfig
    stop_event: threading.Event
    thread: threading.Thread


def agent_headers() -> dict[str, str]:
    return {
        "Accept": "application/json",
        "X-Biometric-Token": API_TOKEN,
    }


def normalize_mac(value: str) -> str:
    characters = re.sub(r"[^0-9a-fA-F]", "", value).lower()
    if len(characters) != 12:
        raise ValueError(f"MAC inválida: {value}")

    return ":".join(characters[index : index + 2] for index in range(0, 12, 2))


def reader_from_mapping(data: dict[str, Any], position: int) -> ReaderConfig:
    key = str(data.get("key") or f"lector-{position}").strip()
    name = str(data.get("name") or key).strip()
    school_value = str(data.get("school") or "").strip()
    mac = normalize_mac(str(data.get("mac") or ""))
    network = str(data.get("network") or "192.168.100").strip().rstrip(".")
    preferred_ip_value = str(data.get("ip") or "").strip()
    preferred_ip = preferred_ip_value or None

    if not re.fullmatch(r"(?:\d{1,3}\.){2}\d{1,3}", network):
        raise ValueError(f"Red inválida para {key}: {network}")

    ipaddress.ip_network(f"{network}.0/24")
    if preferred_ip is not None:
        ipaddress.ip_address(preferred_ip)

    return ReaderConfig(
        key=key,
        name=name,
        school=school_value or None,
        mac=mac,
        network=network,
        preferred_ip=preferred_ip,
        port=int(data.get("port") or 4370),
        password=int(data.get("password") or 0),
        timeout=max(1, int(data.get("timeout") or 5)),
    )


def validate_reader_configs(readers: list[ReaderConfig]) -> list[ReaderConfig]:
    keys = [reader.key for reader in readers]
    macs = [reader.mac for reader in readers]
    if len(keys) != len(set(keys)):
        raise ValueError("Cada lector debe tener un key único.")
    if len(macs) != len(set(macs)):
        raise ValueError("Cada lector debe tener una MAC única.")

    return readers


def load_environment_reader_configs() -> list[ReaderConfig]:
    configured_readers = os.getenv("BIOMETRIC_READERS", "").strip()
    if configured_readers:
        decoded = json.loads(configured_readers)
        if not isinstance(decoded, list) or not decoded:
            raise ValueError("BIOMETRIC_READERS debe ser un arreglo JSON no vacío.")

        readers = [
            reader_from_mapping(reader, index)
            for index, reader in enumerate(decoded, start=1)
            if isinstance(reader, dict)
        ]
        if len(readers) != len(decoded):
            raise ValueError("Cada elemento de BIOMETRIC_READERS debe ser un objeto JSON.")
    else:
        readers = [
            reader_from_mapping(
                {
                    "key": os.getenv("BIOMETRIC_READER_KEY", "lector-1"),
                    "name": os.getenv("BIOMETRIC_READER_NAME", "Lector principal"),
                    "school": os.getenv("BIOMETRIC_READER_SCHOOL", ""),
                    "mac": os.getenv("BIOMETRIC_READER_MAC", "00:17:61:11:18:e3"),
                    "network": os.getenv("BIOMETRIC_READER_NETWORK", "192.168.100"),
                    "ip": os.getenv("BIOMETRIC_READER_IP", ""),
                    "port": os.getenv("BIOMETRIC_READER_PORT", "4370"),
                    "password": os.getenv("BIOMETRIC_READER_PASSWORD", "0"),
                    "timeout": os.getenv("BIOMETRIC_READER_TIMEOUT", "5"),
                },
                1,
            )
        ]

    return validate_reader_configs(readers)


def fetch_registered_readers() -> list[ReaderConfig] | None:
    try:
        response = requests.get(
            f"{AGENT_URL}/devices",
            headers=agent_headers(),
            timeout=5,
        )
        response.raise_for_status()
        decoded = response.json().get("devices", [])
        if not isinstance(decoded, list):
            raise ValueError("La respuesta de dispositivos no contiene una lista.")

        return validate_reader_configs(
            [
                reader_from_mapping(reader, index)
                for index, reader in enumerate(decoded, start=1)
                if isinstance(reader, dict)
            ]
        )
    except (requests.RequestException, ValueError, json.JSONDecodeError) as error:
        LOGGER.debug("No se pudo sincronizar la lista de lectores con Laravel: %s", error)
        return None


def ping(ip_address: str) -> None:
    subprocess.run(
        ["ping", "-n", "1", "-w", "250", ip_address],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        check=False,
    )


def populate_arp_table(network: str) -> None:
    with ARP_SCAN_LOCK:
        current_time = time.monotonic()
        last_scan = ARP_SCAN_TIMES.get(network, 0)
        if current_time - last_scan < ARP_CACHE_SECONDS:
            return

        LOGGER.info("Escaneando %s.1-254 para actualizar la tabla ARP...", network)
        with ThreadPoolExecutor(max_workers=64) as executor:
            list(executor.map(ping, (f"{network}.{host}" for host in range(1, 255))))

        ARP_SCAN_TIMES[network] = time.monotonic()


def find_reader_ips(reader: ReaderConfig) -> list[str]:
    populate_arp_table(reader.network)

    try:
        arp_output = subprocess.check_output(
            ["arp", "-a"],
            text=True,
            encoding="utf-8",
            errors="ignore",
        )
    except (OSError, subprocess.CalledProcessError) as error:
        LOGGER.warning("%s: no se pudo leer la tabla ARP: %s", reader.label, error)
        return []

    target_mac = reader.mac.replace(":", "-")
    matches: set[str] = set()
    for match in re.finditer(
        r"(?P<ip>\d{1,3}(?:\.\d{1,3}){3})\s+(?P<mac>[0-9a-fA-F:-]{17})",
        arp_output,
    ):
        if match.group("mac").lower().replace(":", "-") != target_mac:
            continue

        ip_address = match.group("ip")
        if ip_address.startswith(f"{reader.network}."):
            matches.add(ip_address)

    return sorted(matches, key=lambda address: int(ipaddress.ip_address(address)))


def resolve_reader_ips(reader: ReaderConfig) -> list[str]:
    candidates: list[str] = []
    if reader.preferred_ip:
        candidates.append(reader.preferred_ip)

    for discovered_ip in find_reader_ips(reader):
        if discovered_ip not in candidates:
            candidates.append(discovered_ip)

    if candidates:
        LOGGER.info(
            "%s: IP candidatas para MAC %s: %s",
            reader.label,
            reader.mac,
            ", ".join(candidates),
        )
    else:
        LOGGER.warning("%s: no se encontró la MAC %s en la red.", reader.label, reader.mac)

    return candidates


def attendance_number(attendance: Any, attribute: str) -> int:
    try:
        return int(getattr(attendance, attribute, 0) or 0)
    except (TypeError, ValueError):
        return 0


def attendance_event_key(reader: ReaderConfig, attendance: Any) -> str:
    event_timestamp = attendance.timestamp.strftime("%Y-%m-%d %H:%M:%S")
    identity = "|".join(
        [
            reader.key,
            str(attendance.user_id),
            event_timestamp,
            str(attendance_number(attendance, "status")),
            str(attendance_number(attendance, "punch")),
        ]
    )

    return hashlib.sha256(identity.encode("utf-8")).hexdigest()


def send_attendance(
    reader: ReaderConfig,
    reader_ip: str,
    attendance: Any,
    source: str = "live",
) -> bool:
    user_id = attendance.user_id
    payload = {
        "id_lector": str(user_id),
        "reader_key": reader.key,
        "reader_name": reader.name,
        "reader_school": reader.school,
        "reader_mac": reader.mac,
        "reader_ip": reader_ip,
        "event_key": attendance_event_key(reader, attendance),
        "event_timestamp": attendance.timestamp.strftime("%Y-%m-%d %H:%M:%S"),
        "event_source": source,
        "device_status": attendance_number(attendance, "status"),
        "device_punch": attendance_number(attendance, "punch"),
    }

    try:
        response = requests.post(
            ATTENDANCE_URL,
            json=payload,
            headers=agent_headers(),
            timeout=10,
        )
        data = response.json()
    except (requests.RequestException, ValueError) as error:
        LOGGER.error("%s: error enviando ID %s a Laravel: %s", reader.label, user_id, error)
        return False

    if response.ok and data.get("success"):
        action = (
            "ignorado por intervalo"
            if data.get("ignored")
            else ("ya sincronizado" if data.get("duplicate") else "registrado")
        )
        LOGGER.info(
            "%s: ID %s %s (%s, %s, origen=%s).",
            reader.label,
            user_id,
            action,
            data.get("student", "estudiante"),
            data.get("tipo", "registro"),
            source,
        )
        return True

    LOGGER.warning(
        "%s: Laravel rechazó ID %s con HTTP %s: %s",
        reader.label,
        user_id,
        response.status_code,
        data.get("message", data),
    )
    return False


def fetch_attendance_checkpoint(reader: ReaderConfig) -> tuple[bool, datetime | None]:
    try:
        response = requests.get(
            f"{AGENT_URL}/devices/{reader.key}/attendance/checkpoint",
            headers=agent_headers(),
            timeout=5,
        )
        response.raise_for_status()
        last_event_at = response.json().get("last_event_at")
        checkpoint = datetime.fromisoformat(last_event_at) if last_event_at else None
        return True, checkpoint
    except (requests.RequestException, ValueError, TypeError, json.JSONDecodeError) as error:
        LOGGER.warning("%s: no se pudo consultar el último ponche del sistema: %s", reader.label, error)
        return False, None


def synchronize_attendance_history(reader: ReaderConfig, connection: Any, reader_ip: str) -> bool:
    checkpoint_available, checkpoint = fetch_attendance_checkpoint(reader)
    if not checkpoint_available:
        return False

    try:
        attendances = connection.get_attendance() or []
    except Exception as error:
        LOGGER.warning("%s: no se pudo leer el historial del lector: %s", reader.label, error)
        return False

    valid_attendances = [
        attendance
        for attendance in attendances
        if getattr(attendance, "timestamp", None) is not None
        and getattr(attendance, "user_id", None) is not None
    ]
    valid_attendances.sort(key=lambda attendance: attendance.timestamp)
    overlap_start = checkpoint - timedelta(minutes=5) if checkpoint is not None else None
    pending_attendances = [
        attendance
        for attendance in valid_attendances
        if overlap_start is None or attendance.timestamp >= overlap_start
    ]

    if not pending_attendances:
        LOGGER.info("%s: historial revisado; no hay ponches pendientes.", reader.label)
        return True

    synchronized = 0
    failed = 0
    for attendance in pending_attendances:
        if send_attendance(reader, reader_ip, attendance, source="history"):
            synchronized += 1
        else:
            failed += 1

    LOGGER.info(
        "%s: historial revisado; %s eventos aceptados, %s pendientes por error.",
        reader.label,
        synchronized,
        failed,
    )

    return failed == 0


def stringify(value: Any) -> str | None:
    if value is None:
        return None
    if isinstance(value, bytes):
        return value.decode(errors="ignore").strip("\x00")
    return str(value)


def device_information(connection: Any) -> dict[str, Any]:
    def safely(method_name: str) -> Any:
        try:
            return getattr(connection, method_name)()
        except Exception as error:
            LOGGER.debug("No se pudo consultar %s: %s", method_name, error)
            return None

    users = safely("get_users")
    templates = safely("get_templates")

    return {
        "serial_number": stringify(safely("get_serialnumber")),
        "model": stringify(safely("get_device_name")),
        "firmware_version": stringify(safely("get_firmware_version")),
        "platform": stringify(safely("get_platform")),
        "device_time": stringify(safely("get_time")),
        "user_count": len(users) if isinstance(users, list) else 0,
        "fingerprint_count": len(templates) if isinstance(templates, list) else 0,
    }


def send_heartbeat(
    reader: ReaderConfig,
    status: str,
    reader_ip: str | None,
    information: dict[str, Any] | None = None,
    error: str | None = None,
) -> None:
    payload = {
        "status": status,
        "ip_address": reader_ip,
        **(information or {}),
    }
    if error:
        payload["error"] = error[:2000]

    try:
        requests.post(
            f"{AGENT_URL}/devices/{reader.key}/heartbeat",
            json=payload,
            headers=agent_headers(),
            timeout=5,
        ).raise_for_status()
    except requests.RequestException as request_error:
        LOGGER.debug("%s: no se pudo enviar heartbeat: %s", reader.label, request_error)


def fetch_next_command(reader: ReaderConfig) -> dict[str, Any] | None:
    try:
        response = requests.get(
            f"{AGENT_URL}/devices/{reader.key}/commands/next",
            headers=agent_headers(),
            timeout=5,
        )
        if response.status_code == 204:
            return None
        response.raise_for_status()
        command = response.json()
        return command if isinstance(command, dict) else None
    except (requests.RequestException, ValueError) as error:
        LOGGER.debug("%s: no se pudo consultar órdenes: %s", reader.label, error)
        return None


def complete_command(
    reader: ReaderConfig,
    command_id: int,
    success: bool,
    result: dict[str, Any] | None = None,
    error: str | None = None,
) -> None:
    try:
        requests.post(
            f"{AGENT_URL}/commands/{command_id}/complete",
            json={
                "success": success,
                "result": result or {},
                "error": error,
            },
            headers=agent_headers(),
            timeout=10,
        ).raise_for_status()
    except requests.RequestException as request_error:
        LOGGER.error("%s: no se pudo confirmar la orden %s: %s", reader.label, command_id, request_error)


def process_command(reader: ReaderConfig, connection: Any, command: dict[str, Any]) -> None:
    command_id = int(command["id"])
    command_type = str(command.get("type") or "")
    payload = command.get("payload") or {}
    LOGGER.info("%s: ejecutando orden SDK %s (#%s).", reader.label, command_type, command_id)

    try:
        connection.cancel_capture()
        if command_type == "inspect":
            result = device_information(connection)
        elif command_type == "sync_time":
            connection.set_time(datetime.now())
            result = {"device_time": datetime.now().isoformat(timespec="seconds")}
        elif command_type == "enroll":
            uid = int(payload["uid"])
            user_id = str(payload["user_id"])
            finger_index = int(payload["finger_index"])
            name = str(payload.get("name") or user_id)[:24]
            connection.set_user(uid=uid, name=name, user_id=user_id)
            enrollment_completed = False
            try:
                enrollment_completed = bool(
                    connection.enroll_user(uid=uid, temp_id=finger_index, user_id=user_id)
                )
            except Exception as enrollment_error:
                if "cant' reg events 0" not in str(enrollment_error).lower():
                    raise

                LOGGER.warning(
                    "%s: el firmware rechazó cerrar el modo de eventos; verificando la plantilla guardada.",
                    reader.label,
                )
                enrollment_completed = bool(
                    connection.get_user_template(
                        uid=uid,
                        temp_id=finger_index,
                        user_id=user_id,
                    )
                )

            if not enrollment_completed:
                raise RuntimeError("El lector no completó las tres capturas de la huella.")
            result = {"user_id": user_id, "finger_index": finger_index}
        elif command_type == "verify_enrollment":
            uid = int(payload["uid"])
            user_id = str(payload["user_id"])
            finger_index = int(payload["finger_index"])
            template = connection.get_user_template(
                uid=uid,
                temp_id=finger_index,
                user_id=user_id,
            )
            if not template:
                raise RuntimeError("No se encontró esa plantilla de huella en el lector.")
            result = {"user_id": user_id, "finger_index": finger_index, "verified": True}
        else:
            raise ValueError(f"Orden SDK no soportada: {command_type}")

        complete_command(reader, command_id, True, result=result)
        LOGGER.info("%s: orden SDK %s completada.", reader.label, command_type)
    except Exception as error:
        complete_command(reader, command_id, False, error=str(error))
        LOGGER.error("%s: falló la orden SDK %s: %s", reader.label, command_type, error)


def should_stop(local_stop_event: threading.Event) -> bool:
    return STOP_EVENT.is_set() or local_stop_event.is_set()


def wait_for_retry(local_stop_event: threading.Event) -> None:
    deadline = time.monotonic() + RECONNECT_DELAY
    while not should_stop(local_stop_event) and time.monotonic() < deadline:
        STOP_EVENT.wait(timeout=0.5)


def listen_to_reader(
    reader: ReaderConfig,
    reader_ip: str,
    force_udp: bool,
    local_stop_event: threading.Event,
) -> bool:
    protocol = "UDP" if force_udp else "TCP"
    connection = None
    zk = ZK(
        reader_ip,
        port=reader.port,
        timeout=reader.timeout,
        password=reader.password,
        force_udp=force_udp,
        ommit_ping=True,
    )

    try:
        LOGGER.info("%s: conectando a %s:%s por %s...", reader.label, reader_ip, reader.port, protocol)
        connection = zk.connect()
        connection.enable_device()
        information = device_information(connection)
        send_heartbeat(reader, "connected", reader_ip, information)
        pending_command = fetch_next_command(reader)
        if pending_command is not None:
            process_command(reader, connection, pending_command)
            return True

        synchronize_attendance_history(reader, connection, reader_ip)
        LOGGER.info("%s: conectado a %s por %s; esperando ponches.", reader.label, reader_ip, protocol)
        last_heartbeat = time.monotonic()
        last_history_sync = time.monotonic()
        pending_command = None

        while not should_stop(local_stop_event):
            if pending_command is not None:
                process_command(reader, connection, pending_command)
                return True

            restart_capture_for_history = False
            for attendance in connection.live_capture(new_timeout=10):
                if should_stop(local_stop_event):
                    connection.cancel_capture()
                    return True

                if attendance is not None:
                    LOGGER.info(
                        "%s: ponche recibido desde %s: ID=%s, hora=%s, estado=%s.",
                        reader.label,
                        reader_ip,
                        attendance.user_id,
                        attendance.timestamp,
                        attendance.status,
                    )
                    send_attendance(reader, reader_ip, attendance)

                current_time = time.monotonic()
                if current_time - last_heartbeat >= HEARTBEAT_SECONDS:
                    send_heartbeat(reader, "connected", reader_ip)
                    last_heartbeat = current_time

                pending_command = fetch_next_command(reader)
                if pending_command is not None:
                    connection.cancel_capture()
                    break

                if current_time - last_history_sync >= HISTORY_SYNC_SECONDS:
                    connection.cancel_capture()
                    restart_capture_for_history = True
                    break

            if restart_capture_for_history:
                synchronize_attendance_history(reader, connection, reader_ip)
                last_history_sync = time.monotonic()
                continue

            if pending_command is None:
                LOGGER.warning("%s: el lector %s cerró la captura en vivo.", reader.label, reader_ip)
                return True

        return True
    except Exception as error:
        send_heartbeat(reader, "disconnected", reader_ip, error=str(error))
        LOGGER.warning("%s: falló %s por %s: %s", reader.label, reader_ip, protocol, error)
        return False
    finally:
        if connection is not None:
            try:
                connection.disconnect()
            except Exception as error:
                LOGGER.debug("%s: error al desconectar: %s", reader.label, error)


def run_reader_service(reader: ReaderConfig, local_stop_event: threading.Event) -> None:
    while not should_stop(local_stop_event):
        candidates = resolve_reader_ips(reader)
        connected = False

        for reader_ip in candidates:
            for force_udp in (False, True):
                if should_stop(local_stop_event):
                    return
                if listen_to_reader(reader, reader_ip, force_udp, local_stop_event):
                    connected = True
                    break
            if connected:
                break

        if not should_stop(local_stop_event):
            LOGGER.info("%s: reintentando conexión en %s segundos.", reader.label, RECONNECT_DELAY)
            wait_for_retry(local_stop_event)


def reconcile_workers(
    workers: dict[str, ReaderWorker],
    readers: list[ReaderConfig],
) -> None:
    desired = {reader.key: reader for reader in readers}

    for key, worker in list(workers.items()):
        replacement_needed = key not in desired or worker.config != desired[key]
        if replacement_needed:
            worker.stop_event.set()
        if not worker.thread.is_alive():
            workers.pop(key)

    for key, reader in desired.items():
        if key in workers:
            continue

        local_stop_event = threading.Event()
        thread = threading.Thread(
            target=run_reader_service,
            args=(reader, local_stop_event),
            name=reader.key,
            daemon=True,
        )
        workers[key] = ReaderWorker(reader, local_stop_event, thread)
        thread.start()
        LOGGER.info("Supervisor iniciado para %s, MAC %s.", reader.label, reader.mac)


def run_service() -> None:
    environment_readers = load_environment_reader_configs()
    workers: dict[str, ReaderWorker] = {}
    reconcile_workers(workers, environment_readers)
    LOGGER.info("Servicio biométrico iniciado. Destino: %s", ATTENDANCE_URL)
    last_device_sync = 0.0

    try:
        while not STOP_EVENT.is_set():
            current_time = time.monotonic()
            if current_time - last_device_sync >= DEVICE_SYNC_SECONDS:
                registered_readers = fetch_registered_readers()
                if registered_readers is not None:
                    reconcile_workers(workers, registered_readers)
                last_device_sync = current_time

            for key, worker in list(workers.items()):
                if not worker.thread.is_alive():
                    workers.pop(key)

            STOP_EVENT.wait(timeout=1)
    except KeyboardInterrupt:
        LOGGER.info("Deteniendo servicio biométrico...")
    finally:
        STOP_EVENT.set()
        for worker in workers.values():
            worker.stop_event.set()


if __name__ == "__main__":
    try:
        run_service()
    except (ValueError, json.JSONDecodeError) as error:
        LOGGER.critical("Configuración biométrica inválida: %s", error)
        raise SystemExit(1) from error
