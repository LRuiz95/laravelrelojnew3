<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceSyncControllerTest extends TestCase
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

    public function test_sync_users(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.sync-users', $device)
        );

        $response->assertOk();
    }

    public function test_sync_fingerprints(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        // Create some fingerprints first
        $fingerprints = 3;
        for ($i = 0; $i < $fingerprints; $i++) {
            \App\Models\Fingerprint::create([
                'device_id' => $device->id,
                'employee_id' => $employee->id,
                'finger' => $i + 1,
                'template_hash' => 'hash_' . $i,
                'template' => \Illuminate\Support\Str::random(64),
            ]);
        }

        $response = $this->postJson(
            route('devices.sync-fingerprints', $device)
        );

        $response->assertOk();
    }

    public function test_sync_attendances(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        // Create some attendances
        for ($i = 0; $i < 5; $i++) {
            $this->createAttendance($device->id, $employee->id, 0, now()->subHours($i)->toDateTimeString());
        }

        $response = $this->postJson(
            route('devices.sync-attendances', $device)
        );

        $response->assertOk();
    }

    public function test_sync_all(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        // Create some data
        for ($i = 0; $i < 3; $i++) {
            $this->createAttendance($device->id, $employee->id, 0, now()->subHours($i)->toDateTimeString());
        }
        \App\Models\Fingerprint::create([
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'finger' => 1,
            'template_hash' => 'hash_1',
            'template' => \Illuminate\Support\Str::random(64),
        ]);

        $response = $this->postJson(
            route('devices.sync-all', $device)
        );

        $response->assertOk();
    }

    public function test_set_time(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->postJson(
            route('devices.set-time', $device)
        );

        $response->assertOk();
    }

    public function test_clear_attendance(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->post(
            route('devices.clear-attendance', $device)
        );

        $response->assertOk();
    }

    public function test_restore(): void
    {
        $this->actingAdmin();
        ['device' => $device, 'employee' => $employee] = $this->createDeviceEmployee();

        $response = $this->post(
            route('devices.restore', $device)
        );

        $response->assertOk();
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
}