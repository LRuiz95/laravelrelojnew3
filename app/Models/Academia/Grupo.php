<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'codigo_grupo',
        'inicial',
        'final',
        'periodo',
        'grado',
        'turno',
        'nivel',
        'inscritos',
        'id_campus',
        'carrera',
        'activo',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'grado' => 'integer',
        'inscritos' => 'integer',
        'activo' => 'boolean',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'inicial')
            ->whereColumn('ciclos.final', 'grupos.final')
            ->whereColumn('ciclos.periodo', 'grupos.periodo');
    }

    public function nivelRel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel', 'nivel');
    }

    public function turnoRel(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno', 'turno');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    /**
     * Query builder for alumnos enrolled in this grupo.
     * NOT a relationship — Grupo has composite PK so BelongsToMany doesn't work.
     */
    public function alumnos()
    {
        return Alumno::query()
            ->select('alumnos.*')
            ->join('alumnos_grupos', function ($join) {
                $join->on('alumnos.numero_alumno', '=', 'alumnos_grupos.numero_alumno')
                    ->where('alumnos_grupos.codigo_grupo', $this->codigo_grupo)
                    ->where('alumnos_grupos.inicial', $this->inicial)
                    ->where('alumnos_grupos.final', $this->final)
                    ->where('alumnos_grupos.periodo', $this->periodo);
            })
            ->selectRaw('
                alumnos_grupos.fecha_inscripcion as pivot_fecha_inscripcion,
                alumnos_grupos.estatus as pivot_estatus,
                alumnos_grupos.observaciones as pivot_observaciones
            ');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'codigo_grupo')
            ->whereColumn('horarios_det.inicial', 'grupos.inicial')
            ->whereColumn('horarios_det.final', 'grupos.final')
            ->whereColumn('horarios_det.periodo', 'grupos.periodo');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->where('inicial', $inicial)->where('final', $final)->where('periodo', $periodo);
    }

    public function scopePorNivelTurno($query, string $nivel, string $turno)
    {
        return $query->where('nivel', $nivel)->where('turno', $turno);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "Grupo {$this->codigo_grupo} - {$this->grado}° {$this->turnoRel?->descripcion_corta}",
        );
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->ciclo->label} - {$this->label}",
        );
    }
}