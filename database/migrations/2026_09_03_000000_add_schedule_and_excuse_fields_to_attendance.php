<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->time('attendance_entry_time')->default('08:00:00')->after('attendance_cooldown_minutes');
            $table->time('attendance_exit_time')->default('14:00:00')->after('attendance_entry_time');
            $table->unsignedSmallInteger('attendance_late_grace_minutes')->default(0)->after('attendance_exit_time');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->boolean('is_late')->default(false)->after('is_ignored')->index();
            $table->boolean('is_early_departure')->default(false)->after('is_late')->index();
            $table->string('excuse_type')->nullable()->after('ignored_reason');
            $table->text('excuse_note')->nullable()->after('excuse_type');
            $table->foreignId('excused_by')->nullable()->after('excuse_note')->constrained('users')->nullOnDelete();
            $table->timestamp('excused_at')->nullable()->after('excused_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('excused_by');
            $table->dropIndex(['is_late']);
            $table->dropIndex(['is_early_departure']);
            $table->dropColumn(['is_late', 'is_early_departure', 'excuse_type', 'excuse_note', 'excused_at']);
        });

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['attendance_entry_time', 'attendance_exit_time', 'attendance_late_grace_minutes']);
        });
    }
};
