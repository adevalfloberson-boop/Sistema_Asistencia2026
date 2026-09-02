<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmsEvent extends Model
{
    protected $fillable = [
        'biometric_device_id',
        'student_id',
        'attendance_id',
        'device_event_key',
        'table_name',
        'user_id',
        'event_at',
        'status_code',
        'verify_mode',
        'processing_status',
        'raw_payload',
        'error',
    ];

    protected function casts(): array
    {
        return ['event_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
