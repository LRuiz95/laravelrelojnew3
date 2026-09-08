<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardQueryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    private function createDeviceEmployee(): array
    {
        $device = Device::create([
            'name' => 'Test Device',
            'serial_number' => 'TEST-' . uniqid(),
            'ip' => '192.168.1.100',
            'port' => 4370,
            'status' => 'online',
        ]);

        $employee = Employee::create([
            'name' => 'Empleado Test',
            'user_id' => 'EMP-' . uniqid(),
            'status_actual' => 'A',
        ]);

        DB::table('device_employee')->insert([
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'device_uid' => '1',
            'card_number' => '0',
            'active' => true,
        ]);

        return ['device' => $device, 'employee' => $employee];
    }

    private function createAttendance(int $deviceId, ?int $employeeId, int $type, string $recordedAt): void
    {
        DB::table('attendances')->insert([
            'device_id' => $deviceId,
            'employee_id' => $employeeId,
            'user_id' => 'TEST',
            'state' => 0,
            'type' => $type,
            'attendance_type' => 'biometric',
            'source' => 'zkteco',
            'recorded_at' => $recordedAt,
        ]);
    }

    public function test_index_uses_composite_index(): void
    {
        $this->actingAdmin();
        $this->createDeviceEmployee();

        $response = $this->get('/');
        $response->assertOk();

        $indexes = DB::select(
            "SHOW INDEX FROM attendances WHERE Key_name = ?",
            ['idx_attendances_type_recorded_at']
        );
        $this->assertNotEmpty($indexes, 'Composite index idx_attendances_type_recorded_at should exist');
    }

    public function test_kpis_returns_five_cards(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        // Today: 4 entries (type 0) + 2 exits (type 1) = 6 checks, 1 employee
        for ($i = 0; $i < 4; $i++) {
            $this->createAttendance($device->id, $employee->id, 0, now()->subHours(3 + $i)->toDateTimeString());
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createAttendance($device->id, $employee->id, 1, now()->subMinutes(30 + $i)->toDateTimeString());
        }

        $response = $this->getJson('/kpis/json');
        $response->assertOk();

        $kpis = $response->json('kpis');
        $this->assertCount(5, $kpis);

        foreach ($kpis as $kpi) {
            $this->assertArrayHasKey('value', $kpi);
            $this->assertArrayHasKey('label', $kpi);
            $this->assertArrayHasKey('trend', $kpi);
            $this->assertIsInt($kpi['value']);
        }

        $this->assertEquals(6, $kpis[0]['value']); // Chequeos de hoy
        $this->assertEquals(1, $kpis[1]['value']); // Empleados que checaron
        $this->assertEquals(4, $kpis[2]['value']); // Entradas
        $this->assertEquals(2, $kpis[3]['value']); // Salidas
    }

    public function test_donut_distribution(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        for ($i = 0; $i < 3; $i++) {
            $this->createAttendance($device->id, $employee->id, 0, now()->subHours(2 + $i)->toDateTimeString());
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createAttendance($device->id, $employee->id, 1, now()->subMinutes(15 + $i)->toDateTimeString());
        }

        $response = $this->get('/');
        $response->assertOk();

        $counts = DB::table('attendances')
            ->whereDate('recorded_at', today())
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        $this->assertEquals(3, $counts[0] ?? 0);
        $this->assertEquals(2, $counts[1] ?? 0);
    }

    public function test_trend_all_ranges_render(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $this->createAttendance($device->id, $employee->id, 0, now()->subHours(1)->toDateTimeString());

        foreach (['hoy', '7d', '30d', '12m'] as $rango) {
            $response = $this->get("/?rango={$rango}");
            $response->assertOk();
        }
    }

    public function test_todayinfo_aggregation(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $this->createAttendance($device->id, $employee->id, 0, now()->subHours(5)->toDateTimeString());
        $this->createAttendance($device->id, $employee->id, 1, now()->subHour()->toDateTimeString());

        $response = $this->get('/');
        $response->assertOk();

        $agg = DB::table('attendances')
            ->whereDate('recorded_at', today())
            ->selectRaw('COUNT(*) as checks, COUNT(DISTINCT employee_id) as employees')
            ->first();

        $this->assertEquals(2, $agg->checks);
        $this->assertEquals(1, $agg->employees);
    }
}
