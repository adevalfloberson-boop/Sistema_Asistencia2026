# Migración del listener web al puerto 590

Este cambio mueve el listener web/nodo local del portal al puerto `590`.

## Qué cambia

| Componente | Antes | Ahora |
| --- | ---: | ---: |
| URL local de ejemplo | `http://localhost:8000` | `http://localhost:590` |
| Nodo Windows (`run-school-node.ps1`) | `8000` | `590` |
| Instalador del nodo Windows | `8000` | `590` |
| Servidor de desarrollo Windows sin OpenSSL | `8000` | `SERVER_PORT` o `590` |

El puerto `590` corresponde al proceso web que expone Laravel. No cambia el puerto del protocolo del lector SDK, cuyo valor por defecto continúa siendo `4370` en la configuración de cada dispositivo.

## Aplicación

1. Definir en `.env`:

   ```dotenv
   APP_URL=https://dominio-publico:590
   SERVER_PORT=590
   ```

2. Permitir TCP `590` en el firewall del equipo servidor.
3. Si existe NAT, proxy inverso o Tailscale Serve, apuntarlo al listener local `127.0.0.1:590`.
4. Configurar cada lector ADMS con la dirección `dominio-publico` y el puerto `590`.
5. Confirmar que el lector envía los ponches a `/iclock/cdata`.
6. Reiniciar el nodo y confirmar que el proceso escucha en `590`.

## Verificación

En Windows:

```powershell
Get-NetTCPConnection -LocalPort 590 -State Listen
.\scripts\windows\run-school-node.ps1 -Port 590
```

Validar después:

- `GET /login` responde.
- El lector puede consultar `/iclock/cdata`.
- Una marcación llega a `/iclock/cdata` y aparece en `attendances`.
- `/iclock/getrequest` y `/iclock/devicecmd` siguen intercambiando comandos.
- `python scripts/biometrico.py` muestra la dirección ADMS y rechaza un `APP_URL` sin `:590`.

## Rollback

Para volver temporalmente al puerto anterior, iniciar explícitamente el nodo con `-Port 8000`, restaurar `APP_URL` y ajustar firewall/proxy:

```powershell
.\scripts\windows\run-school-node.ps1 -Port 8000
```

El rollback de red no requiere modificar las rutas de Laravel ni los registros almacenados.
