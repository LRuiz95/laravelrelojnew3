<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        return $this->belongsTo(Ciclo::class, ['inicial', 'final', 'periodo'], ['inicial', 'final', 'periodo']);
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

    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'alumnos_grupos', 
            ['codigo_grupo', 'inicial', 'final', 'periodo'], 'numero_alumno')
            ->withPivot('fecha_inscripcion', 'estatus', 'observaciones')
            ->withTimestamps();
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, ['codigo_grupo', 'inicial', 'final', 'periodo'], 
            ['codigo_grupo', 'inicial', 'final', 'periodo']);
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