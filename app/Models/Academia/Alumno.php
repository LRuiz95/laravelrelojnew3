<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Alumno extends Model
{
    use HasFactory;

    protected $table = 'alumnos';

    protected $fillable = [
        'numero_alumno',
        'paterno',
        'materno',
        'nombre',
        'curp',
        'fecha_nacimiento',
        'sexo',
        'estado_civil',
        'direccion',
        'colonia',
        'ciudad',
        'estado',
        'cp',
        'telefono',
        'email',
        'lugar_nacimiento',
        'nacionalidad',
        'nivel',
        'turno',
        'id_campus',
        'carrera',
        'plan',
        'fecha_ingreso',
        'estatus',
        'tipo_ingreso',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
    ];

    protected $primaryKey = 'numero_alumno';
    public $incrementing = false;
    protected $keyType = 'int';

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'alumnos_grupos', 'numero_alumno', 
            ['codigo_grupo', 'inicial', 'final', 'periodo'])
            ->withPivot('fecha_inscripcion', 'estatus', 'observaciones')
            ->withTimestamps();
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(AlumnoKardex::class, 'numero_alumno', 'numero_alumno');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    public function nivelRel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel', 'nivel');
    }

    public function turnoRel(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno', 'turno');
    }

    public function scopeActivo($query)
    {
        return $query->where('estatus', 'ACTIVO');
    }

    public function scopePorEstatus($query, string $estatus)
    {
        return $query->where('estatus', $estatus);
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->whereHas('grupos', fn ($q) => $q->where('inicial', $inicial)
            ->where('final', $final)->where('periodo', $periodo));
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->paterno} {$this->materno} {$this->nombre}"),
        );
    }

    protected function iniciales(): Attribute
    {
        return Attribute::make(
            get: fn () => strtoupper(substr($this->paterno, 0, 1) . substr($this->materno ?? '', 0, 1) . substr($this->nombre, 0, 1)),
        );
    }

    protected function edad(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha_nacimiento ? $this->fecha_nacimiento->age : null,
        );
    }
}