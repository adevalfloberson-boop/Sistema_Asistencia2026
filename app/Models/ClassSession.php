<?php

namespace App\Models;

use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'teacher_id',
        'subject',
        'scheduled_at',
        'started_at',
        'ended_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(ClassAttendanceVerification::class);
    }
}
