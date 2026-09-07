<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AlumnoGrupo extends Pivot
{
    use HasFactory;

    protected $table = 'alumnos_grupos';

    protected $fillable = [
        'numero_alumno',
        'codigo_grupo',
        'inicial',
        'final',
        'periodo',
        'fecha_inscripcion',
        'estatus',
        'observaciones',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'fecha_inscripcion' => 'date',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'numero_alumno', 'numero_alumno');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'codigo_grupo')
            ->whereColumn('grupos.inicial', 'alumnos_grupos.inicial')
            ->whereColumn('grupos.final', 'alumnos_grupos.final')
            ->whereColumn('grupos.periodo', 'alumnos_grupos.periodo');
    }

    protected function estatusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estatus) {
                'INSCRITO' => 'Inscrito',
                'BAJA' => 'Baja',
                'CAMBIO_GRUPO' => 'Cambio de grupo',
                'REINSCRITO' => 'Reinscrito',
                default => $this->estatus,
            },
        );
    }
}