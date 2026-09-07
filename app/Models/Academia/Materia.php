<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = [
        'clave_asignatura',
        'id_plan',
        'nombre_asignatura',
        'nombre_corto',
        'semestre',
        'horas_teoria',
        'horas_practica',
        'creditos',
        'tipo',
        'activa',
    ];

    protected $casts = [
        'id_plan' => 'integer',
        'semestre' => 'integer',
        'horas_teoria' => 'integer',
        'horas_practica' => 'integer',
        'creditos' => 'integer',
        'activa' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id_plan');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function cursosDet(): HasMany
    {
        return $this->hasMany(CursoDet::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(AlumnoKardex::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function scopeActiva($query)
    {
        return $query->where('activa', true);
    }

    public function scopePorPlan($query, int $idPlan)
    {
        return $query->where('id_plan', $idPlan);
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

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nombre_corto ?: $this->nombre_asignatura,
        );
    }
}