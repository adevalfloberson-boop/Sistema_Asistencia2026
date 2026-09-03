<?php

use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('initial-admin.password');
});

test('seeding without initial credentials leaves operational data untouched', function () {
    $this->seed();

    expect(User::query()->count())->toBe(0)
        ->and(School::query()->count())->toBe(0)
        ->and(BiometricDevice::query()->count())->toBe(0);
});

test('seeding creates only the configured superadministrator and is idempotent', function () {
    config()->set([
        'initial-admin.username' => 'superadmin',
        'initial-admin.email' => 'superadmin@example.test',
        'initial-admin.password' => 'clave-segura-2026',
    ]);

    $this->seed();
    $this->seed();

    $administrator = User::query()->sole();

    expect($administrator->username)->toBe('superadmin')
        ->and($administrator->role)->toBe('superadmin')
        ->and($administrator->school_id)->toBeNull()
        ->and(Hash::check('clave-segura-2026', $administrator->password))->toBeTrue()
        ->and(School::query()->count())->toBe(0)
        ->and(BiometricDevice::query()->count())->toBe(0);
});
