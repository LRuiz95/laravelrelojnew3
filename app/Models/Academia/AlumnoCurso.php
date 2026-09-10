<?php

declare(strict_types=1);

namespace App\Models\Academia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumnoCurso extends Model
{
    use HasFactory;

    protected $table = 'alumnos_cursos';

    protected $fillable = [
        'inicial', 'final', 'periodo', 'codigo_curso', 'numero_alumno',
        'status', 'id_plan', 'id_tipoeval', 'id_etapa', 'clave_asignatura',
        'version', 'tipoexamen', 'web', 'web_operacion',
    ];

    protected $casts = [
        'inicial' => 'integer',
        'final' => 'integer',
        'periodo' => 'integer',
        'numero_alumno' => 'integer',
        'tipoexamen' => 'integer',
        'web' => 'boolean',
    ];
}
