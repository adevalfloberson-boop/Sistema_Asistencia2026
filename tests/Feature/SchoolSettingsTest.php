<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function schoolAdminSession(User $admin): array
{
    return ['user' => ['id' => $admin->id, 'username' => $admin->username, 'role' => 'admin', 'school_id' => $admin->school_id]];
}

test('school administrator sees the configuration center for only their school', function () {
    $school = School::query()->create(['code' => 'CONF-A', 'name' => 'Centro A']);
    $otherSchool = School::query()->create(['code' => 'CONF-B', 'name' => 'Centro B']);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $school->id]);

    $this->withSession(schoolAdminSession($admin))->get(route('dashboard.admin.page', 'settings'))
        ->assertOk()->assertSee('Horarios y asistencia')->assertSee('Notificaciones')->assertSee('Servidor SMTP')
        ->assertSee('Resumen de reglas actuales')->assertSee($school->name)->assertDontSee($otherSchool->name);
});

test('administrator updates schedules and notification preferences for their school', function () {
    $school = School::query()->create(['code' => 'RULES', 'name' => 'Centro Reglas']);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $school->id]);
    $session = schoolAdminSession($admin);

    $this->withSession($session)->put(route('schools.settings.update', $school), [
        'attendance_entry_time' => '07:30', 'attendance_exit_time' => '13:30',
        'attendance_late_grace_minutes' => 15,
    ])->assertRedirect(route('dashboard.admin.page', 'settings'));
    $this->withSession($session)->put(route('schools.notifications.update', $school), [
        'email_enabled' => '1', 'notify_entry' => '1', 'notify_early_departure' => '1', 'send_to_father' => '1',
    ])->assertRedirect(route('dashboard.admin.page', 'settings'));

    expect($school->fresh()->attendance_entry_time->format('H:i'))->toBe('07:30')
        ->and($school->fresh()->attendance_late_grace_minutes)->toBe(15)
        ->and($school->notificationSetting->email_enabled)->toBeTrue()
        ->and($school->notificationSetting->notify_exit)->toBeFalse()
        ->and($school->notificationSetting->send_to_mother)->toBeFalse();
});

test('smtp password is encrypted and never rendered back into the settings page', function () {
    $school = School::query()->create(['code' => 'MAIL', 'name' => 'Centro Correo']);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $school->id]);
    $session = schoolAdminSession($admin);

    $this->withSession($session)->put(route('schools.mail.update', $school), [
        'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_security' => 'starttls',
        'smtp_username' => 'mailer@example.test', 'smtp_password' => 'super-secret-password',
        'from_address' => 'asistencia@example.test', 'from_name' => 'Asistencia Escolar',
        'developer_branding_enabled' => '1', 'developer_name' => 'Master BI',
        'developer_message' => 'Tecnología para la educación.', 'developer_phone' => '809-555-0101',
        'developer_email' => 'contacto@masterbi.test', 'developer_website' => 'https://masterbi.test',
    ])->assertRedirect(route('dashboard.admin.page', 'settings'));

    $rawPassword = DB::table('school_notification_settings')->where('school_id', $school->id)->value('smtp_password');
    expect($rawPassword)->not->toBe('super-secret-password')->and($school->notificationSetting->smtp_password)->toBe('super-secret-password');
    expect($school->notificationSetting->developer_branding_enabled)->toBeTrue()
        ->and($school->notificationSetting->developer_name)->toBe('Master BI')
        ->and($school->notificationSetting->developer_website)->toBe('https://masterbi.test');
    $this->withSession($session)->get(route('dashboard.admin.page', 'settings'))->assertOk()->assertDontSee('super-secret-password');
});

test('administrator cannot update configuration belonging to another school', function () {
    $school = School::query()->create(['code' => 'OWN', 'name' => 'Centro Propio']);
    $otherSchool = School::query()->create(['code' => 'OTHER', 'name' => 'Centro Ajeno']);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $school->id]);

    $this->withSession(schoolAdminSession($admin))->put(route('schools.notifications.update', $otherSchool), ['email_enabled' => '1'])->assertForbidden();
    expect($otherSchool->notificationSetting)->toBeNull();
});

test('schedule validation rejects invalid time ranges', function () {
    $school = School::query()->create(['code' => 'VALID', 'name' => 'Centro Validación']);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $school->id]);
    $this->withSession(schoolAdminSession($admin))->put(route('schools.settings.update', $school), [
        'attendance_entry_time' => '14:00', 'attendance_exit_time' => '07:00',
        'attendance_late_grace_minutes' => 15,
    ])->assertSessionHasErrors('attendance_exit_time');
});
