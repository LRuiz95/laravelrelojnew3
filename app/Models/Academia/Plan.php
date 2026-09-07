<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'id_plan',
        'nombre_plan',
        'nivel',
        'modalidad',
        'duracion_semestres',
        'activo',
    ];

    protected $casts = [
        'id_plan' => 'integer',
        'duracion_semestres' => 'integer',
        'activo' => 'boolean',
    ];

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class, 'id_plan', 'id_plan');
    }

    public function nivelRel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel', 'nivel');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorNivel($query, string $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->nombre_plan} ({$this->id_plan})",
        );
    }
}