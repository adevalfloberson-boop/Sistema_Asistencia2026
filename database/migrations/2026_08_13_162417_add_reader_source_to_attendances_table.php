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
            $table->string('reader_key')->nullable()->after('id_lector')->index();
            $table->string('reader_name')->nullable()->after('reader_key');
            $table->string('reader_school')->nullable()->after('reader_name')->index();
            $table->string('reader_mac')->nullable()->after('reader_school');
            $table->string('reader_ip')->nullable()->after('reader_mac');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['reader_key']);
            $table->dropIndex(['reader_school']);
            $table->dropColumn([
                'reader_key',
                'reader_name',
                'reader_school',
                'reader_mac',
                'reader_ip',
            ]);
        });
    }
};
