"""Show the ADMS endpoints used by ZKTeco readers.

In ADMS mode no local SDK agent is required. Each reader connects directly to
Laravel through the public URL configured in APP_URL.
"""

from __future__ import annotations

import os
from pathlib import Path

from dotenv import load_dotenv


PROJECT_ROOT = Path(__file__).resolve().parent.parent
load_dotenv(PROJECT_ROOT / ".env")


def main() -> None:
    application_url = os.getenv("APP_URL", "").rstrip("/")

    if not application_url:
        raise SystemExit("APP_URL no está configurado. Define primero el dominio público del sistema.")

    print("Modo ADMS activo: no se inicia ningún agente SDK local.")
    print("Configure cada lector ZKTeco con el dominio público del sistema.")
    print()
    print(f"Servidor ADMS: {application_url}")
    print(f"Eventos:       {application_url}/iclock/cdata")
    print(f"Comandos:      {application_url}/iclock/getrequest")
    print(f"Confirmación:  {application_url}/iclock/devicecmd")
    print()
    print("El lector debe estar registrado en el panel con su número de serie y modo ADMS.")
    print("La MAC puede guardarse para identificación; no se escanea la red local.")


if __name__ == "__main__":
    main()
