<?php

use App\Mail\AttendanceRecordedMail;
use App\Models\Attendance;
use App\Models\AttendanceNotification;
use App\Models\School;
use App\Models\Student;
use App\Services\SchoolMailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\PendingMail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-06 08:00:00');
    $this->school = School::query()->create(['code' => 'RESEND', 'name' => 'Centro Reenvío']);
    $this->school->notificationSetting()->create([
        'email_enabled' => true,
        'notify_entry' => true,
        'notify_exit' => true,
        'notify_early_departure' => true,
        'send_to_father' => true,
        'send_to_mother' => true,
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_security' => 'starttls',
        'from_address' => 'asistencia@example.test',
        'from_name' => 'Asistencia',
    ]);
    $this->student = Student::query()->create([
        'school_id' => $this->school->id,
        'matricula' => 'R-001',
        'nombre' => 'Ana',
        'apellido' => 'García',
        'curso' => 'Primero A',
        'id_lector' => '801',
        'father_email' => 'padre@gmail.com',
        'mother_email' => 'madre@outlook.com',
    ]);
    $this->attendance = Attendance::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->student->id,
        'matricula' => $this->student->matricula,
        'id_lector' => $this->student->id_lector,
        'sync_source' => 'adms',
        'fecha_hora' => now(),
        'curso' => $this->student->curso,
        'estado' => 'Entrada',
        'tipo' => 'Entrada',
        'is_ignored' => false,
    ]);
});

afterEach(function (): void {
    $this->travelBack();
});

test('the resend command previews pending Gmail alerts without sending them', function () {
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldNotReceive('make');
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $this->artisan('attendance:resend-missed-emails', ['--date' => '2026-10-06'])
        ->expectsOutputToContain('padre@gmail.com')
        ->expectsOutputToContain('Vista previa')
        ->assertSuccessful();

    expect(AttendanceNotification::query()->count())->toBe(0);
});

test('the resend command sends only unsent Gmail recipients', function () {
    $pendingMail = Mockery::mock(PendingMail::class);
    $pendingMail->shouldReceive('send')->once()->with(Mockery::type(AttendanceRecordedMail::class));
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('to')->once()->with('padre@gmail.com')->andReturn($pendingMail);
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($mailer);
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $this->artisan('attendance:resend-missed-emails', ['--date' => '2026-10-06', '--send' => true])
        ->assertSuccessful();

    expect(AttendanceNotification::query()->where('recipient', 'padre@gmail.com')->where('status', 'Enviado')->count())->toBe(1)
        ->and(AttendanceNotification::query()->where('recipient', 'madre@outlook.com')->count())->toBe(0);
});
