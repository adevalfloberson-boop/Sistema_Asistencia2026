<x-mail::message>
# Lector desconectado

El sistema dejó de recibir comunicación del lector **{{ $device->name }}**.

<x-mail::table>
| Información | Detalle |
|:--|:--|
| Escuela | {{ $school->name }} |
| Lector | {{ $device->name }} |
| Serie | {{ $device->serial_number ?: 'No registrada' }} |
| Ubicación | {{ $device->location ?: 'No especificada' }} |
| Último contacto | {{ $device->last_seen_at?->format('d/m/Y H:i:s') ?: 'No disponible' }} |
| IP observada | {{ $device->ip_address ?: 'No disponible' }} |
| Caídas registradas | {{ $device->disconnection_count }} |
</x-mail::table>

Se enviará una nueva alerta solamente si el lector vuelve a conectarse y luego se desconecta otra vez.

Atentamente,<br>
{{ $setting->from_name ?: $school->name }}
</x-mail::message>
