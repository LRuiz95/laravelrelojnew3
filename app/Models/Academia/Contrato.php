<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = [
        'contrato',
        'descripcion',
        'tipo_personal',
        'tiene_antiguedad',
        'tiene_prestaciones',
        'activo',
    ];

    protected $casts = [
        'tiene_antiguedad' => 'boolean',
        'tiene_prestaciones' => 'boolean',
        'activo' => 'boolean',
    ];

    public function empleados(): HasMany
    {
        return $this->hasMany(\App\Models\Employee::class, 'contrato', 'contrato');
    }

    public function profesores(): HasMany
    {
        return $this->hasMany(Profesor::class, 'contrato', 'contrato');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_personal', $tipo);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contrato,
        );
    }
}