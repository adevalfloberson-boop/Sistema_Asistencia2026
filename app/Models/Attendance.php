<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'school_id',
        'biometric_device_id',
        'student_id',
        'matricula',
        'id_lector',
        'reader_key',
        'reader_name',
        'reader_school',
        'reader_mac',
        'reader_ip',
        'device_event_key',
        'sync_source',
        'device_status',
        'device_punch',
        'is_ignored',
        'ignored_reason',
        'fecha_hora',
        'received_at',
        'curso',
        'estado',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'received_at' => 'datetime',
            'is_ignored' => 'boolean',
        ];
    }

    /**
     * Get the student that owns the attendance.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }
}
