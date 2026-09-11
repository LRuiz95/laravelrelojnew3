<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Academia\Profesor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'asunto',
        'tipo_justificacion',
        'fecha_justificacion',
        'fecha_creacion',
        'empleado_id',
        'profesor_clave',
        'area_id',
        'puesto_id',
        'director_id',
        'numero_empleado',
        'motivo',
        'estado',
        'responsable_area_id',
        'created_by_user_id',
        'autorizado_at',
        'autorizado_por_user_id',
    ];

    protected $casts = [
        'fecha_justificacion' => 'date',
        'fecha_creacion' => 'datetime',
        'autorizado_at' => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'empleado_id');
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'profesor_clave', 'clave_profesor');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'director_id');
    }

    public function responsableArea(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsable_area_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por_user_id');
    }
}
