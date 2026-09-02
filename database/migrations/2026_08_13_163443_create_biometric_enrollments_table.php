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
        Schema::create('biometric_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_command_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_id');
            $table->unsignedTinyInteger('finger_index');
            $table->string('status')->default('pending')->index();
            $table->timestamp('enrolled_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(
                ['biometric_device_id', 'student_id', 'finger_index'],
                'device_student_finger_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_enrollments');
    }
};
