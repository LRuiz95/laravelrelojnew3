<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class Alumno extends Model
{
    use HasFactory;

    protected $table = 'alumnos';

    protected $fillable = [
        'numero_alumno',
        'matricula',
        'matricula_oficial',
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
        'celular',
        'email',
        'lugar_nacimiento',
        'nacionalidad',
        'nivel',
        'turno',
        'grado',
        'subnivel',
        'id_campus',
        'id_escuela',
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

    public function kardex(): HasMany
    {
        return $this->hasMany(AlumnoKardex::class, 'numero_alumno', 'numero_alumno');
    }

    /**
     * Grupos en los que está inscrito este alumno (catálogo global, todos los ciclos).
     * Para filtrar por ciclo usar wherePivot('inicial', $inicial)->wherePivot('final', $final)->wherePivot('periodo', $periodo).
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'alumnos_grupos',
            'numero_alumno',
            'codigo_grupo',
        )->using(\App\Models\Academia\AlumnoGrupo::class)
         ->withPivot('inicial', 'final', 'periodo', 'fecha_inscripcion', 'estatus', 'observaciones');
    }

    /**
     * Inscripciones del alumno en todos los ciclos (via alumnos_grupos).
     * Cada registro tiene acceso al grupo via $inscripcion->grupo.
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(\App\Models\Academia\AlumnoGrupo::class, 'numero_alumno', 'numero_alumno');
    }

    public function cursosAcademicos(): HasMany
    {
        return $this->hasMany(AlumnoCurso::class, 'numero_alumno', 'numero_alumno');
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
        return $query->whereExists(fn ($q) => $q
            ->select(DB::raw(1))
            ->from('alumnos_grupos')
            ->whereColumn('alumnos_grupos.numero_alumno', 'alumnos.numero_alumno')
            ->where('alumnos_grupos.inicial', $inicial)
            ->where('alumnos_grupos.final', $final)
            ->where('alumnos_grupos.periodo', $periodo));
    }

    /**
     * Alumnos inscritos en un ciclo específico, con eager-load del grupo del ciclo.
     * Wrapper de scopePorCiclo + eager load de la relación grupo filtrada por el mismo ciclo.
     */
    public function scopeInscritosEnCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->whereExists(fn ($q) => $q
            ->select(DB::raw(1))
            ->from('alumnos_grupos')
            ->whereColumn('alumnos_grupos.numero_alumno', 'alumnos.numero_alumno')
            ->where('alumnos_grupos.inicial', $inicial)
            ->where('alumnos_grupos.final', $final)
            ->where('alumnos_grupos.periodo', $periodo))
            ->with(['inscripciones' => fn ($q) => $q
                ->where('inicial', $inicial)
                ->where('final', $final)
                ->where('periodo', $periodo)
                ->with('grupo')]);
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

    protected function turnoBase(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->turno ? strtoupper(substr(trim($this->turno), 0, 1)) : null,
        );
    }

    protected function edad(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha_nacimiento ? $this->fecha_nacimiento->age : null,
        );
    }
}