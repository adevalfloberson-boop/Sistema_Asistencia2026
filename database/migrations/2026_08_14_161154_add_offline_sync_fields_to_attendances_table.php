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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('device_event_key', 64)->nullable()->after('reader_ip')->unique();
            $table->string('sync_source')->default('live')->after('device_event_key')->index();
            $table->unsignedSmallInteger('device_status')->nullable()->after('sync_source');
            $table->unsignedSmallInteger('device_punch')->nullable()->after('device_status');
            $table->timestamp('received_at')->nullable()->after('fecha_hora')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['device_event_key']);
            $table->dropIndex(['sync_source']);
            $table->dropIndex(['received_at']);
            $table->dropColumn([
                'device_event_key',
                'sync_source',
                'device_status',
                'device_punch',
                'received_at',
            ]);
        });
    }
};
