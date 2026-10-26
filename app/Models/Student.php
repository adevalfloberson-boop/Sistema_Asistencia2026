<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'course_id',
        'matricula',
        'nombre',
        'apellido',
        'sexo',
        'father_email',
        'mother_email',
        'numero_lista',
        'area',
        'seccion',
        'curso',
        'id_lector',
        'photo_path',
        'face_photo_path',
        'face_sync_status',
        'face_consent_at',
        'face_synced_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'numero_lista' => 'integer',
            'face_consent_at' => 'datetime',
            'face_synced_at' => 'datetime',
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

    public function earlyDepartureAuthorizations(): HasMany
    {
        return $this->hasMany(EarlyDepartureAuthorization::class);
    }

    public function attendanceExceptions(): HasMany
    {
        return $this->hasMany(StudentAttendanceException::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path === null ? null : Storage::disk('public')->url($this->photo_path);
    }
}
