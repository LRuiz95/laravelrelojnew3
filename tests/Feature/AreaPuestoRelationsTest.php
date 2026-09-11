<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Academia\Profesor;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Puesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaPuestoRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_and_puesto_modules_are_available_in_the_ui(): void
    {
        $user = User::factory()->create();
        $area = Area::create([
            'identificador' => 'AR-01',
            'descripcion' => 'Recursos Humanos',
        ]);
        $puesto = Puesto::create([
            'identificador' => 'P-01',
            'descripcion' => 'Analista',
            'area_id' => $area->id,
        ]);

        $this->actingAs($user);

        $this->get(route('areas.index'))
            ->assertOk()
            ->assertSee('Áreas')
            ->assertSee($area->identificador);

        $this->get(route('puestos.index'))
            ->assertOk()
            ->assertSee('Puestos')
            ->assertSee($puesto->identificador);
    }

    public function test_employee_and_professor_can_be_linked_to_area_puesto_and_director(): void
    {
        $director = Employee::create([
            'user_id' => '1001',
            'name' => 'Director General',
            'type' => 'admin',
        ]);

        $area = Area::create([
            'identificador' => 'AR-01',
            'descripcion' => 'Recursos Humanos',
            'empleado_responsable_id' => $director->id,
        ]);

        $puesto = Puesto::create([
            'identificador' => 'P-01',
            'descripcion' => 'Analista',
            'area_id' => $area->id,
        ]);

        $employee = Employee::create([
            'user_id' => '2001',
            'name' => 'Empleado prueba',
            'type' => 'admin',
            'area_id' => $area->id,
            'puesto_id' => $puesto->id,
        ]);

        $profesor = Profesor::create([
            'clave_profesor' => 'PROF-01',
            'nombre_profesor' => 'María',
            'paterno' => 'López',
            'materno' => 'García',
            'area_id' => $area->id,
            'puesto_id' => $puesto->id,
            'director_id' => $director->id,
        ]);

        $this->assertSame('AR-01', $employee->area->identificador);
        $this->assertSame('P-01', $employee->puesto->identificador);
        $this->assertSame('AR-01', $profesor->area->identificador);
        $this->assertSame('P-01', $profesor->puesto->identificador);
        $this->assertSame('Director General', $profesor->director->name);
        $this->assertSame('Director General', $area->empleadoResponsable->name);
        $this->assertSame('Recursos Humanos', $puesto->area->descripcion);
    }
}
