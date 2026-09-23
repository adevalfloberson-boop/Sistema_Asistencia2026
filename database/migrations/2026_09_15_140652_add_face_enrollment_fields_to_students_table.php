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
        Schema::table('students', function (Blueprint $table) {
            $table->string('face_photo_path')->nullable()->after('id_lector');
            $table->string('face_sync_status')->nullable()->index()->after('face_photo_path');
            $table->timestamp('face_consent_at')->nullable()->after('face_sync_status');
            $table->timestamp('face_synced_at')->nullable()->after('face_consent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'face_photo_path',
                'face_sync_status',
                'face_consent_at',
                'face_synced_at',
            ]);
        });
    }
};
