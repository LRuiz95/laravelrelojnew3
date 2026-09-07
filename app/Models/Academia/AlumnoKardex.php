<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AlumnoKardex extends Model
{
    use HasFactory;

    protected $table = 'alumnos_kardex';

    protected $fillable = [
        'numero_alumno',
        'inicial',
        'final',
        'periodo',
        'clave_asignatura',
        'id_eval',
        'calificacion',
        'literal',
        'tipo_examen',
        'fecha_examen',
        'profesor',
        'observaciones',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'calificacion' => 'decimal:2',
        'tipo_examen' => 'integer',
        'fecha_examen' => 'date',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'numero_alumno', 'numero_alumno');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function metodoEval(): BelongsTo
    {
        return $this->belongsTo(MetodoEval::class, 'id_eval', 'id_eval');
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->where('inicial', $inicial)->where('final', $final)->where('periodo', $periodo);
    }

    public function scopePorMateria($query, string $claveAsignatura)
    {
        return $query->where('clave_asignatura', $claveAsignatura);
    }

    protected function aprobado(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->literal === 'S' || ($this->calificacion !== null && $this->calificacion >= 6),
        );
    }

    protected function reprobado(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->literal === 'N' || ($this->calificacion !== null && $this->calificacion < 6),
        );
    }

    protected function sinDerecho(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->literal === 'ND' || $this->literal === 'SD',
        );
    }
}