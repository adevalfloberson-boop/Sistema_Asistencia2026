<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = config('initial-admin.password');

        if (is_string($adminPassword) && $adminPassword !== '') {
            User::query()->firstOrCreate(
                ['email' => config('initial-admin.email')],
                [
                    'school_id' => null,
                    'name' => 'Superadministrador',
                    'username' => config('initial-admin.username'),
                    'role' => 'superadmin',
                    'is_active' => true,
                    'password' => $adminPassword,
                ],
            );
        }
    }
}
