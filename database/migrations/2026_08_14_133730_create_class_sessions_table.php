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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->dateTime('scheduled_at')->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamps();

            $table->index(['course_id', 'teacher_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
