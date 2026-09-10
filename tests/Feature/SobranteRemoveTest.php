<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Pivots\DeviceEmployee;
use App\Models\Employee;
use App\Services\SobranteService;
use App\Services\ZktecoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SobranteRemoveTest extends TestCase
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
            'name' => 'Chk Remove',
            'ip' => '192.168.1.98',
        ], $attrs));
    }

    private function createEmployee(array $attrs = []): Employee
    {
        return Employee::create(array_merge([
            'user_id' => (string) rand(1000, 9999),
            'name' => 'Emp Remove',
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

    private function mockZkteco(bool $success = true): \Mockery\MockInterface
    {
        $mock = Mockery::mock(ZktecoService::class);
        $mock->shouldReceive('removeUserFromDevice')->once()->andReturn($success);
        return $mock;
    }

    // ---------------------------------------------------------------
    // Tipo A: remove
    // ---------------------------------------------------------------

    public function test_remove_tipo_a_deletes_pivot(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'A', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('device_employee', [
            'device_id' => $device->id,
            'device_uid' => $pivot->device_uid,
        ]);
    }

    public function test_remove_tipo_a_does_not_touch_employee_table(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee();
        $pivot = $this->createOrphanPivot($device, $employee->id);
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'A', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_remove_tipo_a_returns_correct_message(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'A', $mock);

        $this->assertStringContainsString('Eliminado', $result['message']);
        $this->assertStringContainsString('catálogo local', $result['message']);
    }

    // ---------------------------------------------------------------
    // Tipo B: remove
    // ---------------------------------------------------------------

    public function test_remove_tipo_b_deletes_pivot(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'B']);
        $pivot = $this->createPivot($device, $employee);
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'B', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('device_employee', [
            'device_id' => $device->id,
            'device_uid' => $pivot->device_uid,
        ]);
    }

    public function test_remove_tipo_b_preserves_employee(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'B']);
        $pivot = $this->createPivot($device, $employee);
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'B', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_remove_tipo_b_returns_message_about_employee(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'B']);
        $pivot = $this->createPivot($device, $employee);
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'B', $mock);

        $this->assertStringContainsString('Employee se conserva', $result['message']);
    }

    public function test_remove_tipo_b_does_not_delete_employee_even_if_last_device(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'B']);
        $pivot = $this->createPivot($device, $employee);
        $mock = $this->mockZkteco(true);

        $this->assertEquals(1, $employee->devices()->count());

        $result = $this->service->remove($device->id, $pivot->device_uid, 'B', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertEquals(0, $employee->fresh()->devices()->count());
    }

    // ---------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------

    public function test_remove_returns_error_for_nonexistent_pivot(): void
    {
        $device = $this->createDevice();
        $mock = $this->mockZkteco(true);

        $result = $this->service->remove($device->id, 99999, 'A', $mock);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no encontrado', $result['message']);
    }

    public function test_remove_returns_error_for_invalid_type(): void
    {
        $device = $this->createDevice();

        $result = $this->service->remove($device->id, 1, 'X');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('inválido', $result['message']);
    }

    public function test_remove_returns_error_for_nonexistent_device(): void
    {
        $result = $this->service->remove(99999, 1, 'A');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Dispositivo no encontrado', $result['message']);
    }

    // ---------------------------------------------------------------
    // Hardware failure resilience
    // ---------------------------------------------------------------

    public function test_remove_tipo_a_succeeds_even_when_hardware_fails(): void
    {
        $device = $this->createDevice();
        $pivot = $this->createOrphanPivot($device);
        $mock = $this->mockZkteco(false);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'A', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('device_employee', [
            'device_id' => $device->id,
            'device_uid' => $pivot->device_uid,
        ]);
    }

    public function test_remove_tipo_b_succeeds_even_when_hardware_fails(): void
    {
        $device = $this->createDevice();
        $employee = $this->createEmployee(['status_actual' => 'B']);
        $pivot = $this->createPivot($device, $employee);
        $mock = $this->mockZkteco(false);

        $result = $this->service->remove($device->id, $pivot->device_uid, 'B', $mock);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('device_employee', [
            'device_id' => $device->id,
            'device_uid' => $pivot->device_uid,
        ]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
