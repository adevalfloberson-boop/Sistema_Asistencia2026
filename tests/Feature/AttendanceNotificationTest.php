<?php

use App\Mail\AttendanceRecordedMail;
use App\Models\Attendance;
use App\Models\AttendanceNotification;
use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\Student;
use App\Services\BiometricAttendanceRecorder;
use App\Services\SchoolMailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\PendingMail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->school = School::query()->create(['code' => 'NOTIFY', 'name' => 'Centro Notificaciones', 'attendance_cooldown_minutes' => 1, 'attendance_entry_time' => '08:00', 'attendance_exit_time' => '14:00', 'attendance_late_grace_minutes' => 10]);
    $this->school->notificationSetting()->create(['email_enabled' => true, 'notify_entry' => true, 'notify_exit' => true, 'notify_early_departure' => true, 'send_to_father' => true, 'send_to_mother' => true, 'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_security' => 'starttls', 'smtp_username' => 'mailer', 'smtp_password' => 'secret', 'from_address' => 'asistencia@example.test', 'from_name' => 'Asistencia']);
    $this->student = Student::query()->create(['school_id' => $this->school->id, 'matricula' => 'N-001', 'nombre' => 'Jean', 'apellido' => 'Pierre', 'curso' => 'Primero A', 'id_lector' => '501', 'father_email' => 'familia@example.test', 'mother_email' => 'familia@example.test']);
    $this->device = BiometricDevice::query()->create(['school_id' => $this->school->id, 'key' => 'notify-reader', 'name' => 'Entrada', 'mac_address' => '00:17:61:11:18:f4', 'network' => '192.168.20']);
});

test('a live attendance sends one message to duplicate parent addresses and records it', function () {
    $pendingMail = Mockery::mock(PendingMail::class);
    $pendingMail->shouldReceive('send')->once()->with(Mockery::type(AttendanceRecordedMail::class));
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('to')->once()->with('familia@example.test')->andReturn($pendingMail);
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($mailer);
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $result = app(BiometricAttendanceRecorder::class)->record($this->student, $this->device, ['reader_key' => $this->device->key, 'event_source' => 'live']);
    expect($result['created'])->toBeTrue()->and(Attendance::query()->count())->toBe(1)->and(AttendanceNotification::query()->where('status', 'Enviado')->count())->toBe(1);
});

test('mail failure does not prevent attendance and is recorded safely', function () {
    $pendingMail = Mockery::mock(PendingMail::class);
    $pendingMail->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP connection failed'));
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('to')->once()->andReturn($pendingMail);
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($mailer);
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $result = app(BiometricAttendanceRecorder::class)->record($this->student, $this->device, ['reader_key' => $this->device->key, 'event_source' => 'live']);
    expect($result['created'])->toBeTrue()->and(Attendance::query()->count())->toBe(1)->and(AttendanceNotification::query()->value('status'))->toBe('Fallido');
});

test('disabled notifications leave attendance working and record an omitted result', function () {
    $this->school->notificationSetting->update(['email_enabled' => false]);
    $result = app(BiometricAttendanceRecorder::class)->record($this->student, $this->device, ['reader_key' => $this->device->key, 'event_source' => 'live']);
    expect($result['created'])->toBeTrue()->and(Attendance::query()->count())->toBe(1)->and(AttendanceNotification::query()->value('status'))->toBe('Omitido');
});

test('history synchronization does not send family notifications', function () {
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldNotReceive('make');
    $this->app->instance(SchoolMailerFactory::class, $factory);
    app(BiometricAttendanceRecorder::class)->record($this->student, $this->device, ['reader_key' => $this->device->key, 'event_source' => 'history', 'event_timestamp' => '2026-09-20 08:00:00']);
    expect(Attendance::query()->count())->toBe(1)->and(AttendanceNotification::query()->count())->toBe(0);
});
