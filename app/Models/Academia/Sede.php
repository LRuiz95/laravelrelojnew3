<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Sede extends Model
{
    use HasFactory;

    protected $table = 'sedes';

    protected $fillable = [
        'id_campus',
        'descripcion',
        'direccion',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'id_campus', 'id_campus');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'id_campus', 'id_campus');
    }

    public function profesores(): HasMany
    {
        return $this->hasMany(Profesor::class, 'id_campus', 'id_campus');
    }

    public function alumnos(): HasMany
    {
        return $this->hasMany(Alumno::class, 'id_campus', 'id_campus');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->descripcion,
        );
    }
}