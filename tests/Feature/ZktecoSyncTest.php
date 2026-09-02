<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ZktecoConnectionException;
use App\Jobs\SyncEmployeeToDeviceJob;
use App\Jobs\SyncDeviceJob;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Models\Employee;
use App\Services\ZktecoService;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ZktecoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_sync_without_duplicates(): void
    {
        $device = Device::create(['name' => 'Prueba', 'ip' => '192.168.1.20']);
        $client = Mockery::mock(ZKTeco::class);
        $client->shouldReceive('connect')->twice()->andReturnTrue();
        $client->shouldReceive('getUsers')->twice()->andReturn([
            ['user_id' => 10, 'uid' => 7, 'name' => 'Ana', 'role' => 0, 'card_no' => null, 'password' => null],
        ]);
        $client->shouldReceive('disconnect')->twice();

        $service = new TestableZktecoService($device, $client);

        $first = $service->syncUsers();
        $second = $service->syncUsers();

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['updated']);
        $this->assertSame(1, Employee::count());
        $this->assertDatabaseHas('device_employee', [
            'device_id' => $device->id,
            'device_uid' => 7,
            'active' => true,
        ]);
    }

    public function test_same_user_id_from_different_devices_is_not_duplicated(): void
    {
        $firstDevice = Device::create(['name' => 'Primero', 'ip' => '192.168.1.25']);
        $secondDevice = Device::create(['name' => 'Segundo', 'ip' => '192.168.1.26']);

        $firstClient = Mockery::mock(ZKTeco::class);
        $firstClient->shouldReceive('connect')->once()->andReturnTrue();
        $firstClient->shouldReceive('getUsers')->once()->andReturn([
            ['user_id' => 1002, 'uid' => 1, 'name' => 'Omar', 'role' => 0, 'card_no' => null, 'password' => null],
        ]);
        $firstClient->shouldReceive('disconnect')->once();

        $secondClient = Mockery::mock(ZKTeco::class);
        $secondClient->shouldReceive('connect')->once()->andReturnTrue();
        $secondClient->shouldReceive('getUsers')->once()->andReturn([
            ['user_id' => 1002, 'uid' => 90, 'name' => 'Omar Avila', 'role' => 0, 'card_no' => null, 'password' => null],
        ]);
        $secondClient->shouldReceive('disconnect')->once();

        (new TestableZktecoService($firstDevice, $firstClient))->syncUsers();
        (new TestableZktecoService($secondDevice, $secondClient))->syncUsers();

        // Una sola entrada de catálogo con dos enrolamientos (uno por equipo).
        $employee = Employee::where('user_id', '1002')->firstOrFail();
        $this->assertSame('Omar Avila', $employee->name);
        $this->assertSame(2, $employee->devices()->count());

        $uidsByDevice = $employee->devices()->pluck('device_uid', 'devices.id');
        $this->assertSame(1, (int) $uidsByDevice[$firstDevice->id]);
        $this->assertSame(90, (int) $uidsByDevice[$secondDevice->id]);
    }

    public function test_offline_device_raises_a_connection_exception(): void
    {
        $device = Device::create(['name' => 'Offline', 'ip' => '192.168.1.21']);
        $service = new OfflineZktecoService($device);

        $this->expectException(ZktecoConnectionException::class);
        $service->getUsers();
    }

    public function test_employee_can_be_created_in_database(): void
    {
        $device = Device::create(['name' => 'Prueba', 'ip' => '192.168.1.22']);

        $employee = Employee::create([
            'user_id' => '22',
            'name' => 'Luis',
        ]);
        $device->employees()->attach($employee, [
            'device_uid' => 8,
            'role' => 0,
        ]);

        $this->assertDatabaseHas('employees', ['user_id' => '22', 'name' => 'Luis']);
        $this->assertDatabaseHas('device_employee', [
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'device_uid' => 8,
            'role' => 0,
        ]);
    }

    public function test_employee_sync_creates_one_full_task_per_selected_device_without_duplicates(): void
    {
        Queue::fake();

        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $employee = Employee::create(['user_id' => '320', 'name' => 'Empleado central']);
        $firstDevice = Device::create(['name' => 'D1', 'ip' => '192.168.1.41']);
        $secondDevice = Device::create(['name' => 'D2', 'ip' => '192.168.1.42']);

        $response = $this->actingAs($user)->post(route('employees.sync-devices', $employee), [
            'device_ids' => [$firstDevice->id, $secondDevice->id],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, DeviceSync::where('employee_id', $employee->id)->count());
        $this->assertSame(2, DeviceSync::where('employee_id', $employee->id)->where('operation', 'sync_full')->count());
        Queue::assertPushed(SyncEmployeeToDeviceJob::class, 2);

        $this->actingAs($user)->post(route('employees.sync-devices', $employee), [
            'device_ids' => [$firstDevice->id, $secondDevice->id],
        ]);

        $this->assertSame(2, DeviceSync::where('employee_id', $employee->id)->count());
        Queue::assertPushed(SyncEmployeeToDeviceJob::class, 2);
    }

    public function test_catalog_pin_is_globally_unique(): void
    {
        Employee::create(['user_id' => '444', 'name' => 'Primera']);

        $this->expectException(QueryException::class);

        Employee::create(['user_id' => '444', 'name' => 'Duplicado']);
    }

    public function test_card_number_can_repeat_across_devices_but_not_within_one(): void
    {
        $deviceA = Device::create(['name' => 'Edificio A', 'ip' => '192.168.1.31']);
        $deviceB = Device::create(['name' => 'Edificio B', 'ip' => '192.168.1.32']);

        $employee = Employee::create(['user_id' => '55', 'name' => 'Con tarjeta']);
        $other = Employee::create(['user_id' => '56', 'name' => 'Otra persona']);

        // La misma tarjeta en dos equipos distintos es válida (misma persona).
        $deviceA->employees()->attach([$employee->id => ['device_uid' => 1, 'card_number' => '998877']]);
        $deviceB->employees()->attach([$other->id => ['device_uid' => 1]]);

        DB::table('device_employee')
            ->where('device_id', $deviceB->id)
            ->update(['card_number' => '998877']);

        $this->expectException(QueryException::class);

        // Dos personas distintas con la misma tarjeta EN EL MISMO equipo: no.
        $deviceA->employees()->attach([$other->id => ['device_uid' => 2, 'card_number' => '998877']]);
    }

    public function test_partial_sync_data_is_not_marked_as_failed_when_records_were_saved(): void
    {
        $device = Device::create(['name' => 'Parcial', 'ip' => '192.168.1.28']);
        $sync = DeviceSync::create([
            'device_id' => $device->id,
            'status' => 'running',
            'operation' => 'all',
            'stage' => 'usuarios',
            'processed' => 10,
            'total' => 20,
            'created_count' => 10,
            'updated_count' => 0,
        ]);

        (new SyncDeviceJob($device, $sync, 'all'))->failed(new \RuntimeException('error simulado'));

        $this->assertSame('completed', $sync->fresh()->status);
        $this->assertSame('Sincronización completada con advertencias.', $sync->fresh()->error_message ?? null);
    }

    public function test_full_sync_uses_the_service_stage_flow_for_users_assistances_and_fingerprints(): void
    {
        $device = Device::create(['name' => 'Completa', 'ip' => '192.168.1.29']);
        $sync = DeviceSync::create([
            'device_id' => $device->id,
            'status' => 'queued',
            'operation' => 'all',
            'stage' => 'Preparando',
            'processed' => 0,
            'total' => 10,
        ]);

        $service = new class($device) extends ZktecoService
        {
            public array $calls = [];

            public function syncUsers(?callable $onProgress = null): array
            {
                $this->calls[] = 'users';

                return ['created' => 2, 'updated' => 1, 'total' => 3];
            }

            public function syncAttendances(?callable $onProgress = null): array
            {
                $this->calls[] = 'attendances';

                return ['created' => 4, 'total' => 4];
            }

            public function syncFingerprints(?int $employeeId = null, int $batchSize = 25, ?callable $onProgress = null): array
            {
                $this->calls[] = 'fingerprints';

                return ['created' => 3, 'updated' => 2, 'processed' => 5];
            }
        };

        $job = new class($device, $sync, 'all') extends SyncDeviceJob
        {
            public function exposeAllSync(ZktecoService $service, array &$warnings): array
            {
                return $this->runAllSync($service, $warnings);
            }
        };

        $warnings = [];
        $result = $job->exposeAllSync($service, $warnings);

        $this->assertSame(['users', 'attendances', 'fingerprints'], $service->calls);
        $this->assertSame(2, $result['users']['created']);
        $this->assertSame(4, $result['attendances']['created']);
        $this->assertSame(3, $result['fingerprints']['created']);
        $this->assertSame([], $warnings);
    }

    public function test_full_sync_progress_is_capped_and_not_over_100_percent(): void
    {
        $device = Device::create(['name' => 'Progress', 'ip' => '192.168.1.30']);
        $sync = DeviceSync::create([
            'device_id' => $device->id,
            'status' => 'completed',
            'operation' => 'all',
            'stage' => 'Terminado',
            'processed' => 579,
            'total' => 1,
            'created_count' => 504,
            'updated_count' => 0,
        ]);

        $payload = (new \App\Events\SyncProgressUpdated($sync, 'completed'))->broadcastWith();

        $this->assertSame(100, $payload['progress']);
        $this->assertSame(1, $payload['total']);
    }

    public function test_device_serial_number_cannot_be_repeated(): void
    {
        Device::create([
            'name' => 'Principal',
            'ip' => '192.168.1.23',
            'serial_number' => 'SERIAL-001',
        ]);

        $this->expectException(QueryException::class);

        Device::create([
            'name' => 'Duplicado',
            'ip' => '192.168.1.24',
            'serial_number' => 'SERIAL-001',
        ]);
    }

    public function test_fingerprint_is_unique_per_device_employee_and_finger(): void
    {
        $deviceA = Device::create(['name' => 'Huellas A', 'ip' => '192.168.1.27']);
        $deviceB = Device::create(['name' => 'Huellas B', 'ip' => '192.168.1.33']);

        $employee = Employee::create(['user_id' => '33', 'name' => 'Empleado global']);
        $deviceA->employees()->attach($employee->id, ['device_uid' => 3]);
        $deviceB->employees()->attach($employee->id, ['device_uid' => 9]);

        $row = fn (Device $device): array => [
            'device_id' => $device->id,
            'employee_id' => $employee->id,
            'finger' => 2,
            'template' => 'template-a',
            'template_hash' => hash('sha256', 'template-a'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('fingerprints')->insert($row($deviceA));
        // El mismo empleado y dedo en OTRO checador es válido (plantillas son
        // propiedad de cada equipo).
        DB::table('fingerprints')->insert($row($deviceB));
        $this->assertSame(2, DB::table('fingerprints')->count());

        $this->expectException(QueryException::class);

        // Duplicado exacto en el mismo dispositivo: rechazado por el único
        // compuesto [device_id, employee_id, finger].
        DB::table('fingerprints')->insert($row($deviceA));
    }

    public function test_syncing_fingerprints_reattributes_legacy_null_origin_rows(): void
    {
        $device = Device::create(['name' => 'Reatribucion', 'ip' => '192.168.1.35']);

        $employee = Employee::create(['user_id' => '88', 'name' => 'Con huella legada']);
        $device->employees()->attach($employee->id, ['device_uid' => 4]);

        // Legado: dedos 1 y 2 sin origen (migración del 20-08).
        foreach ([1, 2] as $finger) {
            DB::table('fingerprints')->insert([
                'employee_id' => $employee->id,
                'finger' => $finger,
                'template' => "tpl-legada-{$finger}",
                'template_hash' => hash('sha256', "tpl-legada-{$finger}"),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $client = Mockery::mock(ZKTeco::class);
        $client->shouldReceive('connect')->once()->andReturnTrue();
        // El equipo solo reporta el dedo 1.
        $client->shouldReceive('getFingerprint')->once()->with(4)->andReturn([
            1 => 'tpl-nueva',
        ]);
        $client->shouldReceive('disconnect')->once();

        (new TestableZktecoService($device, $client))->syncFingerprints();

        // Dedo 1 re-atribuido: una sola fila y con origen real (sin duplicado NULL).
        $this->assertSame(1, DB::table('fingerprints')
            ->where('employee_id', $employee->id)
            ->where('finger', 1)
            ->count());
        $this->assertDatabaseHas('fingerprints', [
            'employee_id' => $employee->id,
            'finger' => 1,
            'device_id' => $device->id,
        ]);

        // Dedo 2 no reportado por el equipo: conserva su fila legada como única copia.
        $this->assertDatabaseHas('fingerprints', [
            'employee_id' => $employee->id,
            'finger' => 2,
            'device_id' => null,
        ]);
    }

    public function test_centralization_command_is_idempotent_on_an_already_migrated_database(): void
    {
        $device = Device::create(['name' => 'Legacy', 'ip' => '192.168.1.34']);
        $employee = Employee::create(['user_id' => '77', 'name' => 'Ya migrada']);
        $device->employees()->attach($employee->id, ['device_uid' => 5]);

        // Bajo RefreshDatabase las migraciones ya corrieron: ambas pasadas
        // deben caer en la rama "ya migrado" sin duplicar ni alterar datos.
        $this->artisan('migrate:employees-to-central')->assertSuccessful();
        $this->artisan('migrate:employees-to-central')->assertSuccessful();

        $this->assertSame(1, Employee::count());
        $this->assertSame(1, DB::table('device_employee')->count());
        $this->assertDatabaseHas('employees', ['user_id' => '77', 'name' => 'Ya migrada']);
    }

    /**
     * Regresión: en este firmware el byte "state" del log viene constante en 1
     * y el modo real del checado viaja en "type". Las etiquetas/colores deben
     * derivar de type (con state como respaldo), no de state.
     */
    public function test_attendance_labels_derive_from_punch_type(): void
    {
        $device = Device::create(['name' => 'Estados', 'ip' => '192.168.1.36']);

        $cases = [
            ['state' => 1, 'type' => 0, 'label' => 'Entrada', 'short' => 'Entrada', 'color' => 'cat-green'],
            ['state' => 1, 'type' => 1, 'label' => 'Salida', 'short' => 'Salida', 'color' => 'cat-blue'],
            ['state' => 1, 'type' => 4, 'label' => 'Entrada de tiempo extra', 'short' => 'Entrada T.E.', 'color' => 'cat-purple'],
            ['state' => 1, 'type' => 5, 'label' => 'Salida de tiempo extra', 'short' => 'Salida T.E.', 'color' => 'cat-lavender'],
        ];

        foreach ($cases as $i => $case) {
            $attendance = Attendance::create([
                'device_id' => $device->id,
                'user_id' => '233',
                'state' => $case['state'],
                'type' => $case['type'],
                'recorded_at' => now()->addSeconds($i),
            ]);

            $this->assertSame($case['label'], $attendance->stateLabel(), "stateLabel con type={$case['type']}");
            $this->assertSame($case['short'], $attendance->shortStateLabel(), "shortStateLabel con type={$case['type']}");
            $this->assertSame($case['color'], $attendance->stateColorClass(), "stateColorClass con type={$case['type']}");
        }

        // Firmware que sí reporta el modo en state: type fuera del mapa → respaldo.
        $legacy = Attendance::create([
            'device_id' => $device->id,
            'user_id' => '234',
            'state' => 5,
            'type' => 250,
            'recorded_at' => now(),
        ]);
        $this->assertSame('Salida T.E.', $legacy->shortStateLabel());
    }

    /**
     * Regresión: devices.status solo se actualizaba desde el botón manual de
     * verificación; las sincronizaciones exitosas lo dejaban en "unknown" y el
     * dashboard mostraba cero checadores en línea aunque hubiera conectividad.
     * Ahora boot() marca online/offline en cada intento real de conexión.
     */
    public function test_successful_connection_marks_device_online(): void
    {
        $device = Device::create(['name' => 'Ping OK', 'ip' => '192.168.1.50']);

        $zk = Mockery::mock(ZKTeco::class);
        $zk->shouldReceive('ping')->once()->andReturnTrue();

        $service = new class($device, $zk) extends ZktecoService
        {
            public function __construct(Device $device, private readonly ZKTeco $stubbedClient)
            {
                parent::__construct($device);
            }

            public function client(): ZKTeco
            {
                return $this->stubbedClient;
            }

            public function probe(): ZKTeco
            {
                return $this->boot();
            }
        };

        $service->probe();

        $this->assertSame('online', $device->fresh()->status);
    }

    public function test_failed_ping_marks_device_offline_and_raises(): void
    {
        $device = Device::create(['name' => 'Ping Fail', 'ip' => '192.168.1.51']);

        $zk = Mockery::mock(ZKTeco::class);
        $zk->shouldReceive('ping')->once()->andReturnFalse();

        $service = new class($device, $zk) extends ZktecoService
        {
            public function __construct(Device $device, private readonly ZKTeco $stubbedClient)
            {
                parent::__construct($device);
            }

            public function client(): ZKTeco
            {
                return $this->stubbedClient;
            }

            public function probe(): ZKTeco
            {
                return $this->boot();
            }
        };

        try {
            $service->probe();
            $this->fail('Se esperaba ZktecoConnectionException.');
        } catch (ZktecoConnectionException) {
            $this->assertSame('offline', $device->fresh()->status);
        }
    }
}

final class TestableZktecoService extends ZktecoService
{
    public function __construct(Device $device, private ZKTeco $testClient)
    {
        parent::__construct($device);
    }

    protected function boot(): ZKTeco
    {
        return $this->testClient;
    }
}

final class OfflineZktecoService extends ZktecoService
{
    protected function boot(): ZKTeco
    {
        throw new ZktecoConnectionException('offline', '192.168.1.21');
    }
}
