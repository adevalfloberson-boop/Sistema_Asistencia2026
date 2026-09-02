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
        'primary_color',
        'attendance_cooldown_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'attendance_cooldown_minutes' => 'integer',
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
