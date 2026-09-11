<?php

namespace Tests\Feature;

use App\Models\Academia\DocenteAsistencia;
use App\Models\Academia\Profesor;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PuntualidadModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_and_professor_punctuality_module_aggregates_statuses(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $device = Device::create(['name' => 'Checador Test', 'ip' => '192.168.1.50']);

        $employee = Employee::create([
            'user_id' => '201',
            'name' => 'Ana García',
            'type' => 'biometric',
            'numero_empleado' => 'E-201',
        ]);

        Attendance::create([
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'user_id' => '201',
            'state' => 1,
            'type' => 0,
            'recorded_at' => today()->setTime(8, 05),
        ]);

        Attendance::create([
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'user_id' => '201',
            'state' => 1,
            'type' => 1,
            'recorded_at' => today()->setTime(17, 45),
        ]);

        Incidencia::create([
            'asunto' => 'Justificación',
            'tipo_justificacion' => 'Permiso',
            'fecha_justificacion' => today(),
            'empleado_id' => $employee->id,
            'numero_empleado' => 'E-201',
            'motivo' => 'Revisión médica',
            'estado' => 'aprobada',
            'created_by_user_id' => $user->id,
        ]);

        $profesor = Profesor::create([
            'clave_profesor' => 'P-100',
            'nombre_profesor' => 'Luis',
            'paterno' => 'Pérez',
            'materno' => 'López',
            'status_actual' => 'A',
        ]);

        DocenteAsistencia::create([
            'inicial' => 2026,
            'final' => 2026,
            'periodo' => 1,
            'codigo_grupo' => 'G-01',
            'clave_profesor' => 'P-100',
            'clave_asignatura' => 'MAT-01',
            'dia' => 1,
            'sesion' => 1,
            'fecha' => today(),
            'estado' => 'RETARDO',
            'observaciones' => 'Llegó tarde',
        ]);

        Incidencia::create([
            'asunto' => 'Justificación docente',
            'tipo_justificacion' => 'Permiso',
            'fecha_justificacion' => today(),
            'profesor_clave' => 'P-100',
            'numero_empleado' => 'P-100',
            'motivo' => 'Médico',
            'estado' => 'pendiente',
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/puntualidad?from='.today()->toDateString().'&to='.today()->toDateString().'&gracia_minutos=10&hora_entrada_base=08:00&hora_salida_base=17:00');

        $response->assertOk();
        $response->assertSee('Puntualidad');

        $employeeRows = $response->viewData('employeeRows');
        $this->assertNotEmpty($employeeRows);
        $this->assertSame('atraso', $employeeRows->first()['llegada_estado']);
        $this->assertSame('autorizada', $employeeRows->first()['incidencia_estado']);

        $professorRows = $response->viewData('professorRows');
        $this->assertNotEmpty($professorRows);
        $this->assertSame('retardo', $professorRows->first()['estado']);
        $this->assertSame('pendiente', $professorRows->first()['incidencia_estado']);
    }
}
