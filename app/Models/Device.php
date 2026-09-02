<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\DeviceEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'port',
        'password',
        'serial_number',
        'device_name',
        'status',
        'description',
    ];

    protected $casts = [
        'port' => 'integer',
        'password' => 'encrypted',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'device_employee')
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
        // Las huellas son por dispositivo desde 2026_08_23_000002: cada
        // checador conserva sus propias plantillas para el mismo empleado.
        return $this->hasMany(Fingerprint::class);
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(DeviceSync::class);
    }

    public function latestSync(): HasOne
    {
        return $this->hasOne(DeviceSync::class)->latestOfMany();
    }

    public static function states(): array
    {
        return [
            'online' => 'En línea',
            'offline' => 'Sin conexión',
            'unknown' => 'Desconocido',
        ];
    }
}
