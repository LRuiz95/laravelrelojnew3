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

    /**
     * Relaciones hasMany simples (sin whereColumn — falla en eager loading).
     * Para composite key, usar los accessors get*CompletosAttribute.
     */
    public function periodos(): HasMany
    {
        return $this->hasMany(Curso::class, 'inicial');
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'inicial');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'inicial');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'inicial');
    }

    /**
     * Cursos con composite key (inicial + final + periodo).
     */
    public function getCursosCompletosAttribute()
    {
        return Curso::query()
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->get();
    }

    /**
     * Grupos con composite key (inicial + final + periodo).
     */
    public function getGruposCompletosAttribute()
    {
        return Grupo::query()
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->get();
    }

    /**
     * Horarios con composite key (inicial + final + periodo).
     */
    public function getHorariosCompletosAttribute()
    {
        return HorarioDet::query()
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->get();
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