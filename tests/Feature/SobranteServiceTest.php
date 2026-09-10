<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Pivots\DeviceEmployee;
use App\Models\Employee;
use App\Services\SobranteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SobranteServiceTest extends TestCase
{
    use RefreshDatabase;

    private SobranteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SobranteService();
    }

    private function createDevice(array $attrs = []): Device
    {
        return Device::create(array_merge([
            'name' => 'Checador Test',
            'ip' => '192.168.1.99',
        ], $attrs));
    }

    private function createEmployee(array $attrs = []): Employee
    {
        return Employee::create(array_merge([
            'user_id' => (string) rand(1000, 9999),
            'name' => 'Empleado Test',
            'status_actual' => 'A',
        ], $attrs));
    }

    private function createPivot(Device $device, Employee $employee, array $attrs = []): DeviceEmployee
    {
        return DeviceEmployee::create(array_merge([
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'device_uid' => rand(1, 100),
            'role' => 0,
            'active' => true,
        ], $attrs));
    }

    /**
     * Crea un pivot Tipo A: employee_id apunta a un ID inexistente.
     * Requiere deshabilitar FK checks temporalmente.
     */
    private function createOrphanPivot(Device $device, int $fakeEmployeeId = 99999, int $uid = 0): DeviceEmployee
    {
        $uid = $uid ?: rand(1000, 9999);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $id = DB::table('device_employee')->insertGetId([
            'device_id' => $device->id,
            'employee_id' => $fakeEmployeeId,
            'device_uid' => $uid,
            'role' => 0,
            'active' => true,
            'fingerprint_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return DeviceEmployee::find($id);
    }

    // ---------------------------------------------------------------
    // Detección de sobrantes
    // ---------------------------------------------------------------

    public function test_tipo_a_detected_when_employee_missing(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);

        $results = $this->service->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('A', $results->first()->sobrante_type);
        $this->assertEquals($device->id, $results->first()->device_id);
    }

    public function test_tipo_b_detected_when_employee_is_baja(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'B']);
        $pivot = $this->createPivot($device, $employee);

        $results = $this->service->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('B', $results->first()->sobrante_type);
        $this->assertEquals($employee->id, $results->first()->employee_id);
    }

    public function test_active_employee_is_not_sobrante(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'A']);
        $this->createPivot($device, $employee);

        $results = $this->service->query()->get();

        $this->assertCount(0, $results);
    }

    public function test_no_duplicates_when_both_types_exist(): void
    {
        $device = $this->createDevice();
        $employeeB = $this->createEmployee(['status_actual' => 'B']);
        $this->createPivot($device, $employeeB);
        $this->createOrphanPivot($device);

        $results = $this->service->query()->get();

        $this->assertCount(2, $results);
        $types = $results->pluck('sobrante_type')->toArray();
        $this->assertContains('A', $types);
        $this->assertContains('B', $types);
    }

    // ---------------------------------------------------------------
    // Filtros
    // ---------------------------------------------------------------

    public function test_filter_by_device_id(): void
    {
        $device1 = $this->createDevice(['ip' => '192.168.1.10']);
        $device2 = $this->createDevice(['ip' => '192.168.1.11']);
        $this->createOrphanPivot($device1);
        $this->createOrphanPivot($device2);

        $results = $this->service->query(false, $device1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($device1->id, $results->first()->device_id);
    }

    public function test_filter_by_type_a(): void
    {
        $device = $this->createDevice();
        $employeeB = $this->createEmployee(['status_actual' => 'B']);
        $this->createPivot($device, $employeeB);
        $this->createOrphanPivot($device);

        $results = $this->service->query(false, null, 'A')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('A', $results->first()->sobrante_type);
    }

    public function test_filter_by_type_b(): void
    {
        $device = $this->createDevice();
        $employeeB = $this->createEmployee(['status_actual' => 'B']);
        $this->createPivot($device, $employeeB);
        $this->createOrphanPivot($device);

        $results = $this->service->query(false, null, 'B')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('B', $results->first()->sobrante_type);
    }

    public function test_search_by_name_for_tipo_b(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['name' => 'Juan Perez', 'status_actual' => 'B']);
        $this->createPivot($device, $employee);

        $results = $this->service->query(false, null, null, 'Juan')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($employee->id, $results->first()->employee_id);
    }

    public function test_search_does_not_match_tipo_a(): void
    {
        $device = $this->createDevice();
        $this->createOrphanPivot($device);

        $results = $this->service->query(false, null, null, 'Juan')->get();

        $this->assertCount(0, $results);
    }

    // ---------------------------------------------------------------
    // Ignorados
    // ---------------------------------------------------------------

    public function test_ignored_excluded_by_default(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $this->service->ignore($device->id, $pivot->device_uid);

        $results = $this->service->query(false)->get();
        $this->assertCount(0, $results);
    }

    public function test_ignored_included_when_flagged(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $this->service->ignore($device->id, $pivot->device_uid);

        $results = $this->service->query(true)->get();
        $this->assertCount(1, $results);
    }

    public function test_ignore_sets_ignored_at(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);

        $result = $this->service->ignore($device->id, $pivot->device_uid);

        $this->assertTrue($result);
        $this->assertNotNull($pivot->fresh()->ignored_at);
    }

    public function test_unignore_clears_ignored_at(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $this->service->ignore($device->id, $pivot->device_uid);

        $result = $this->service->unignore($device->id, $pivot->device_uid);

        $this->assertTrue($result);
        $this->assertNull($pivot->fresh()->ignored_at);
    }

    // ---------------------------------------------------------------
    // Stats
    // ---------------------------------------------------------------

    public function test_stats_count_by_type(): void
    {
        $device = $this->createDevice();
        $employeeB = $this->createEmployee(['status_actual' => 'B']);
        $this->createPivot($device, $employeeB);
        $this->createOrphanPivot($device);
        $this->createOrphanPivot($device);

        $stats = $this->service->getStats();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['type_a']);
        $this->assertEquals(1, $stats['type_b']);
    }

    public function test_stats_exclude_ignored(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $this->service->ignore($device->id, $pivot->device_uid);

        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(1, $stats['ignored']);
    }

    public function test_stats_filter_by_device(): void
    {
        $device1 = $this->createDevice(['ip' => '192.168.1.10']);
        $device2 = $this->createDevice(['ip' => '192.168.1.11']);
        $this->createOrphanPivot($device1);
        $this->createOrphanPivot($device2);

        $stats = $this->service->getStats($device1->id);

        $this->assertEquals(1, $stats['total']);
    }

    // ---------------------------------------------------------------
    // Paginación
    // ---------------------------------------------------------------

    public function test_paginate_works(): void
    {
        $device = $this->createDevice();
        for ($i = 0; $i < 30; $i++) {
            $this->createOrphanPivot($device, 99999, 5000 + $i);
        }

        $page = $this->service->query()->paginate(10);

        $this->assertEquals(10, $page->count());
        $this->assertEquals(30, $page->total());
        $this->assertEquals(3, $page->lastPage());
    }

    // ---------------------------------------------------------------
    // find
    // ---------------------------------------------------------------

    public function test_find_by_id_returns_sobrante(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);

        $found = $this->service->findById($pivot->id);

        $this->assertNotNull($found);
        $this->assertEquals($pivot->id, $found->pivot_id);
        $this->assertEquals('A', $found->sobrante_type);
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $found = $this->service->findById(99999);

        $this->assertNull($found);
    }
}
