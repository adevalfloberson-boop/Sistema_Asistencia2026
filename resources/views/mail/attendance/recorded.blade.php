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
</x-mail::message>
