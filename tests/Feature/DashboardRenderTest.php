<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Pivots\DeviceEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regresión: con la BD vacía los eager loads nunca se resuelven (se
     * cargan por fila al hidratar) y las vistas "pasan" sin ejecutar los
     * closures que consumen relaciones — así se colaron bugs legados como
     * Employee::with('device') hasta producción. Sembrar filas es obligatorio.
     */
    private function seedRenderData(): void
    {
        $device = Device::create(['name' => 'Entrada principal', 'ip' => '192.168.1.10']);

        $enrolled = Employee::create(['user_id' => '101', 'name' => 'Empleado enrolado']);
        Employee::create(['user_id' => '102', 'name' => 'Empleado sin checador']);

        $enrolled->devices()->attach($device->id, [
            'device_uid' => 1,
            'role' => array_key_first(DeviceEmployee::ROLES),
            'active' => true,
        ]);

        Attendance::create([
            'device_id' => $device->id,
            'employee_id' => $enrolled->id,
            'user_id' => '101',
            'state' => 0,
            'recorded_at' => now(),
        ]);
    }

    public function test_pages_render_for_authenticated_user(): void
    {
        $this->seedRenderData();

        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();
        $response->assertSee('Panel de control');
        // El composer de búsqueda global procesó ambas filas del catálogo.
        $response->assertSee('Empleado enrolado');
        $response->assertSee('Empleado sin checador');

        $this->actingAs($user)->get('/devices')->assertOk();
        $this->actingAs($user)->get('/employees')->assertOk();
        $this->actingAs($user)->get('/attendances')->assertOk();

        $response = $this->actingAs($user)->get('/attendances');
        $response->assertSee('Asistencias');
    }

    /** El selector de rango de la gráfica de tendencia debe resolver los 4 rangos */
    public function test_dashboard_trend_ranges_render(): void
    {
        $this->seedRenderData();

        $user = User::factory()->create(['role' => 'admin']);

        foreach (['hoy', '7d', '30d', '12m'] as $rango) {
            $response = $this->actingAs($user)->get('/?rango='.$rango);
            $response->assertOk();
        }

        // Rango por defecto cuando el parámetro es inválido
        $this->actingAs($user)->get('/?rango=basura')->assertOk();
    }

    /**
     * El endpoint que consume el Heartbeat para refrescar las tarjetas KPI
     * en vivo. Las etiquetas se fijan aquí a propósito: el JS empareja
     * tarjeta↔dato por texto de etiqueta, no por posición.
     */
    public function test_kpis_json_endpoint_returns_five_cards(): void
    {
        $this->seedRenderData();

        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->getJson('/kpis/json');

        $response->assertOk()
            ->assertJsonStructure(['kpis' => [['value', 'label', 'trend']]]);

        $kpis = $response->json('kpis');
        $this->assertCount(5, $kpis);
        $this->assertSame(
            ['Chequeos de hoy', 'Empleados que checaron hoy', 'Entradas hoy', 'Salidas hoy', 'Checadores en línea'],
            array_column($kpis, 'label')
        );
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_from_login_redirects_to_root(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/login')->assertRedirect('/');
    }
}
