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
            $table->text('notification_message')->nullable()->after('from_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_notification_settings', function (Blueprint $table): void {
            $table->dropColumn('notification_message');
        });
    }
};
