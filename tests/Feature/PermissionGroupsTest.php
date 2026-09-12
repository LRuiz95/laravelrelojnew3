<?php

namespace Tests\Feature;

use App\Models\Academia\Profesor;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionGroupPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_module_permission_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)
            ->get(route('incidencias.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('attendances.export'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertForbidden();
    }

    public function test_user_can_resolve_permissions_from_assigned_employee_and_professor_groups(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $module = Module::create([
            'slug' => 'incidencias',
            'name' => 'Incidencias',
            'description' => 'Módulo de incidencias',
            'active' => true,
        ]);

        $viewPermission = Permission::create([
            'module_id' => $module->id,
            'slug' => 'incidencias.view',
            'action' => 'view',
            'name' => 'Ver incidencias',
        ]);

        $employeeGroup = PermissionGroup::create([
            'name' => 'Empleados RH',
            'description' => 'Permisos de RH',
            'is_default' => false,
        ]);

        $profesorGroup = PermissionGroup::create([
            'name' => 'Profesores Académicos',
            'description' => 'Permisos académicos',
            'is_default' => false,
        ]);

        PermissionGroupPermission::create([
            'permission_group_id' => $employeeGroup->id,
            'permission_id' => $viewPermission->id,
        ]);

        $employee = Employee::create([
            'user_id' => 'E-100',
            'name' => 'Empleado A',
            'type' => 'biometric',
            'auth_user_id' => $user->id,
        ]);

        $employee->permissionGroups()->attach($employeeGroup->id);

        $profesor = Profesor::create([
            'clave_profesor' => 'P-100',
            'nombre_profesor' => 'Profesor A',
            'paterno' => 'Pérez',
            'materno' => 'López',
            'auth_user_id' => $user->id,
        ]);

        $profesor->permissionGroups()->attach($profesorGroup->id);

        $this->assertTrue($user->hasModulePermission('incidencias', 'view'));
        $this->assertTrue($user->hasPermission('incidencias', 'view'));
        $this->assertFalse($user->hasPermission('empleados', 'create'));
        $this->assertFalse($user->canAccessModule('empleados', 'create'));
    }
}
