<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'inicial',
        'final',
        'periodo',
        'clave_curso',
        'nombre_curso',
        'nivel',
        'turno',
        'id_campus',
        'activo',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'activo' => 'boolean',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'inicial')
            ->whereColumn('ciclos.final', 'cursos.final')
            ->whereColumn('ciclos.periodo', 'cursos.periodo');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    public function materias(): HasMany
    {
        return $this->hasMany(CursoDet::class, 'curso_id', 'id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->where('inicial', $inicial)->where('final', $final)->where('periodo', $periodo);
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->nombre_curso} ({$this->clave_curso})",
        );
    }

    protected function cicloLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->inicial}-{$this->final}-{$this->periodo}",
        );
    }
}