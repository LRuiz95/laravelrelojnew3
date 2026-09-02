<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Enrolamiento de una persona en un checador. Toda la metadata de hardware
 * (UID local del equipo, rol, tarjeta, PIN, estado) vive aquí: la misma
 * persona puede estar enrolada en varios dispositivos con UIDs distintos.
 */
final class DeviceEmployee extends Pivot
{
    public const ROLES = [
        0 => 'Usuario',
        13 => 'Supervisor',
        14 => 'Admin',
    ];

    protected $table = 'device_employee';

    protected $fillable = [
        'device_id',
        'employee_id',
        'device_uid',
        'role',
        'card_number',
        'password',
        'active',
        'fingerprint_count',
    ];

    /**
     * Al modificar la pivote se actualiza updated_at del catálogo (BelongsToMany::touchIfTouching).
     */
    protected $touches = ['employee'];

    protected function casts(): array
    {
        return [
            'role' => 'integer',
            'device_uid' => 'integer',
            'fingerprint_count' => 'integer',
            'active' => 'boolean',
            // El PIN del checador es credencial: cifrado en reposo.
            'password' => 'encrypted',
        ];
    }

    public static function roles(): array
    {
        return self::ROLES;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? 'Desconocido';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
