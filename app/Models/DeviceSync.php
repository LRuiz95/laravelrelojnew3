<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceSync extends Model
{
    protected $fillable = [
        'device_id',
        'status',
        'operation',
        'stage',
        'employee_id',
        'started_at',
        'finished_at',
        'processed',
        'total',
        'created_count',
        'updated_count',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'employee_id' => 'integer',
        'processed' => 'integer',
        'total' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeviceSyncItem::class);
    }

    public function getOperationLabelAttribute(): string
    {
        return [
            'users' => 'usuarios',
            'fingerprints' => 'huellas',
            'attendances' => 'asistencias',
            'all' => 'todo',
            'sync_full' => 'sincronización completa',
        ][$this->operation] ?? $this->operation ?? '—';
    }
}
