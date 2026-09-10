<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class HorarioDet extends Model
{
    use HasFactory;

    protected $table = 'horarios_det';

    protected $fillable = [
        'inicial',
        'final',
        'periodo',
        'codigo_grupo',
        'clave_profesor',
        'clave_asignatura',
        'dia',
        'sesion',
        'horas_teoria_practica',
        'id_campus',
        'edificio',
        'aula',
        'origen_horario',
        'horas_semanales',
        'activo',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'dia' => 'integer',
        'sesion' => 'integer',
        'horas_teoria_practica' => 'decimal:2',
        'horas_semanales' => 'integer',
        'activo' => 'boolean',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'inicial', 'inicial');
    }

    /**
     * Resolve ciclo with all 3 columns (inicial + final + periodo).
     */
    public function getCicloCompletoAttribute(): ?Ciclo
    {
        return Ciclo::query()
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->first();
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'codigo_grupo', 'codigo_grupo');
    }

    /**
     * Resolve grupo with composite key (codigo_grupo + inicial + final + periodo).
     */
    public function getGrupoCompletoAttribute(): ?Grupo
    {
        return Grupo::query()
            ->where('codigo_grupo', $this->codigo_grupo)
            ->where('inicial', $this->inicial)
            ->where('final', $this->final)
            ->where('periodo', $this->periodo)
            ->first();
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'clave_profesor', 'clave_profesor');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'clave_asignatura', 'clave_asignatura');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_campus', 'id_campus');
    }

    public function sesionBase(): BelongsTo
    {
        return $this->belongsTo(SesionBase::class, 'sesion', 'sesion');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCiclo($query, int $inicial, int $final, int $periodo)
    {
        return $query->where('inicial', $inicial)->where('final', $final)->where('periodo', $periodo);
    }

    public function scopePorProfesor($query, string $claveProfesor)
    {
        return $query->where('clave_profesor', $claveProfesor);
    }

    public function scopePorGrupo($query, string $codigoGrupo)
    {
        return $query->where('codigo_grupo', $codigoGrupo);
    }

    public function scopePorDia($query, int $dia)
    {
        return $query->where('dia', $dia);
    }

    public function scopePorSesion($query, int $sesion)
    {
        return $query->where('sesion', $sesion);
    }

    protected function diaNombre(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->dia) {
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
                default => "Día {$this->dia}",
            },
        );
    }

    protected function diaCorto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->dia) {
                1 => 'Lun', 2 => 'Mar', 3 => 'Mié',
                4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom',
                default => "D{$this->dia}",
            },
        );
    }

    protected function esPTC(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->origen_horario === 'HD',
        );
    }

    protected function esPA(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->origen_horario === 'CA',
        );
    }

    protected function ubicacion(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->edificio} {$this->aula}"),
        );
    }

    protected function tipoClase(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->origen_horario) {
                'HD' => 'PTC',
                'CA' => 'PA',
                default => $this->origen_horario ?? '—',
            },
        );
    }
}