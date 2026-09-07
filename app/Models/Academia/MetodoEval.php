<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class MetodoEval extends Model
{
    use HasFactory;

    protected $table = 'metodos_eval';

    protected $fillable = [
        'id_eval',
        'nombre_corto',
        'descripcion',
        'tipo_examen',
        'orden',
        'es_final',
        'activo',
    ];

    protected $casts = [
        'tipo_examen' => 'integer',
        'orden' => 'integer',
        'es_final' => 'boolean',
        'activo' => 'boolean',
    ];

    public function kardex(): HasMany
    {
        return $this->hasMany(AlumnoKardex::class, 'id_eval', 'id_eval');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopeFinales($query)
    {
        return $query->where('es_final', true);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nombre_corto,
        );
    }

    protected function tipoExamenTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->tipo_examen) {
                0 => 'Parcial',
                1 => 'Final',
                2 => 'Extraordinario',
                3 => 'Repetición',
                default => 'Desconocido',
            },
        );
    }
}