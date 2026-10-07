<x-mail::message>
# Resumen de lectores

Estado actual de los lectores de **{{ $school->name }}** al {{ now()->format('d/m/Y H:i:s') }}.

<x-mail::table>
| Lector | Estado | Último contacto | Caídas registradas |
|:--|:--|:--|--:|
@forelse ($devices as $device)
| {{ $device->name }} | {{ $device->connectionStatus() === 'online' ? 'En línea' : 'Sin comunicación' }} | {{ $device->last_seen_at?->format('d/m/Y H:i:s') ?: 'Nunca' }} | {{ $device->disconnection_count }} |
@empty
| Sin lectores activos | — | — | 0 |
@endforelse
</x-mail::table>

**Total de caídas registradas desde la activación del monitoreo:** {{ $devices->sum('disconnection_count') }}

Atentamente,<br>
{{ $setting->from_name ?: $school->name }}
</x-mail::message>
