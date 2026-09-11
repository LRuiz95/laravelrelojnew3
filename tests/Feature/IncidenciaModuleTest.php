<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Academia\Profesor;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Incidencia;
use App\Models\Puesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidenciaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_an_incidence_and_automatic_authorization_is_assigned(): void
    {
        $responsable = Employee::create([
            'user_id' => '1001',
            'name' => 'Responsable del área',
            'type' => 'admin',
        ]);

        $area = Area::create([
            'identificador' => 'AR-INC',
            'descripcion' => 'Área de pruebas',
            'empleado_responsable_id' => $responsable->id,
        ]);

        $puesto = Puesto::create([
            'identificador' => 'P-INC',
            'descripcion' => 'Analista',
            'area_id' => $area->id,
        ]);

        $employee = Employee::create([
            'user_id' => '2001',
            'name' => 'Empleado de prueba',
            'type' => 'biometric',
            'area_id' => $area->id,
            'puesto_id' => $puesto->id,
        ]);

        $user = User::factory()->create([
            'name' => 'Usuario prueba',
            'email' => 'prueba@example.com',
            'role' => Role::Admin,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('incidencias.store'), [
            'tipo_persona' => 'empleado',
            'empleado_id' => $employee->id,
            'asunto' => 'Justificación de ausencia',
            'tipo_justificacion' => 'Permiso',
            'fecha_justificacion' => '2026-09-10',
            'motivo' => 'Necesito justificar la ausencia por cita médica.',
        ]);

        $response->assertRedirect(route('incidencias.index'));
        $this->assertDatabaseHas('incidencias', [
            'empleado_id' => $employee->id,
            'area_id' => $area->id,
            'puesto_id' => $puesto->id,
            'responsable_area_id' => $responsable->id,
            'estado' => 'pendiente',
        ]);

        $incidencia = Incidencia::first();
        $this->assertSame('Empleado de prueba', $incidencia->empleado->name);
    }

    public function test_authorized_user_can_change_incidence_status(): void
    {
        $empleado = Employee::create([
            'user_id' => '3001',
            'name' => 'Empleado autorizado',
            'type' => 'biometric',
        ]);

        $incidencia = Incidencia::create([
            'asunto' => 'Justificación',
            'tipo_justificacion' => 'Permiso',
            'fecha_justificacion' => '2026-09-10',
            'empleado_id' => $empleado->id,
            'numero_empleado' => '3001',
            'motivo' => 'Motivo de prueba',
            'estado' => 'pendiente',
        ]);

        $user = User::factory()->create([
            'name' => 'Usuario aprobador',
            'email' => 'aprobador@example.com',
            'role' => Role::Admin,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('incidencias.estado', $incidencia), [
            'estado' => 'aprobada',
        ]);

        $response->assertRedirect(route('incidencias.index'));
        $this->assertDatabaseHas('incidencias', [
            'id' => $incidencia->id,
            'estado' => 'aprobada',
            'autorizado_por_user_id' => $user->id,
        ]);
    }
}
