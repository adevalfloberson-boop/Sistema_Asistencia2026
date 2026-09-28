<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\SystemAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123');

        // 1. Institución Principal (SaaS Tenant A)
        $schoolA = School::query()->updateOrCreate(
            ['code' => 'SANPATRICIO'],
            [
                'name' => 'Colegio Bilingüe San Patricio',
                'short_name' => 'San Patricio',
                'tax_id' => '130-98421-1',
                'email' => 'direccion@sanpatricio.edu.do',
                'phone' => '+1 (809) 555-0199',
                'address' => 'Av. Enriquillo #45, Los Cacicazgos, Santo Domingo',
                'logo_path' => null,
                'is_active' => true,
                'active_modules' => ['attendance', 'biometric', 'classroom_verification', 'reports'],
                'attendance_entry_time' => '07:30',
                'attendance_exit_time' => '13:30',
                'attendance_late_grace_minutes' => 15,
                'attendance_cooldown_minutes' => 10,
            ]
        );

        // Institución Secundaria (SaaS Tenant B para mostrar Multi-tenant)
        $schoolB = School::query()->updateOrCreate(
            ['code' => 'POLI-METRO'],
            [
                'name' => 'Politécnico Metropolitano del Caribe',
                'short_name' => 'Poli Metro',
                'tax_id' => '101-55230-8',
                'email' => 'administracion@polimetro.edu.do',
                'phone' => '+1 (809) 555-0844',
                'address' => 'Av. Independencia Km 9.5',
                'logo_path' => null,
                'is_active' => true,
                'active_modules' => ['attendance', 'biometric'],
                'attendance_entry_time' => '07:45',
                'attendance_exit_time' => '14:00',
                'attendance_late_grace_minutes' => 10,
                'attendance_cooldown_minutes' => 15,
            ]
        );

        // 2. Usuarios de Demostración para cada Rol
        $superAdmin = User::query()->updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Ing. Roberto Méndez',
                'email' => 'superadmin@antigravity-saas.com',
                'password' => $password,
                'role' => 'superadmin',
                'school_id' => null,
                'is_active' => true,
            ]
        );

        $director = User::query()->updateOrCreate(
            ['username' => 'director'],
            [
                'name' => 'Dra. Patricia Morales',
                'email' => 'directora@sanpatricio.edu.do',
                'password' => $password,
                'role' => 'admin',
                'school_id' => $schoolA->id,
                'is_active' => true,
            ]
        );

        $docente = User::query()->updateOrCreate(
            ['username' => 'docente01'],
            [
                'name' => 'Prof. Carlos Ramírez',
                'email' => 'carlos.ramirez@sanpatricio.edu.do',
                'password' => $password,
                'role' => 'teacher',
                'school_id' => $schoolA->id,
                'is_active' => true,
            ]
        );

        $docente2 = User::query()->updateOrCreate(
            ['username' => 'docente02'],
            [
                'name' => 'Lic. Elena Valenzuela',
                'email' => 'elena.valenzuela@sanpatricio.edu.do',
                'password' => $password,
                'role' => 'teacher',
                'school_id' => $schoolA->id,
                'is_active' => true,
            ]
        );

        $visualizador = User::query()->updateOrCreate(
            ['username' => 'recepcion'],
            [
                'name' => 'Recepción y Seguridad',
                'email' => 'recepcion@sanpatricio.edu.do',
                'password' => $password,
                'role' => 'viewer',
                'school_id' => $schoolA->id,
                'is_active' => true,
            ]
        );

        // 3. Cursos y Áreas Académicas
        $cursosData = [
            ['code' => '1SEC-A', 'name' => '1.º Secundaria A', 'grade' => 'Primero', 'area' => 'Secundaria General', 'section' => 'A', 'shift' => 'Matutina'],
            ['code' => '2SEC-A', 'name' => '2.º Secundaria A', 'grade' => 'Segundo', 'area' => 'Secundaria General', 'section' => 'A', 'shift' => 'Matutina'],
            ['code' => '3SEC-B', 'name' => '3.º Secundaria B', 'grade' => 'Tercero', 'area' => 'Secundaria General', 'section' => 'B', 'shift' => 'Matutina'],
            ['code' => '4BAC-A', 'name' => '4.º Bachillerato Ciencias', 'grade' => 'Cuarto', 'area' => 'Bachillerato Técnico', 'section' => 'A', 'shift' => 'Matutina'],
        ];

        $cursos = collect();
        foreach ($cursosData as $cData) {
            $cursos->push(Course::query()->updateOrCreate(
                ['school_id' => $schoolA->id, 'code' => $cData['code']],
                array_merge($cData, ['is_active' => true])
            ));
        }

        // Asignar cursos a profesores
        $docente->courses()->sync([$cursos[0]->id, $cursos[1]->id]);
        $docente2->courses()->sync([$cursos[2]->id, $cursos[3]->id]);

        // 4. Lectores Biométricos ADMS
        $dev1 = BiometricDevice::query()->updateOrCreate(
            ['key' => 'DEV-MAIN-01'],
            [
                'school_id' => $schoolA->id,
                'name' => 'Torniquetes Entrada Principal',
                'model' => 'ZKTeco SpeedFace-V5L',
                'serial_number' => 'ZK-SPF5-99812A',
                'ip_address' => '192.168.10.150',
                'mac_address' => '00:1a:79:b8:31:01',
                'status' => 'connected',
                'connection_mode' => 'adms',
                'location' => 'Lobby Exterior',
                'firmware_version' => 'Ver 2.4.1 Cloud',
                'last_seen_at' => $now->copy()->subSeconds(30),
                'last_connected_at' => $now->copy()->subHours(8),
                'is_active' => true,
            ]
        );

        $dev2 = BiometricDevice::query()->updateOrCreate(
            ['key' => 'DEV-PABB-02'],
            [
                'school_id' => $schoolA->id,
                'name' => 'Lector Pabellón B - Secundaria',
                'model' => 'ZKTeco ProCapture-T',
                'serial_number' => 'ZK-PCAP-44120B',
                'ip_address' => '192.168.10.152',
                'mac_address' => '00:1a:79:b8:31:02',
                'status' => 'connected',
                'connection_mode' => 'adms',
                'location' => 'Pasillo Nivel 2',
                'firmware_version' => 'Ver 2.4.1 Cloud',
                'last_seen_at' => $now->copy()->subMinutes(3),
                'last_connected_at' => $now->copy()->subHours(8),
                'is_active' => true,
            ]
        );

        $dev3 = BiometricDevice::query()->updateOrCreate(
            ['key' => 'DEV-GYM-03'],
            [
                'school_id' => $schoolA->id,
                'name' => 'Puerta Canchas y Gimnasio',
                'model' => 'ZKTeco Horus E1-RFID',
                'serial_number' => 'ZK-HORUS-77319C',
                'ip_address' => '192.168.10.155',
                'mac_address' => '00:1a:79:b8:31:03',
                'status' => 'delayed',
                'connection_mode' => 'adms',
                'location' => 'Acceso Deportivo',
                'firmware_version' => 'Ver 2.3.0 Cloud',
                'last_seen_at' => $now->copy()->subMinutes(24),
                'last_connected_at' => $now->copy()->subHours(4),
                'is_active' => true,
            ]
        );

        // 5. Alumnos Realistas
        $estudiantesData = [
            // 1.º Secundaria A
            ['nombre' => 'Mateo', 'apellido' => 'Peña Almonte', 'matricula' => 'MAT-2026-001', 'numero_lista' => 1, 'id_lector' => '1001', 'curso_idx' => 0],
            ['nombre' => 'Sofía', 'apellido' => 'Castillo Gómez', 'matricula' => 'MAT-2026-002', 'numero_lista' => 2, 'id_lector' => '1002', 'curso_idx' => 0],
            ['nombre' => 'Lucas', 'apellido' => 'Morales Reyes', 'matricula' => 'MAT-2026-003', 'numero_lista' => 3, 'id_lector' => '1003', 'curso_idx' => 0],
            ['nombre' => 'Valentina', 'apellido' => 'Santana Díaz', 'matricula' => 'MAT-2026-004', 'numero_lista' => 4, 'id_lector' => '1004', 'curso_idx' => 0],
            ['nombre' => 'Diego', 'apellido' => 'Rodríguez Cruz', 'matricula' => 'MAT-2026-005', 'numero_lista' => 5, 'id_lector' => '1005', 'curso_idx' => 0],
            ['nombre' => 'Camila', 'apellido' => 'Fernández Soto', 'matricula' => 'MAT-2026-006', 'numero_lista' => 6, 'id_lector' => '1006', 'curso_idx' => 0],
            ['nombre' => 'Sebastián', 'apellido' => 'Mejía Rosario', 'matricula' => 'MAT-2026-007', 'numero_lista' => 7, 'id_lector' => '1007', 'curso_idx' => 0],
            ['nombre' => 'Isabella', 'apellido' => 'Vargas Peguero', 'matricula' => 'MAT-2026-008', 'numero_lista' => 8, 'id_lector' => '1008', 'curso_idx' => 0],
            ['nombre' => 'Gabriel', 'apellido' => 'Núñez Tavárez', 'matricula' => 'MAT-2026-009', 'numero_lista' => 9, 'id_lector' => '1009', 'curso_idx' => 0],
            ['nombre' => 'Emma', 'apellido' => 'Guzmán Paulino', 'matricula' => 'MAT-2026-010', 'numero_lista' => 10, 'id_lector' => '1010', 'curso_idx' => 0],

            // 2.º Secundaria A
            ['nombre' => 'Alejandro', 'apellido' => 'Pérez Santos', 'matricula' => 'MAT-2026-011', 'numero_lista' => 1, 'id_lector' => '1011', 'curso_idx' => 1],
            ['nombre' => 'Mia', 'apellido' => 'Polanco Abreu', 'matricula' => 'MAT-2026-012', 'numero_lista' => 2, 'id_lector' => '1012', 'curso_idx' => 1],
            ['nombre' => 'Daniel', 'apellido' => 'Cabrera Henríquez', 'matricula' => 'MAT-2026-013', 'numero_lista' => 3, 'id_lector' => '1013', 'curso_idx' => 1],
            ['nombre' => 'Lucía', 'apellido' => 'Jiménez Acosta', 'matricula' => 'MAT-2026-014', 'numero_lista' => 4, 'id_lector' => '1014', 'curso_idx' => 1],
            ['nombre' => 'Nicolás', 'apellido' => 'Báez De la Rosa', 'matricula' => 'MAT-2026-015', 'numero_lista' => 5, 'id_lector' => '1015', 'curso_idx' => 1],

            // 3.º Secundaria B
            ['nombre' => 'Emilio', 'apellido' => 'Tejeda Marte', 'matricula' => 'MAT-2026-021', 'numero_lista' => 1, 'id_lector' => '1021', 'curso_idx' => 2],
            ['nombre' => 'Victoria', 'apellido' => 'López Ventura', 'matricula' => 'MAT-2026-022', 'numero_lista' => 2, 'id_lector' => '1022', 'curso_idx' => 2],
            ['nombre' => 'Adrián', 'apellido' => 'Peralta Gil', 'matricula' => 'MAT-2026-023', 'numero_lista' => 3, 'id_lector' => '1023', 'curso_idx' => 2],
            ['nombre' => 'Sara', 'apellido' => 'Batista Collado', 'matricula' => 'MAT-2026-024', 'numero_lista' => 4, 'id_lector' => '1024', 'curso_idx' => 2],

            // 4.º Bachillerato Ciencias
            ['nombre' => 'Samuel', 'apellido' => 'Hidalgo Mena', 'matricula' => 'MAT-2026-031', 'numero_lista' => 1, 'id_lector' => '1031', 'curso_idx' => 3],
            ['nombre' => 'Paula', 'apellido' => 'Ortiz Silverio', 'matricula' => 'MAT-2026-032', 'numero_lista' => 2, 'id_lector' => '1032', 'curso_idx' => 3],
            ['nombre' => 'Marcos', 'apellido' => 'Duran Pichardo', 'matricula' => 'MAT-2026-033', 'numero_lista' => 3, 'id_lector' => '1033', 'curso_idx' => 3],
            ['nombre' => 'Natalia', 'apellido' => 'Paz Céspedes', 'matricula' => 'MAT-2026-034', 'numero_lista' => 4, 'id_lector' => '1034', 'curso_idx' => 3],
        ];

        $students = collect();
        foreach ($estudiantesData as $sData) {
            $curso = $cursos[$sData['curso_idx']];
            $student = Student::query()->updateOrCreate(
                ['school_id' => $schoolA->id, 'id_lector' => $sData['id_lector']],
                [
                    'course_id' => $curso->id,
                    'nombre' => $sData['nombre'],
                    'apellido' => $sData['apellido'],
                    'matricula' => $sData['matricula'],
                    'numero_lista' => $sData['numero_lista'],
                    'curso' => $curso->name,
                    'area' => $curso->area,
                    'seccion' => $curso->section,
                    'is_active' => true,
                ]
            );

            // Registrar huella digital biométrica simulada
            BiometricEnrollment::query()->updateOrCreate(
                ['student_id' => $student->id, 'finger_index' => 6],
                [
                    'user_id' => $student->id_lector,
                    'biometric_device_id' => $dev1->id,
                    'status' => 'verified',
                    'enrolled_at' => $now->copy()->subDays(15),
                ]
            );

            $students->push($student);
        }

        // 6. Asistencias y Ponches del Día de Hoy
        $today = Carbon::today();
        foreach ($students as $index => $student) {
            if ($index % 6 !== 0) {
                $minute = 32 + ($index * 2) % 25;
                $punchTime = $today->copy()->setTime(7, $minute, rand(10, 58));

                Attendance::query()->updateOrCreate(
                    [
                        'school_id' => $schoolA->id,
                        'student_id' => $student->id,
                        'fecha_hora' => $punchTime,
                    ],
                    [
                        'biometric_device_id' => ($index % 2 === 0) ? $dev1->id : $dev2->id,
                        'id_lector' => $student->id_lector,
                        'matricula' => $student->matricula,
                        'curso' => $student->curso,
                        'tipo' => 'Entrada',
                        'estado' => 'A tiempo',
                        'sync_source' => 'adms_live',
                        'is_ignored' => false,
                    ]
                );
            }
        }

        // 7. Sesión de Aula para Docente
        $cursoDocente = $cursos[0];
        $session = ClassSession::query()->updateOrCreate(
            [
                'course_id' => $cursoDocente->id,
                'teacher_id' => $docente->id,
                'scheduled_at' => $today->copy()->setTime(8, 0, 0),
            ],
            [
                'subject' => 'Matemáticas y Razonamiento Lógico',
                'status' => 'open',
            ]
        );

        $firstStudents = $students->where('course_id', $cursoDocente->id)->take(8);
        foreach ($firstStudents as $idx => $st) {
            $status = ($idx === 0) ? ClassAttendanceVerification::StatusLate : (($idx === 7) ? ClassAttendanceVerification::StatusCampusAbsentClass : ClassAttendanceVerification::StatusPresent);
            ClassAttendanceVerification::query()->updateOrCreate(
                [
                    'class_session_id' => $session->id,
                    'student_id' => $st->id,
                ],
                [
                    'teacher_id' => $docente->id,
                    'status' => $status,
                    'was_on_campus' => true,
                    'note' => $status === ClassAttendanceVerification::StatusLate ? 'Llegó 10 min tarde por transporte escolar' : null,
                    'verified_at' => $now->copy()->subMinutes(20),
                ]
            );
        }

        // 8. Registro de Auditoría
        SystemAuditLog::query()->create([
            'school_id' => $schoolA->id,
            'actor_id' => $director->id,
            'event' => 'school.session_opened',
            'description' => 'Apertura de jornada escolar matutina. Sincronización con 3 lectores biométricos completada.',
            'ip_address' => '192.168.10.50',
            'created_at' => $now->copy()->subHours(2),
        ]);
    }
}
