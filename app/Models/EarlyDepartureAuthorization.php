<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarlyDepartureAuthorization extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'authorized_for',
        'authorized_by_name',
        'reason',
        'attendance_id',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_for' => 'date',
            'used_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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
