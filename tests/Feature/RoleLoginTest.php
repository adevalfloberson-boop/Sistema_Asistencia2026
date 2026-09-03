<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadministrator signs in without an institution and enters the global console', function () {
    User::factory()->create([
        'username' => 'superadmin',
        'role' => 'superadmin',
        'school_id' => null,
        'password' => 'clave-segura-2026',
    ]);

    $this->post(route('login.attempt'), [
        'username' => 'superadmin',
        'password' => 'clave-segura-2026',
    ])->assertRedirect(route('dashboard.superadmin'));
});

test('viewer signs in to their school and cannot be redirected to an administrator page', function () {
    $school = School::query()->create(['code' => 'INST001', 'name' => 'Centro INST001']);
    User::factory()->create([
        'username' => 'visualizacion',
        'role' => 'viewer',
        'school_id' => $school->id,
        'password' => 'clave-segura-2026',
    ]);

    $this->withSession(['url.intended' => route('dashboard.admin')])
        ->post(route('login.attempt'), [
            'institution_code' => 'INST001',
            'username' => 'visualizacion',
            'password' => 'clave-segura-2026',
        ])->assertRedirect(route('dashboard.viewer'));
});
