<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\Course;
use App\Models\DeviceCommand;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolAdministration
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('user.role') === 'superadmin') {
            return $next($request);
        }

        abort_unless($request->session()->get('user.role') === 'admin', 403);
        $schoolId = (int) $request->session()->get('user.school_id');
        abort_unless($schoolId > 0, 403);

        if ($request->filled('school_id')) {
            abort_unless($request->integer('school_id') === $schoolId, 403);
        }

        foreach (['school', 'student', 'course', 'teacher', 'device', 'enrollment', 'command', 'attendance'] as $parameter) {
            $resource = $request->route($parameter);
            $resourceSchoolId = match (true) {
                $resource instanceof School => $resource->id,
                $resource instanceof Student, $resource instanceof Course, $resource instanceof User, $resource instanceof BiometricDevice => $resource->school_id,
                $resource instanceof Attendance => $resource->school_id,
                $resource instanceof BiometricEnrollment => $resource->device?->school_id,
                $resource instanceof DeviceCommand => $resource->device?->school_id,
                default => null,
            };

            if ($resourceSchoolId !== null) {
                abort_unless((int) $resourceSchoolId === $schoolId, 403);
            }
        }

        return $next($request);
    }
}
