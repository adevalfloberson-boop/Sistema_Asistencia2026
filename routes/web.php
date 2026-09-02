<?php

use App\Http\Controllers\AdmsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricAgentController;
use App\Http\Controllers\ClassAttendanceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/api/asistencia', [DashboardController::class, 'registrarAsistencia'])->name('api.asistencia');

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

Route::middleware('role:superadmin')->prefix('dashboard/admin')->group(function (): void {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard.admin');

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

    Route::name('devices.')->group(function (): void {
        Route::post('/dispositivos', [DeviceController::class, 'store'])->name('store');
        Route::put('/dispositivos/{device}', [DeviceController::class, 'update'])->name('update');
        Route::post('/dispositivos/{device}/consultar', [DeviceController::class, 'inspect'])->name('inspect');
        Route::post('/dispositivos/{device}/sincronizar-hora', [DeviceController::class, 'synchronizeTime'])->name('sync-time');
        Route::post('/enrolamientos', [DeviceController::class, 'enroll'])->name('enroll');
        Route::post('/enrolamientos/{enrollment}/verificar', [DeviceController::class, 'verifyEnrollment'])->name('enrollments.verify');
        Route::get('/ordenes/{command}', [DeviceController::class, 'commandStatus'])->name('commands.show');
    });
});

Route::middleware('role:teacher')->prefix('dashboard/docente')->group(function (): void {
    Route::get('/', TeacherDashboardController::class)->name('dashboard.docente');
    Route::post('/sesiones', [ClassAttendanceController::class, 'start'])->name('teacher.sessions.start');
    Route::post('/verificaciones', [ClassAttendanceController::class, 'verify'])->name('teacher.verifications.store');
    Route::post('/sesiones/{classSession}/cerrar', [ClassAttendanceController::class, 'close'])->name('teacher.sessions.close');
});
