<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteAsistencia extends Model
{
    use HasFactory;

    protected $table = 'docentes_asistencias';

    protected $fillable = [
        'inicial',
        'final',
        'periodo',
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
        'dia' => 'integer',
        'sesion' => 'integer',
        'fecha' => 'date',
    ];
}
