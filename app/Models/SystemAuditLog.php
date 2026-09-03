<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'school_id',
        'event',
        'description',
        'ip_address',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
