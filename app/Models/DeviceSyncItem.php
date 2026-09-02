<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DeviceSyncItem extends Model
{
    protected $fillable = [
        'device_sync_id',
        'fingerprint_id',
        'credential_type',
        'finger',
        'status',
        'attempts',
        'message',
    ];

    protected $casts = [
        'finger' => 'integer',
        'attempts' => 'integer',
    ];

    public function sync(): BelongsTo
    {
        return $this->belongsTo(DeviceSync::class, 'device_sync_id');
    }

    public function fingerprint(): BelongsTo
    {
        return $this->belongsTo(Fingerprint::class);
    }

    public function label(): string
    {
        return match ($this->credential_type) {
            'employee' => 'Empleado',
            'pin' => 'PIN',
            'card' => 'Tarjeta',
            'fingerprint' => 'Huella '.$this->finger,
            default => $this->credential_type,
        };
    }
}
