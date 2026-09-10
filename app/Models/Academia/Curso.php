<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'inicial',
        'final',
        'periodo',
        'id_plan',
        'id_tipoeval',
        'id_etapa',
        'clave_curso',
        'codigo_grupo',
        'clave_asignatura',
        'clave_profesor',
        'cupo_maximo',
        'desde',
        'hasta',
        'sesiones',
        'inscritos',
        'suplente',
        'nombre_curso',
        'nivel',
        'turno',
        'id_campus',
        'activo',
    ];

    protected $casts = [
        'id_plan' => 'integer',
        'cupo_maximo' => 'integer',
        'sesiones' => 'integer',
        'inscritos' => 'integer',
        'desde' => 'date',
        'hasta' => 'date',
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'activo' => 'boolean',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'inicial', 'inicial');
    }

    /**
     * Resolve ciclo with all 3 columns (inicial + final + periodo).
     */
    public function getCicloCompletoAttribute(): ?Ciclo
    {
        return Ciclo::query()
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->first();
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id_plan');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'clave_profesor', 'clave_profesor');
    }

    public function nivelRel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel', 'nivel');
    }

    public function turnoRel(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno', 'turno');
    }

    public function materias(): HasMany
    {
        return $this->hasMany(CursoDet::class, 'curso_id', 'id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->where('inicial', $inicial)->where('final', $final)->where('periodo', $periodo);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->nombre_curso} ({$this->clave_curso})",
        );
    }

    protected function cicloLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->inicial}-{$this->final}-{$this->periodo}",
        );
    }

    protected function turnoNombre(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->turno ? strtoupper(substr(trim($this->turno), 0, 1)) : null) {
                'M' => 'Matutino',
                'V' => 'Vespertino',
                default => $this->turno ?: 'Sin turno',
            },
        );
    }
}