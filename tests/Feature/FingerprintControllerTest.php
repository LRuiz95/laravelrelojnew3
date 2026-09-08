<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FingerprintControllerTest extends TestCase
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

    public function test_assign_fingerprint(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.sync-fingerprints', $device),
            ['employee_id' => $employee->id, 'device_id' => $device->id]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'assigned']);
    }

    public function test_copy_fingerprint(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.sync-fingerprints', $device),
            ['employee_id' => $employee->id, 'device_id' => $device->id, 'target_device_id' => Device::create([
                'name' => 'Target Device',
                'serial_number' => 'TARGET-' . uniqid(),
                'ip' => '192.168.1.101',
                'port' => 4370,
                'status' => 'online',
            ])->id]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'copied']);
    }

    public function test_delete_fingerprint(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.sync-fingerprints', $device),
            ['employee_id' => $employee->id, 'action' => 'delete', 'device_id' => $device->id, 'fingerprint_id' => 999]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'deleted']);
    }

    public function test_upload_fingerprints(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.employees.upload-fingerprints', [$device, $employee]),
            ['device_id' => $device->id]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'uploaded']);
    }

    public function test_upload_fingerprints_on_device(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.employees.upload-fingerprints', [$device, $employee])
        );

        $response->assertOk();
        $response->assertJson(['status' => 'uploaded']);
    }

    public function test_remove_from_device(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->delete(
            route('devices.employees.remove', [$device, $employee])
        );

        $response->assertOk();
        $response->assertJson(['status' => 'removed']);
    }
}