<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CursoDet extends Model
{
    use HasFactory;

    protected $table = 'cursos_det';

    protected $fillable = [
        'curso_id',
        'clave_asignatura',
        'semestre',
        'horas_teoria',
        'horas_practica',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'curso_id' => 'integer',
        'semestre' => 'integer',
        'horas_teoria' => 'integer',
        'horas_practica' => 'integer',
        'activo' => 'boolean',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCurso($query, int $cursoId)
    {
        return $query->where('curso_id', $cursoId);
    }

    public function scopePorSemestre($query, int $semestre)
    {
        return $query->where('semestre', $semestre);
    }

    protected function horasTotales(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->horas_teoria + $this->horas_practica,
        );
    }

    protected function tipoLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->tipo) {
                'obligatoria' => 'Obligatoria',
                'optativa' => 'Optativa',
                default => $this->tipo,
            },
        );
    }
}