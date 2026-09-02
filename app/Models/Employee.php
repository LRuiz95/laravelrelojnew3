<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\DeviceEmployee;
use App\Models\DeviceSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo central de empleados. Solo información global de la persona
 * (user_id = PIN/badge único global, name). Todo atributo de hardware vive en
 * la tabla pivote device_employee vía la relación devices().
 */
class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * Al modificar enrolamientos (attach/detach/updateExistingPivot) se
     * actualiza el updated_at del empleado para invalidar cachés y reflejar
     * cambios en la UI. BelongsToMany::touchIfTouching() consulta este array.
     */
    protected $touches = ['devices'];

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_employee')
            ->using(DeviceEmployee::class)
            ->withPivot('device_uid', 'role', 'card_number', 'password', 'active', 'fingerprint_count')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(Fingerprint::class);
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(DeviceSync::class);
    }

    public static function roles(): array
    {
        return DeviceEmployee::roles();
    }
}
