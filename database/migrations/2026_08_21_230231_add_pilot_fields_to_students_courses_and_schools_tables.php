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
        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedSmallInteger('attendance_cooldown_minutes')->default(10);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('area')->nullable();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedSmallInteger('numero_lista')->nullable()->index();
            $table->string('area')->nullable();
            $table->string('seccion')->nullable();
            $table->unique(['course_id', 'numero_lista']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'numero_lista']);
            $table->dropIndex(['numero_lista']);
            $table->dropColumn(['numero_lista', 'area', 'seccion']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('area');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('attendance_cooldown_minutes');
        });
    }
};
