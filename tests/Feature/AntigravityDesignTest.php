<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('hub comercial renders successfully', function () {
    $response = $this->get(route('antigravity.hub'));
    $response->assertOk();
    $response->assertSee('Sistema de Asistencia Escolar Biométrico');
});

test('login corporativo split-screen renders successfully', function () {
    $response = $this->get(route('antigravity.login'));
    $response->assertOk();
    $response->assertSee('Iniciar Sesión');
    $response->assertSee('Directora');
});

test('admin dashboard renders successfully with metrics', function () {
    $response = $this->get(route('antigravity.admin'));
    $response->assertOk();
    $response->assertSee('Panel Directivo');
    $response->assertSee('Asistencia Global');
    $response->assertSee('Semáforo Directivo');
    $response->assertSee('Verde · Presente');
    $response->assertSee('Amarillo · Tardanza');
    $response->assertSee('Rojo · Ausente');
});

test('student directory renders successfully', function () {
    $response = $this->get(route('antigravity.admin.students'));
    $response->assertOk();
    $response->assertSee('Directorio Académico');
});

test('enrollment station renders with 10 finger map', function () {
    $response = $this->get(route('antigravity.admin.enrollment'));
    $response->assertOk();
    $response->assertSee('Estación de Enrolamiento');
    $response->assertSee('Índice');
});

test('biometric device radar renders successfully', function () {
    $response = $this->get(route('antigravity.admin.devices'));
    $response->assertOk();
    $response->assertSee('ZKTeco');
    $response->assertSee('ADMS');
});

test('teacher classroom roll call panel renders successfully', function () {
    $response = $this->get(route('antigravity.teacher'));
    $response->assertOk();
    $response->assertSee('Aula Activa');
    $response->assertSee('Presente');
});

test('superadmin saas console renders successfully', function () {
    $response = $this->get(route('antigravity.superadmin'));
    $response->assertOk();
    $response->assertSee('Consola Global SaaS');
});

test('kiosk reception view renders successfully', function () {
    $response = $this->get(route('antigravity.kiosk'));
    $response->assertOk();
    $response->assertSee('Recepción y Control de Acceso');
});

test('quick login logs in and redirects properly', function () {
    $response = $this->get(route('antigravity.quick-login', 'director'));
    $response->assertRedirect(route('antigravity.admin'));
    expect(session('user.role'))->toBe('admin');
});
