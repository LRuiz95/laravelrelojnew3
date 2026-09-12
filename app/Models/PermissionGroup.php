<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_group_permissions');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_permission_groups');
    }

    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Academia\Profesor::class,
            'profesor_permission_groups',
            'permission_group_id',
            'profesor_clave_profesor',
            'id',
            'clave_profesor'
        );
    }
}
