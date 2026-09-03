<?php

use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadministrator sees readers from every school in one network console', function () {
    $north = School::query()->create(['code' => 'NORTE', 'name' => 'Escuela Norte']);
    $south = School::query()->create(['code' => 'SUR', 'name' => 'Escuela Sur']);
    $admin = User::factory()->create(['username' => 'admin', 'role' => 'superadmin']);

    BiometricDevice::query()->create([
        'school_id' => $north->id,
        'key' => 'norte-entrada',
        'name' => 'Entrada Norte',
        'mac_address' => '00:11:22:33:44:01',
        'network' => '192.168.10',
        'last_seen_at' => now(),
    ]);
    BiometricDevice::query()->create([
        'school_id' => $south->id,
        'key' => 'sur-entrada',
        'name' => 'Entrada Sur',
        'mac_address' => '00:11:22:33:44:02',
        'network' => '192.168.20',
    ]);

    $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'superadmin',
        'institution_code' => $north->code,
    ]])->get(route('dashboard.admin.page', 'devices'))
        ->assertOk()
        ->assertSee('Red global de lectores')
        ->assertSee('Escuela Norte')
        ->assertSee('Escuela Sur')
        ->assertSee('Entrada Norte')
        ->assertSee('Entrada Sur');
});

test('teacher cannot open the superadministrator panel', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->withSession(['user' => [
        'id' => $teacher->id,
        'username' => $teacher->username,
        'role' => 'teacher',
    ]])->get(route('dashboard.admin'))->assertForbidden();
});
