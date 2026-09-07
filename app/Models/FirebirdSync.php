<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FirebirdSync extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation',
        'ciclo',
        'status',
        'stage',
        'started_at',
        'finished_at',
        'processed',
        'total',
        'created_count',
        'updated_count',
        'deleted_count',
        'error_message',
        'log',
        'options',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'processed' => 'integer',
        'total' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'deleted_count' => 'integer',
        'log' => 'array',
        'options' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FirebirdSyncItem::class);
    }

    public function operationLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->operation) {
                'sync_ciclo' => 'Sincronizar Ciclo',
                'sync_catalogos' => 'Sincronizar Catálogos',
                'sync_all' => 'Sincronización Completa',
                default => $this->operation,
            },
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'pending' => 'Pendiente',
                'running' => 'En Progreso',
                'completed' => 'Completado',
                'failed' => 'Fallido',
                'cancelled' => 'Cancelado',
                default => $this->status,
            },
        );
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
