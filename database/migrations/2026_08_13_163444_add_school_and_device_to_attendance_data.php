<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('id_lector')->index();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('biometric_device_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
        });

        $schoolId = DB::table('schools')->where('code', 'INST001')->value('id');

        if ($schoolId !== null) {
            DB::table('students')->whereNull('school_id')->update(['school_id' => $schoolId]);
            DB::table('attendances')->whereNull('school_id')->update(['school_id' => $schoolId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('biometric_device_id');
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
