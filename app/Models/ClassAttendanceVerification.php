<?php

namespace App\Models;

use Database\Factories\ClassAttendanceVerificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAttendanceVerification extends Model
{
    /** @use HasFactory<ClassAttendanceVerificationFactory> */
    use HasFactory;

    public const StatusPresent = 'present';

    public const StatusCampusAbsentClass = 'campus_absent_class';

    public const StatusAbsentCampus = 'absent_campus';

    public const StatusLate = 'late';

    public const StatusExcused = 'excused';

    protected $fillable = [
        'class_session_id',
        'student_id',
        'teacher_id',
        'status',
        'was_on_campus',
        'note',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'was_on_campus' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::StatusPresent => 'Presente en clase',
            self::StatusCampusAbsentClass => 'En el plantel, ausente de clase',
            self::StatusAbsentCampus => 'Ausente del plantel',
            self::StatusLate => 'Llegó tarde a clase',
            self::StatusExcused => 'Ausencia justificada',
        ];
    }
}
