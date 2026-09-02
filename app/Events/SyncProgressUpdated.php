<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncProgressUpdated implements ShouldBroadcast, ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public \App\Models\DeviceSync $sync;

    public string $status;

    public int $processed;

    public int $total;

    public string $stage;

    public string $operation;

    public function __construct(\App\Models\DeviceSync $sync, string $status)
    {
        $this->sync = $sync;
        $this->status = $status;
        $this->processed = $sync->processed ?? 0;
        $this->total = $sync->total ?? 0;
        $this->stage = $sync->stage ?? '';
        $this->operation = $sync->operation ?? 'all';
    }

    public function broadcastWith(): array
    {
        $safeTotal = max(1, $this->total);
        $safeProcessed = min(max(0, $this->processed), $safeTotal);
        $progress = $safeTotal > 0 ? (int) min(100, round(($safeProcessed / $safeTotal) * 100)) : 0;

        return [
            'id' => $this->sync->id,
            'status' => $this->status,
            'processed' => $safeProcessed,
            'total' => $safeTotal,
            'progress' => $progress,
            'stage' => $this->stage,
            'operation' => $this->operation,
            'created' => $this->sync->created_count ?? 0,
            'updated' => $this->sync->updated_count ?? 0,
            'error' => $this->sync->error_message,
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('sync-progress.'.$this->sync->id);
    }
}
