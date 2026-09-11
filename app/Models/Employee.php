<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Academia\Sede;
use App\Models\Pivots\DeviceEmployee;
use App\Models\DeviceSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo central de empleados. Solo información global de la persona
 * (user_id = PIN/badge único global, name). Todo atributo de hardware vive en
 * la tabla pivote device_employee vía la relación devices().
 *
 * ADR-001: type enum (biometric, admin, teacher) - campos academia/RRHH solo usados según type
 */
class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',              // biometric | admin | teacher
        'numero_empleado',   // EMPLEADOS.NUMEMPLEADO
        'clave_profesor',    // PROFESORES.CLAVEPROFESOR
        'departamento',
        'cargo',
        'contrato',
        'status_actual',     // A=Activo, B=Baja
        'fecha_ingreso',
        'id_campus',
        'nivel',
        'tarjeta_id',
        'area_id',
        'puesto_id',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
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

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    public static function roles(): array
    {
        return DeviceEmployee::roles();
    }

    // Scopes por tipo (ADR-001)
    public function scopeBiometric($query)
    {
        return $query->where('type', 'biometric');
    }

    public function scopeAdmin($query)
    {
        return $query->where('type', 'admin');
    }

    public function scopeTeacher($query)
    {
        return $query->where('type', 'teacher');
    }

    /**
     * Empleados activos: status_actual = 'A' o NULL (sin dato se trata como activo).
     */
    public function scopeActivos($query)
    {
        return $query->where(function ($q) {
            $q->where('status_actual', 'A')
              ->orWhereNull('status_actual');
        });
    }

    /**
     * Empleados dados de baja: status_actual = 'B'.
     */
    public function scopeBajas($query)
    {
        return $query->where('status_actual', 'B');
    }

    // Accessor para etiqueta de tipo
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'biometric' => 'Biométrico (ZKTeco)',
            'admin' => 'Administrativo',
            'teacher' => 'Docente',
            default => $this->type,
        };
    }

    public function getStatusActualLabelAttribute(): string
    {
        return match ($this->status_actual) {
            'A' => 'Activo',
            'B' => 'Baja',
            default => $this->status_actual ?? '—',
        };
    }
}
