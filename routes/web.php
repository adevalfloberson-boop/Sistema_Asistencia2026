<?php

use App\Http\Controllers\AdmsController;
use App\Http\Controllers\AntigravityDesignController;
use App\Http\Controllers\AttendanceExcuseController;
use App\Http\Controllers\AttendanceReportExportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricAgentController;
use App\Http\Controllers\ClassAttendanceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EarlyDepartureLinkController;
use App\Http\Controllers\InternshipScheduleController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\PublicEarlyDepartureController;
use App\Http\Controllers\SchoolMailSettingsController;
use App\Http\Controllers\SchoolMailTestController;
use App\Http\Controllers\SchoolNotificationSettingsController;
use App\Http\Controllers\SchoolScheduleExceptionController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\StudentAttendanceExceptionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\ViewerDashboardController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', fn (): RedirectResponse => to_route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/api/asistencia', [DashboardController::class, 'registrarAsistencia'])->name('api.asistencia');
Route::get('/vista-asistencia/{token}', [PublicDashboardController::class, 'show'])->middleware('throttle:120,1')->name('public.dashboard.show');
Route::get('/vista-asistencia/{token}/actividad', [PublicDashboardController::class, 'activity'])->middleware('throttle:120,1')->name('public.dashboard.activity');
Route::get('/autorizar-salidas/{token}', [PublicEarlyDepartureController::class, 'show'])->middleware('throttle:120,1')->name('public.early-departures.show');
Route::post('/autorizar-salidas/{token}', [PublicEarlyDepartureController::class, 'store'])->middleware('throttle:60,1')->name('public.early-departures.store');
Route::delete('/autorizar-salidas/{token}/{authorization}', [PublicEarlyDepartureController::class, 'destroy'])->middleware('throttle:60,1')->name('public.early-departures.destroy');

Route::prefix('iclock')
    ->name('adms.')
    ->middleware('throttle:240,1')
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ])
    ->group(function (): void {
        Route::match(['get', 'post'], '/cdata', [AdmsController::class, 'cdata'])->name('cdata');
        Route::match(['get', 'post'], '/registry', [AdmsController::class, 'registry'])->name('registry');
        Route::get('/getrequest', [AdmsController::class, 'getRequest'])->name('commands.next');
        Route::post('/devicecmd', [AdmsController::class, 'deviceCommand'])->name('commands.complete');
    });

Route::prefix('api/biometric')->name('api.biometric.')->group(function (): void {
    Route::get('/devices', [BiometricAgentController::class, 'devices'])->name('devices');
    Route::post('/devices/{key}/heartbeat', [BiometricAgentController::class, 'heartbeat'])->name('heartbeat');
    Route::get('/devices/{key}/attendance/checkpoint', [BiometricAgentController::class, 'attendanceCheckpoint'])->name('attendance.checkpoint');
    Route::get('/devices/{key}/commands/next', [BiometricAgentController::class, 'nextCommand'])->name('commands.next');
    Route::post('/commands/{command}/complete', [BiometricAgentController::class, 'completeCommand'])->name('commands.complete');
});

Route::middleware(['role:superadmin,admin'])->prefix('dashboard/admin')->group(function (): void {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard.admin');
    Route::get('/{page}', [DashboardController::class, 'admin'])
        ->whereIn('page', ['overview', 'devices', 'enrollment', 'students', 'courses', 'teachers', 'settings', 'attendance', 'reports'])
        ->name('dashboard.admin.page');
    Route::get('/reportes/exportar/excel', AttendanceReportExportController::class)->name('reports.export');

    Route::middleware('school-administration')->group(function (): void {
        Route::post('/estudiantes', [StudentController::class, 'store'])->name('students.store');
        Route::put('/estudiantes/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/estudiantes/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('/cursos', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/cursos/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/cursos/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('/docentes', [TeacherController::class, 'store'])->name('teachers.store');
        Route::put('/docentes/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/docentes/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
        Route::put('/escuelas/{school}/configuracion', SchoolSettingsController::class)->name('schools.settings.update');
        Route::post('/escuelas/{school}/horarios-especiales', [SchoolScheduleExceptionController::class, 'store'])->name('schools.schedule-exceptions.store');
        Route::put('/cursos/{course}/pasantia', [InternshipScheduleController::class, 'update'])->name('courses.internship.update');
        Route::post('/estudiantes/excepciones-asistencia', [StudentAttendanceExceptionController::class, 'store'])->name('students.attendance-exceptions.store');
        Route::put('/escuelas/{school}/notificaciones', [SchoolNotificationSettingsController::class, 'update'])->name('schools.notifications.update');
        Route::put('/escuelas/{school}/correo', [SchoolMailSettingsController::class, 'update'])->name('schools.mail.update');
        Route::post('/escuelas/{school}/correo/prueba', SchoolMailTestController::class)->middleware('throttle:3,1')->name('schools.mail.test');
        Route::post('/escuelas/{school}/enlace-publico', [PublicDashboardController::class, 'generate'])->name('schools.public-dashboard.generate');
        Route::delete('/escuelas/{school}/enlace-publico', [PublicDashboardController::class, 'revoke'])->name('schools.public-dashboard.revoke');
        Route::post('/escuelas/{school}/enlace-salidas', [EarlyDepartureLinkController::class, 'generate'])->name('schools.early-departures.generate');
        Route::delete('/escuelas/{school}/enlace-salidas', [EarlyDepartureLinkController::class, 'revoke'])->name('schools.early-departures.revoke');
        Route::post('/asistencias/{attendance}/excusa', [AttendanceExcuseController::class, 'store'])->name('attendances.excuses.store');

        Route::name('devices.')->group(function (): void {
            Route::get('/dispositivos/estado', [DeviceController::class, 'statuses'])->name('statuses');
            Route::post('/dispositivos', [DeviceController::class, 'store'])->name('store');
            Route::put('/dispositivos/{device}', [DeviceController::class, 'update'])->name('update');
            Route::delete('/dispositivos/{device}', [DeviceController::class, 'destroy'])->name('destroy');
            Route::post('/dispositivos/{device}/consultar', [DeviceController::class, 'inspect'])->name('inspect');
            Route::post('/dispositivos/{device}/sincronizar-hora', [DeviceController::class, 'synchronizeTime'])->name('sync-time');
            Route::post('/enrolamientos', [DeviceController::class, 'enroll'])->name('enroll');
            Route::post('/enrolamientos/rostro', [DeviceController::class, 'enrollFace'])->name('enroll-face');
            Route::post('/enrolamientos/{enrollment}/verificar', [DeviceController::class, 'verifyEnrollment'])->name('enrollments.verify');
            Route::get('/ordenes/{command}', [DeviceController::class, 'commandStatus'])->name('commands.show');
        });
    });
});

Route::middleware('role:superadmin')->prefix('dashboard/superadmin')->group(function (): void {
    Route::get('/', [SuperAdminController::class, 'index'])->name('dashboard.superadmin');
    Route::post('/instituciones', [SuperAdminController::class, 'storeSchool'])->name('superadmin.schools.store');
    Route::put('/instituciones/{school}', [SuperAdminController::class, 'updateSchool'])->name('superadmin.schools.update');
    Route::put('/instituciones/{school}/estado', [SuperAdminController::class, 'changeSchoolStatus'])->name('superadmin.schools.status');
    Route::post('/administradores', [SuperAdminController::class, 'storeAdministrator'])->name('superadmin.administrators.store');
    Route::put('/administradores/{administrator}/contrasena', [SuperAdminController::class, 'resetAdministratorPassword'])->name('superadmin.administrators.password');
    Route::get('/instituciones/{school}/respaldo', [SuperAdminController::class, 'downloadBackup'])->name('superadmin.schools.backup');
});

Route::middleware('role:teacher')->prefix('dashboard/docente')->group(function (): void {
    Route::get('/', TeacherDashboardController::class)->name('dashboard.docente');
    Route::post('/sesiones', [ClassAttendanceController::class, 'start'])->name('teacher.sessions.start');
    Route::post('/sesiones/{classSession}/sincronizar', [ClassAttendanceController::class, 'synchronize'])->name('teacher.sessions.synchronize');
    Route::post('/verificaciones', [ClassAttendanceController::class, 'verify'])->name('teacher.verifications.store');
    Route::post('/verificaciones/lista', [ClassAttendanceController::class, 'storeRoster'])->name('teacher.verifications.roster');
    Route::post('/sesiones/{classSession}/cerrar', [ClassAttendanceController::class, 'close'])->name('teacher.sessions.close');
});

Route::middleware('role:viewer')->get('/dashboard/visualizacion', ViewerDashboardController::class)->name('dashboard.viewer');

// ==============================================================================
// SUITE "DISEÑOS ANTIGRAVITY" - Versión comercial modular independiente
// ==============================================================================
Route::prefix('disenos-antigravity')->name('antigravity.')->group(function (): void {
    Route::get('/', [AntigravityDesignController::class, 'hub'])->name('hub');
    Route::get('/login', [AntigravityDesignController::class, 'login'])->name('login');
    Route::get('/login/rapido/{role}', [AntigravityDesignController::class, 'quickLogin'])->name('quick-login');
    Route::get('/admin', [AntigravityDesignController::class, 'admin'])->name('admin');
    Route::get('/admin/estudiantes', [AntigravityDesignController::class, 'students'])->name('admin.students');
    Route::get('/admin/enrolamiento', [AntigravityDesignController::class, 'enrollment'])->name('admin.enrollment');
    Route::get('/admin/dispositivos', [AntigravityDesignController::class, 'devices'])->name('admin.devices');
    Route::get('/docente', [AntigravityDesignController::class, 'teacher'])->name('teacher');
    Route::get('/superadmin', [AntigravityDesignController::class, 'superadmin'])->name('superadmin');
    Route::get('/kiosco', [AntigravityDesignController::class, 'kiosk'])->name('kiosk');
});
