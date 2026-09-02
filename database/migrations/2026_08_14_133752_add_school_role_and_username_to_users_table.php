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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('username')->nullable()->after('name');
            $table->string('role')->default('teacher')->after('username')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->unique(['school_id', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'username']);
            $table->dropColumn(['username', 'role', 'is_active']);
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
