<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ciclo extends Model
{
    use HasFactory;

    protected $table = 'ciclos';

    protected $fillable = [
        'inicial',
        'final',
        'periodo',
        'descripcion',
        'fecha_inicial',
        'fecha_final',
        'activo',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'fecha_inicial' => 'date',
        'fecha_final' => 'date',
        'activo' => 'boolean',
    ];

    public function periodos(): HasMany
    {
        return $this->hasMany(Curso::class, 'inicial')
            ->whereColumn('cursos.final', 'ciclos.final')
            ->whereColumn('cursos.periodo', 'ciclos.periodo');
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'inicial')
            ->whereColumn('cursos.final', 'ciclos.final')
            ->whereColumn('cursos.periodo', 'ciclos.periodo');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'inicial')
            ->whereColumn('grupos.final', 'ciclos.final')
            ->whereColumn('grupos.periodo', 'ciclos.periodo');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'inicial')
            ->whereColumn('horarios_det.final', 'ciclos.final')
            ->whereColumn('horarios_det.periodo', 'ciclos.periodo');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('inicial')->orderByDesc('final')->orderByDesc('periodo');
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->inicial}-{$this->final}-{$this->periodo}",
        );
    }

    protected function fechaInicialFormateada(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha_inicial?->format('d/m/Y'),
        );
    }

    protected function fechaFinalFormateada(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha_final?->format('d/m/Y'),
        );
    }

    public function getRouteKeyName(): string
    {
        return 'inicial'; // Se usará con composite key manual
    }
}