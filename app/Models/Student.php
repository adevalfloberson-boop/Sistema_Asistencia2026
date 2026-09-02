<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'course_id',
        'matricula',
        'nombre',
        'apellido',
        'numero_lista',
        'area',
        'seccion',
        'curso',
        'id_lector',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'numero_lista' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classVerifications(): HasMany
    {
        return $this->hasMany(ClassAttendanceVerification::class);
    }

    /**
     * Get the attendances for the student.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(BiometricEnrollment::class);
    }
}
