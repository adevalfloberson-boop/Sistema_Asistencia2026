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
        Schema::table('school_notification_settings', function (Blueprint $table) {
            $table->boolean('developer_branding_enabled')->default(false)->after('from_name');
            $table->string('developer_name')->nullable()->after('developer_branding_enabled');
            $table->text('developer_message')->nullable()->after('developer_name');
            $table->string('developer_phone', 50)->nullable()->after('developer_message');
            $table->string('developer_email')->nullable()->after('developer_phone');
            $table->string('developer_website')->nullable()->after('developer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_notification_settings', function (Blueprint $table) {
            $table->dropColumn([
                'developer_branding_enabled',
                'developer_name',
                'developer_message',
                'developer_phone',
                'developer_email',
                'developer_website',
            ]);
        });
    }
};
