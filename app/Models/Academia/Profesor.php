<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Profesor extends Model
{
    use HasFactory;

    protected $table = 'profesores';

    protected $fillable = [
        'clave_profesor',
        'nombre_profesor',
        'paterno',
        'materno',
        'departamento',
        'contrato',
        'status_actual',
        'origen_horario',
        'fecha_ingreso',
        'id_campus',
        'nivel',
        'turno',
        'rfc',
        'curp',
        'email',
        'telefono',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
    ];

    protected $primaryKey = 'clave_profesor';
    public $incrementing = false;
    protected $keyType = 'string';

    public function contratoRel(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato', 'contrato');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'clave_profesor', 'clave_profesor');
    }

    public function scopeActivo($query)
    {
        return $query->where('status_actual', 'A');
    }

    public function scopePorOrigen($query, string $origen)
    {
        return $query->where('origen_horario', $origen);
    }

    public function scopePTC($query)
    {
        return $query->where('origen_horario', 'HD');
    }

    public function scopePA($query)
    {
        return $query->where('origen_horario', 'CA');
    }

    public function scopePorDepartamento($query, string $departamento)
    {
        return $query->where('departamento', $departamento);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->paterno} {$this->materno} {$this->nombre_profesor}"),
        );
    }

    protected function iniciales(): Attribute
    {
        return Attribute::make(
            get: fn () => strtoupper(substr($this->paterno ?? '', 0, 1) . substr($this->materno ?? '', 0, 1) . substr($this->nombre_profesor, 0, 1)),
        );
    }

    protected function tipoContrato(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contratoRel?->descripcion ?? $this->contrato,
        );
    }

    protected function esPTC(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->origen_horario === 'HD',
        );
    }

    protected function esPA(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->origen_horario === 'CA',
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status_actual) {
                'A' => 'Activo',
                'B' => 'Baja',
                default => $this->status_actual,
            },
        );
    }

    protected function origenHorarioLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->origen_horario) {
                'HD' => 'Hora Docente (PTC)',
                'CA' => 'Carga Asignada (PA)',
                default => $this->origen_horario ?? '—',
            },
        );
    }
}