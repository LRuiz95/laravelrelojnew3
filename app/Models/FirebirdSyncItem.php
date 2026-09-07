<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FirebirdSyncItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'firebird_sync_id',
        'table_name',
        'action',
        'record_id',
        'status',
        'message',
        'data_before',
        'data_after',
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after' => 'array',
    ];

    public function sync(): BelongsTo
    {
        return $this->belongsTo(FirebirdSync::class, 'firebird_sync_id');
    }

    public function actionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->action) {
                'insert' => 'Insertado',
                'update' => 'Actualizado',
                'delete' => 'Eliminado (huérfano)',
                'skip' => 'Omitido (sin cambios)',
                'error' => 'Error',
                default => $this->action,
            },
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'success' => 'Éxito',
                'error' => 'Error',
                'skipped' => 'Omitido',
                default => $this->status,
            },
        );
    }
}
