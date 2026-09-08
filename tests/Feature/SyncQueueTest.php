<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\SyncDeviceJob;
use App\Models\Device;
use App\Models\DeviceSync;
use Database\Factories\DeviceFactory;

class SyncQueueTest extends TestCase
{
    /** @test */
    public function sync_device_job_can_be_instantiated_without_error()
    {
        $device = DeviceFactory::new()->create();
        $sync = new DeviceSync();

        $job = new SyncDeviceJob($device, $sync);

        $this->assertNotNull($job);
    }

    /** @test */
    public function sync_device_job_queue_property_is_device_sync()
    {
        $device = DeviceFactory::new()->create();
        $sync = new DeviceSync();

        $job = new SyncDeviceJob($device, $sync);

        $this->assertEquals('device-sync', $job->queue);
    }
}