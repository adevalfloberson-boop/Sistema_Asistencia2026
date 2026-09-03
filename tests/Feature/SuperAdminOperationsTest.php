<?php

use App\Models\School;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->superadmin = User::factory()->create(['role' => 'superadmin', 'school_id' => null]);
    $this->session = ['user' => ['id' => $this->superadmin->id, 'role' => 'superadmin', 'username' => $this->superadmin->username]];
});

test('superadministrator has an independent saas console', function () {
    $this->withSession($this->session)->get(route('dashboard.superadmin'))
        ->assertOk()
        ->assertSee('Consola global del SuperAdmin')
        ->assertSee('Crear institución')
        ->assertSee('Auditoría técnica');
});

test('superadministrator can create and suspend an institution with an audit log', function () {
    $this->withSession($this->session)->post(route('superadmin.schools.store'), [
        'code' => 'COLEGIO01',
        'name' => 'Colegio Uno',
        'tax_id' => 'RUT-123',
        'active_modules' => ['attendance'],
    ])->assertRedirect(route('dashboard.superadmin'));

    $school = School::query()->where('code', 'COLEGIO01')->firstOrFail();
    expect($school->active_modules)->toBe(['attendance']);

    $this->withSession($this->session)->put(route('superadmin.schools.status', $school), ['is_active' => false])
        ->assertRedirect(route('dashboard.superadmin'));

    expect($school->fresh()->is_active)->toBeFalse()
        ->and(SystemAuditLog::query()->where('event', 'school.status_changed')->exists())->toBeTrue();
});

test('superadministrator can create a master administrator and download an encrypted backup', function () {
    $school = School::query()->create(['code' => 'COLEGIO02', 'name' => 'Colegio Dos']);

    $this->withSession($this->session)->post(route('superadmin.administrators.store'), [
        'school_id' => $school->id,
        'name' => 'Directora Dos',
        'username' => 'directora',
        'email' => 'directora@colegio.test',
        'password' => 'clave-segura-2026',
        'password_confirmation' => 'clave-segura-2026',
    ])->assertRedirect(route('dashboard.superadmin'));

    expect(User::query()->where('username', 'directora')->value('role'))->toBe('admin');

    $this->withSession($this->session)->get(route('superadmin.schools.backup', $school))
        ->assertOk()
        ->assertHeader('content-type', 'application/octet-stream')
        ->assertDontSee('Colegio Dos');
});
