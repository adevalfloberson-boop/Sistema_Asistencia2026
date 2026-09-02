<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricEnrollment extends Model
{
    protected $fillable = [
        'biometric_device_id',
        'student_id',
        'device_command_id',
        'user_id',
        'finger_index',
        'status',
        'enrolled_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(DeviceCommand::class, 'device_command_id');
    }
}
