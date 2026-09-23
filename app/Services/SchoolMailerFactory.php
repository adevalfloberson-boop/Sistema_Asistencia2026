<?php

namespace App\Services;

use App\Models\SchoolNotificationSetting;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SchoolMailerFactory
{
    public function make(SchoolNotificationSetting $setting): Mailer
    {
        $mailerName = 'school-smtp-'.$setting->school_id;
        $scheme = $setting->smtp_security === 'ssl_tls' ? 'smtps' : null;

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $setting->smtp_host,
            'port' => $setting->smtp_port,
            'username' => $setting->smtp_username,
            'password' => $setting->smtp_password,
            'timeout' => 10,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);
        Mail::purge($mailerName);

        return Mail::mailer($mailerName);
    }
}
