<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    /**
     * Override dictionary key for BelongsTo relationships.
     * Grupo has composite PK but FK references use codigo_grupo.
     */
    public function getDictionaryKey(): mixed
    {
        return $this->codigo_grupo;
    }

    protected $fillable = [
        'codigo_grupo',
        'tipo_grupo',
        'cupo_maximo',
        'inicial',
        'final',
        'periodo',
        'grado',
        'grupo',
        'turno',
        'nivel',
        'inscritos',
        'id_campus',
        'clave_profesor_titular',
        'clave_profesor_suplente',
        'ciclo_cerrado',
        'carrera',
        'activo',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'grado' => 'integer',
        'inscritos' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Ciclo del grupo. Relación compuesta (inicial + final + periodo).
     * NO usar whereColumn en relaciones — falla en eager loading porque
     * la tabla padre no está en el scope de la query de Eloquent.
     * Se resuelve via accessor `cicloLabel`.
     */
    public function ciclo(): BelongsTo
    {
        // Simple: solo por inicial (80% de los casos — un ciclo = un año)
        // Para queries exactas, usar scopePorCiclo en Ciclo
        return $this->belongsTo(Ciclo::class, 'inicial', 'inicial');
    }

    /**
     * Resolve ciclo with all 3 columns (inicial + final + periodo).
     * Use this when the simple BelongsTo is not enough.
     */
    public function getCicloCompletoAttribute(): ?Ciclo
    {
        return Ciclo::query()
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->first();
    }

    public function nivelRel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel', 'nivel');
    }

    public function turnoRel(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno', 'turno');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    /**
     * Query builder for alumnos enrolled in this grupo.
     * NOT a relationship — Grupo has composite PK so BelongsToMany doesn't work.
     */
    public function alumnos()
    {
        return Alumno::query()
            ->select('alumnos.*')
            ->join('alumnos_grupos', function ($join) {
                $join->on('alumnos.numero_alumno', '=', 'alumnos_grupos.numero_alumno')
                    ->where('alumnos_grupos.codigo_grupo', $this->codigo_grupo)
                    ->where('alumnos_grupos.inicial', $this->inicial)
                    ->where('alumnos_grupos.final', $this->final)
                    ->where('alumnos_grupos.periodo', $this->periodo);
            })
            ->selectRaw('
                alumnos_grupos.fecha_inscripcion as pivot_fecha_inscripcion,
                alumnos_grupos.estatus as pivot_estatus,
                alumnos_grupos.observaciones as pivot_observaciones
            ');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDet::class, 'codigo_grupo');
    }

    /**
     * Horarios del grupo con composite key (inicial + final + periodo).
     * NO usar whereColumn en hasMany — falla en eager loading.
     */
    public function getHorariosCompletosAttribute()
    {
        return HorarioDet::query()
            ->where('codigo_grupo', $this->codigo_grupo)
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->orderBy('dia')
            ->orderBy('sesion')
            ->get();
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->where('inicial', $inicial)->where('final', $final)->where('periodo', $periodo);
    }

    public function scopePorNivelTurno($query, string $nivel, string $turno)
    {
        return $query->where('nivel', $nivel)
            ->whereRaw('UPPER(turno) LIKE ?', [strtoupper(substr($turno, 0, 1)) . '%']);
    }

    /**
     * Descompone el código académico, por ejemplo 24MTC-1-I-1B-3C.
     */
    protected function codigoGrupoPartes(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $partes = explode('-', strtoupper(trim((string) $this->codigo_grupo)));
                $cabecera = array_shift($partes) ?? '';
                $resultado = [
                    'anio_plan' => null,
                    'nivel' => null,
                    'sede' => $partes[0] ?? null,
                    'modelo' => $partes[1] ?? null,
                    'grado_grupo' => $partes[2] ?? null,
                    'nivel_superior' => null,
                ];

                if (preg_match('/^(\d{2})([A-Z]+)$/', $cabecera, $coincidencias)) {
                    $resultado['anio_plan'] = (int) $coincidencias[1];
                    $resultado['nivel'] = $coincidencias[2];
                }

                if (isset($partes[3]) && strtoupper($partes[3]) === '3C') {
                    $resultado['nivel_superior'] = '3C';
                }

                return $resultado;
            },
        );
    }

    protected function turnoBase(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->turno ? strtoupper(substr(trim($this->turno), 0, 1)) : null,
        );
    }

    protected function turnoNombre(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->turno_base) {
                'M' => 'Matutino',
                'V' => 'Vespertino',
                default => $this->turno ?: 'Sin turno',
            },
        );
    }

    protected function modalidadNombre(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->codigo_grupo_partes['modelo'] ?? null) {
                'B' => 'Bilingüe',
                'D' => 'Despresurizado',
                'I' => 'Intensivo',
                'M' => 'Mixto',
                default => 'Tradicional',
            },
        );
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "Grupo {$this->codigo_grupo} - {$this->grado}° {$this->turnoRel?->descripcion_corta}",
        );
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->ciclo->label} - {$this->label}",
        );
    }
}