<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolScheduleException extends Model
{
    protected $fillable = ['school_id', 'date', 'exit_time', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date', 'exit_time' => 'datetime:H:i'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
