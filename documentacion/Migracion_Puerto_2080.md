# Migración del listener web al puerto 2080

Este cambio mueve el listener web/nodo local del portal al puerto `2080`.

## Qué cambia

| Componente | Antes | Ahora |
| --- | ---: | ---: |
| URL local de ejemplo | `http://localhost:8000` | `http://localhost:2080` |
| Nodo Windows (`run-school-node.ps1`) | `8000` | `2080` |
| Instalador del nodo Windows | `8000` | `2080` |
| Servidor de desarrollo Windows sin OpenSSL | `8000` | `SERVER_PORT` o `2080` |

El puerto `2080` corresponde al proceso web que expone Laravel. No cambia el puerto del protocolo del lector SDK, cuyo valor por defecto continúa siendo `4370` en la configuración de cada dispositivo.

## Aplicación

1. Definir en `.env`:

   ```dotenv
   APP_URL=https://dominio-publico:2080
   SERVER_PORT=2080
   ```

2. Permitir TCP `2080` en el firewall del equipo servidor.
3. Si existe NAT, proxy inverso o Tailscale Serve, apuntarlo al listener local `127.0.0.1:2080`.
4. Configurar cada lector ADMS con la dirección `dominio-publico` y el puerto `2080`.
5. Confirmar que el lector envía los ponches a `/iclock/cdata`.
6. Reiniciar el nodo y confirmar que el proceso escucha en `2080`.

## Verificación

En Windows:

```powershell
Get-NetTCPConnection -LocalPort 2080 -State Listen
.\scripts\windows\run-school-node.ps1 -Port 2080
```

Validar después:

- `GET /login` responde.
- El lector puede consultar `/iclock/cdata`.
- Una marcación llega a `/iclock/cdata` y aparece en `attendances`.
- `/iclock/getrequest` y `/iclock/devicecmd` siguen intercambiando comandos.
- `python scripts/biometrico.py` muestra la dirección ADMS y rechaza un `APP_URL` sin `:2080`.

## Rollback

Para volver temporalmente al puerto anterior, iniciar explícitamente el nodo con `-Port 8000`, restaurar `APP_URL` y ajustar firewall/proxy:

```powershell
.\scripts\windows\run-school-node.ps1 -Port 8000
```

El rollback de red no requiere modificar las rutas de Laravel ni los registros almacenados.
