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
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->json('payload')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->string('requested_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
