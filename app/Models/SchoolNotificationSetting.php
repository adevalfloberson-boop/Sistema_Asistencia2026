<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolNotificationSetting extends Model
{
    protected $fillable = [
        'school_id',
        'email_enabled',
        'notify_entry',
        'notify_exit',
        'notify_early_departure',
        'send_to_father',
        'send_to_mother',
        'smtp_host',
        'smtp_port',
        'smtp_security',
        'smtp_username',
        'smtp_password',
        'from_address',
        'from_name',
        'developer_branding_enabled',
        'developer_name',
        'developer_message',
        'developer_phone',
        'developer_email',
        'developer_website',
    ];

    protected $hidden = ['smtp_password'];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'notify_entry' => 'boolean',
            'notify_exit' => 'boolean',
            'notify_early_departure' => 'boolean',
            'send_to_father' => 'boolean',
            'send_to_mother' => 'boolean',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
            'developer_branding_enabled' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
