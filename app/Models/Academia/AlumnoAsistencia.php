<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumnoAsistencia extends Model
{
    use HasFactory;

    protected $table = 'alumnos_asistencias';

    protected $fillable = [
        'inicial',
        'final',
        'periodo',
        'numero_alumno',
        'codigo_grupo',
        'clave_profesor',
        'clave_asignatura',
        'dia',
        'sesion',
        'fecha',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'numero_alumno' => 'integer',
        'dia' => 'integer',
        'sesion' => 'integer',
        'fecha' => 'date',
    ];
}
