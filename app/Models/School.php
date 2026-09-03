<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'code',
        'name',
        'short_name',
        'address',
        'phone',
        'email',
        'tax_id',
        'logo_path',
        'active_modules',
        'primary_color',
        'attendance_cooldown_minutes',
        'attendance_entry_time',
        'attendance_exit_time',
        'attendance_late_grace_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'attendance_cooldown_minutes' => 'integer',
            'attendance_entry_time' => 'datetime:H:i',
            'attendance_exit_time' => 'datetime:H:i',
            'attendance_late_grace_minutes' => 'integer',
            'active_modules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(BiometricDevice::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
