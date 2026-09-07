<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Nivel extends Model
{
    use HasFactory;

    protected $table = 'niveles';

    protected $fillable = [
        'nivel',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'nivel', 'nivel');
    }

    public function planes(): HasMany
    {
        return $this->hasMany(Plan::class, 'nivel', 'nivel');
    }

    public function sesionesBase(): HasMany
    {
        return $this->hasMany(SesionBase::class, 'nivel', 'nivel');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->nivel} - {$this->descripcion}",
        );
    }
}