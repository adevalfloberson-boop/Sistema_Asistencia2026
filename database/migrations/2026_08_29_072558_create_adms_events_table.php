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
        Schema::create('adms_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_event_key', 64)->unique();
            $table->string('table_name', 30)->default('ATTLOG')->index();
            $table->string('user_id')->nullable()->index();
            $table->timestamp('event_at')->nullable()->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedSmallInteger('verify_mode')->nullable();
            $table->string('processing_status', 30)->default('pending')->index();
            $table->text('raw_payload');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adms_events');
    }
};
