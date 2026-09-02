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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
            $table->string('matricula')->nullable();
            $table->string('id_lector');
            $table->dateTime('fecha_hora');
            $table->string('curso')->nullable();
            $table->string('estado'); // 'A tiempo', 'Tarde'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
