<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'identificador',
        'descripcion',
        'empleado_responsable_id',
    ];

    public function empleadoResponsable(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'empleado_responsable_id');
    }

    public function puestos(): HasMany
    {
        return $this->hasMany(Puesto::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
