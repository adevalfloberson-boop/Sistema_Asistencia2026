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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('mac_address')->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->string('model')->nullable();
            $table->string('location')->nullable();
            $table->string('connection_mode')->default('sdk')->index();
            $table->string('network')->default('192.168.100');
            $table->string('ip_address')->nullable();
            $table->unsignedSmallInteger('port')->default(4370);
            $table->text('device_password')->nullable();
            $table->string('status')->default('never_connected')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_disconnected_at')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('platform')->nullable();
            $table->unsignedInteger('user_count')->default(0);
            $table->unsignedInteger('fingerprint_count')->default(0);
            $table->unsignedInteger('attendance_count')->default(0);
            $table->json('capacity')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};
