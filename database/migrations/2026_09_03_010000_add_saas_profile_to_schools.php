<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('tax_id')->nullable()->after('email');
            $table->string('logo_path')->nullable()->after('tax_id');
            $table->json('active_modules')->nullable()->after('logo_path');
        });

        DB::table('schools')->whereNull('active_modules')->update([
            'active_modules' => json_encode(['attendance']),
        ]);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['tax_id', 'logo_path', 'active_modules']);
        });
    }
};
