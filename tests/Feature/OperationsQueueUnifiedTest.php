<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DeviceSync;
use App\Models\FirebirdSync;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsQueueUnifiedTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['role' => Role::Admin]);
    }

    /** @test */
    public function queueDataUnified_returns_firebird_and_device_syncs()
    {
        // Create a FirebirdSync pending manually
        $firebirdSync = FirebirdSync::create([
            'operation' => 'sync_custom',
            'status' => 'pending',
            'options' => ['skip_existing' => true, 'tables' => ['CFGSEDES']],
        ]);

        // Create a DeviceSync manually
        $deviceSync = DeviceSync::create([
            'operation' => 'sync_full',
            'status' => 'queued',
            'total' => 10,
        ]);

        // Call queueDataUnified without filters
        $response = $this->actingAs($this->adminUser)
            ->get('/sync-queue/data-unified');

        $response->assertOk();
        $data = $response->json('syncs');

        // Verify we have both firebird and device syncs
        $types = array_column($data, 'type');
        $this->assertContains('firebird', $types);
        $this->assertContains('device', $types);

        // Verify firebird sync has type 'firebird'
        $firebirdEntry = $data[array_search('firebird', $types)];
        $this->assertEquals('firebird', $firebirdEntry['type']);
        $this->assertEquals('Firebird: ' . $firebirdSync->operationLabel, $firebirdEntry['device']);

        // Verify pending se normaliza a queued
        $this->assertEquals('queued', $firebirdEntry['status']);
    }

    /** @test */
    public function queueDataUnified_with_type_firebird_filter()
    {
        // Create a FirebirdSync pending manually
        $firebirdSync = FirebirdSync::create([
            'operation' => 'sync_custom',
            'status' => 'pending',
            'options' => ['skip_existing' => true, 'tables' => ['CFGSEDES']],
        ]);

        // Create a DeviceSync manually
        $deviceSync = DeviceSync::create([
            'operation' => 'sync_full',
            'status' => 'queued',
            'total' => 10,
        ]);

        // Call queueDataUnified with ?type=firebird
        $response = $this->actingAs($this->adminUser)
            ->get('/sync-queue/data-unified?type=firebird');

        $response->assertOk();
        $data = $response->json('syncs');

        // Should only have firebird entries
        $types = array_column($data, 'type');
        $this->assertContainsOnly('firebird', $types);

        // Verify pending se normaliza a queued
        foreach ($data as $entry) {
            $this->assertEquals('queued', $entry['status']);
        }
    }

    /** @test */
    public function queueDataUnified_pending_normalized_to_queued()
    {
        // Create a pending FirebirdSync manually
        $firebirdSync = FirebirdSync::create([
            'operation' => 'sync_custom',
            'status' => 'pending',
            'options' => ['skip_existing' => true, 'tables' => ['CFGSEDES']],
        ]);

        // Create a DeviceSync manually
        $deviceSync = DeviceSync::create([
            'operation' => 'sync_full',
            'status' => 'queued',
            'total' => 10,
        ]);

        // Call queueDataUnified - verify pending normalizes to queued
        $response = $this->actingAs($this->adminUser)
            ->get('/sync-queue/data-unified');

        $response->assertOk();
        $data = $response->json('syncs');

        $firebirdEntries = array_filter($data, fn($e) => $e['type'] === 'firebird');
        $this->assertGreaterThan(0, count($firebirdEntries));

        // All firebird entries should have status 'queued' (not 'pending')
        foreach ($firebirdEntries as $entry) {
            $this->assertEquals('queued', $entry['status']);
        }
    }
}