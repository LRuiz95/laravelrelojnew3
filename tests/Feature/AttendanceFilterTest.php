<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión: AttendanceController::index() ignoraba por completo los
 * parámetros del formulario (nunca llamaba applyFilters) y, al cablearlo,
 * filtraba por la columna "state" — constante en este firmware — en vez de
 * "type", el modo real de checado (ver Attendance::punchStatus()).
 */
class AttendanceFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Device $deviceA;

    private Device $deviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->deviceA = Device::create(['name' => 'Checador A', 'ip' => '192.168.1.10']);
        $this->deviceB = Device::create(['name' => 'Checador B', 'ip' => '192.168.1.11']);

        $ana = Employee::create(['user_id' => '201', 'name' => 'Ana Entrada']);
        $beto = Employee::create(['user_id' => '202', 'name' => 'Beto Salida']);

        // Ana: entrada hoy (A) y salida hoy (B). Beto: salida T.E. ayer (B).
        Attendance::create([
            'device_id' => $this->deviceA->id,
            'employee_id' => $ana->id,
            'user_id' => '201',
            'state' => 1,
            'type' => 0,
            'recorded_at' => today()->setTime(8, 0),
        ]);
        Attendance::create([
            'device_id' => $this->deviceB->id,
            'employee_id' => $ana->id,
            'user_id' => '201',
            'state' => 1,
            'type' => 1,
            'recorded_at' => today()->setTime(13, 5),
        ]);
        Attendance::create([
            'device_id' => $this->deviceB->id,
            'employee_id' => $beto->id,
            'user_id' => '202',
            'state' => 1,
            'type' => 5,
            'recorded_at' => today()->subDay()->setTime(17, 30),
        ]);
    }

    /**
     * assertDontSee no sirve aquí: el composer del layout inyecta TODOS los
     * empleados en window.__dash (búsqueda global), así que el nombre siempre
     * está en el HTML. Se afirma sobre las filas reales del viewData.
     */
    private function filteredNames(string $url): array
    {
        $response = $this->actingAs($this->user)->get($url);
        $response->assertOk();

        return $response->viewData('attendances')
            ->map(fn ($attendance) => $attendance->employee?->name)
            ->all();
    }

    public function test_state_filter_matches_punch_type(): void
    {
        // type=0 → Entrada: solo Ana; el filtro NO debe usar la columna state (siempre 1).
        $this->assertSame(['Ana Entrada'], $this->filteredNames('/attendances?type=0'));

        // type=5 → Salida T.E.: solo Beto (de ayer).
        $this->assertSame(['Beto Salida'], $this->filteredNames('/attendances?type=5'));
    }

    public function test_device_filter(): void
    {
        $this->assertSame(['Ana Entrada'], $this->filteredNames('/attendances?device_id='.$this->deviceA->id));

        // En B hay dos registros: salida hoy de Ana y salida T.E. ayer de Beto.
        $names = $this->filteredNames('/attendances?device_id='.$this->deviceB->id);
        sort($names);
        $this->assertSame(['Ana Entrada', 'Beto Salida'], $names);
    }

    public function test_date_range_filter(): void
    {
        $hoy = today()->toDateString();

        // La vista agrupa por empleado + fecha; por eso, con el rango del día
        // de hoy solo existe una fila para Ana y Beto queda fuera.
        $this->assertSame(['Ana Entrada'], $this->filteredNames("/attendances?from={$hoy}&to={$hoy}"));
    }

    public function test_combined_filters(): void
    {
        $hoy = today()->toDateString();

        $this->assertSame(['Ana Entrada'], $this->filteredNames("/attendances?type=1&from={$hoy}&to={$hoy}"));
    }

    /** Sin filtros se usa la vista única por empleado+fecha: ambos aparecen */
    public function test_no_filters_shows_unique_list(): void
    {
        $response = $this->actingAs($this->user)->get('/attendances');
        $response->assertOk();
        $response->assertSee('Ana Entrada');
        $response->assertSee('Beto Salida');
    }
}
