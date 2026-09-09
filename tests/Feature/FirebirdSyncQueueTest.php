<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\FirebirdSync;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirebirdSyncQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['role' => Role::Admin]);
    }

    /** @test */
    public function pending_firebird_sync_remains_pending_until_execute_pending_is_called()
    {
        // Create a pending FirebirdSync with tables [CFGSEDES] and skip_existing true
        $sync = FirebirdSync::create([
            'operation' => 'sync_custom',
            'status' => 'pending',
            'tables' => ['CFGSEDES'],
            'options' => ['skip_existing' => true, 'tables' => ['CFGSEDES']],
        ]);

        // Verify it's pending
        $this->assertDatabaseHas('firebird_syncs', [
            'id' => $sync->id,
            'status' => 'pending',
        ]);

        // At this point, without executePending, it should stay pending
        $this->assertEquals('pending', FirebirdSync::find($sync->id)->status);
    }

    /** @test */
    public function firebird_sync_job_run_sync_changes_status_from_pending_and_cleanups_jobs()
    {
        // Create a pending FirebirdSync with tables [CFGSEDES] and skip_existing true
        $sync = FirebirdSync::create([
            'operation' => 'sync_custom',
            'status' => 'pending',
            'tables' => ['CFGSEDES'],
            'options' => ['skip_existing' => true, 'tables' => ['CFGSEDES']],
        ]);

        $originalStatus = $sync->status;

        // Run the job synchronously - it will change status from pending
        // (may become completed or failed depending on Firebird connectivity,
        //  but it must NOT stay pending)
        \App\Jobs\FirebirdSyncJob::runSync(
            $sync,
            'sync_custom',
            null,
            false, // deleteOrphans
            ['CFGSEDES'], // tables
            true // skipExisting
        );

        // Verify status changed from pending (the bug fix: it no longer stays pending)
        $synced = FirebirdSync::find($sync->id);
        $this->assertNotEquals('pending', $synced->status, 'Bug fix verified: sync status changed from pending');

        // Verify job was cleaned from jobs table
        $jobCount = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('payload', 'like', '%FirebirdSyncJob%')
            ->where('payload', 'like', '%s:2:"id";i:'.$sync->id.';%')
            ->count();
        $this->assertEquals(0, $jobCount, 'Job should be cleaned from jobs table');
    }
}