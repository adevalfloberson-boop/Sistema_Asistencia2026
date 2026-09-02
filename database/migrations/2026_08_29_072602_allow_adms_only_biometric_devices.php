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
        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->string('mac_address')->nullable()->change();
            $table->string('network')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->string('mac_address')->nullable(false)->change();
            $table->string('network')->nullable(false)->default('192.168.100')->change();
        });
    }
};
