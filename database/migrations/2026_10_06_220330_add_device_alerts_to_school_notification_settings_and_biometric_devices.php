<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_notification_settings', function (Blueprint $table): void {
            $table->boolean('device_alerts_enabled')->default(false)->after('email_enabled');
            $table->text('device_alert_email')->nullable()->after('device_alerts_enabled');
        });

        Schema::table('biometric_devices', function (Blueprint $table): void {
            $table->timestamp('disconnect_alert_sent_at')->nullable()->after('last_disconnected_at');
            $table->unsignedInteger('disconnection_count')->default(0)->after('disconnect_alert_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_notification_settings', function (Blueprint $table): void {
            $table->dropColumn(['device_alerts_enabled', 'device_alert_email']);
        });

        Schema::table('biometric_devices', function (Blueprint $table): void {
            $table->dropColumn(['disconnect_alert_sent_at', 'disconnection_count']);
        });
    }
};
