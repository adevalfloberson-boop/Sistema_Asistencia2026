<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    protected $fillable = [
        'school_id',
        'key',
        'name',
        'mac_address',
        'serial_number',
        'model',
        'location',
        'connection_mode',
        'network',
        'ip_address',
        'port',
        'device_password',
        'status',
        'is_active',
        'last_seen_at',
        'last_connected_at',
        'last_disconnected_at',
        'firmware_version',
        'platform',
        'user_count',
        'fingerprint_count',
        'attendance_count',
        'capacity',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'device_password' => 'encrypted',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_connected_at' => 'datetime',
            'last_disconnected_at' => 'datetime',
            'capacity' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(BiometricEnrollment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function admsEvents(): HasMany
    {
        return $this->hasMany(AdmsEvent::class);
    }

    public function connectionStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->last_seen_at === null) {
            return 'never_connected';
        }

        if ($this->last_seen_at->lt(now()->subMinutes(config('attendance.device_offline_after_minutes')))) {
            return 'offline';
        }

        if ($this->last_seen_at->lt(now()->subMinutes(config('attendance.device_delayed_after_minutes')))) {
            return 'delayed';
        }

        return 'online';
    }
}
