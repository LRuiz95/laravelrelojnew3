<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SesionBase extends Model
{
    use HasFactory;

    protected $table = 'sesiones_base';

    protected $fillable = [
        'nivel',
        'turno',
        'sesion',
        'hora_inicio',
        'hora_fin',
        'receso',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'sesion' => 'integer',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
        'receso' => 'boolean',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

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
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopePorNivelTurno($query, string $nivel, string $turno)
    {
        return $query->where('nivel', $nivel)->where('turno', $turno);
    }

    protected function esReceso(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->receso,
        );
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->descripcion ?? "Sesión {$this->sesion}",
        );
    }
}