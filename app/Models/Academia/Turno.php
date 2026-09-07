<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos';

    protected $fillable = [
        'turno',
        'descripcion',
        'descripcion_corta',
        'hora_inicio',
        'hora_fin',
        'orden',
        'activo',
    ];

    protected $casts = [
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'turno', 'turno');
    }

    public function sesionesBase(): HasMany
    {
        return $this->hasMany(SesionBase::class, 'turno', 'turno');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->descripcion_corta ?: $this->turno,
        );
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->descripcion} ({$this->turno})",
        );
    }
}