<x-mail::message>
# {{ $isTest ? 'Configuración de correo verificada' : 'Registro de '.$eventLabel }}

@if ($isTest)
Este correo confirma que la configuración SMTP de **{{ $attendance->school->name }}** funciona correctamente.
@else
Se ha registrado la {{ mb_strtolower($eventLabel) }} de **{{ $attendance->student->nombre }} {{ $attendance->student->apellido }}**.

<x-mail::table>
| Información | Detalle |
| :-- | :-- |
| Estudiante | {{ $attendance->student->nombre }} {{ $attendance->student->apellido }} |
| Escuela | {{ $attendance->school->name }} |
| Fecha | {{ $attendance->fecha_hora->format('d/m/Y') }} |
| Hora | {{ $attendance->fecha_hora->format('H:i') }} |
| Tipo de registro | {{ $eventLabel }} |
</x-mail::table>
@endif

Este es un mensaje informativo generado por el sistema de asistencia.

Atentamente,<br>
{{ $attendance->school->name }}

@php($branding = $attendance->school->notificationSetting)
@if ($branding?->developer_branding_enabled && $branding?->developer_name)
---

<small>
Desarrollado por **{{ $branding->developer_name }}**@if($branding->developer_message)<br>{{ $branding->developer_message }}@endif
@if($branding->developer_phone)<br>Teléfono / WhatsApp: {{ $branding->developer_phone }}@endif
@if($branding->developer_email)<br>Correo: [{{ $branding->developer_email }}](mailto:{{ $branding->developer_email }})@endif
@if($branding->developer_website)<br>Sitio web: [{{ $branding->developer_website }}]({{ $branding->developer_website }})@endif
</small>
@endif
</x-mail::message>
