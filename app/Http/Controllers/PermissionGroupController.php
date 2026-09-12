<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PermissionGroup;
use App\Models\Academia\Profesor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionGroupController extends Controller
{
    public function index(): View
    {
        $permissionGroups = PermissionGroup::query()
            ->withCount('permissions')
            ->with(['employees', 'profesores'])
            ->orderBy('name')
            ->get();

        return view('permission-groups.index', compact('permissionGroups'));
    }

    public function create(): View
    {
        return view('permission-groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        PermissionGroup::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('permission-groups.index')->with('success', 'Grupo de permisos creado correctamente.');
    }

    public function edit(PermissionGroup $permissionGroup): View
    {
        return view('permission-groups.edit', compact('permissionGroup'));
    }

    public function update(Request $request, PermissionGroup $permissionGroup): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $permissionGroup->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('permission-groups.index')->with('success', 'Grupo de permisos actualizado correctamente.');
    }

    public function destroy(PermissionGroup $permissionGroup): RedirectResponse
    {
        $permissionGroup->delete();

        return redirect()->route('permission-groups.index')->with('success', 'Grupo de permisos eliminado correctamente.');
    }

    public function assignEmployees(PermissionGroup $permissionGroup): View
    {
        $employees = Employee::query()
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name', 'user_id', 'area_id', 'puesto_id']);

        $selectedEmployeeIds = $permissionGroup->employees()->pluck('employees.id')->all();

        return view('permission-groups.assign-employees', compact('permissionGroup', 'employees', 'selectedEmployeeIds'));
    }

    public function saveEmployees(Request $request, PermissionGroup $permissionGroup): RedirectResponse
    {
        $employeeIds = array_map('intval', $request->input('employee_ids', []));

        $permissionGroup->employees()->sync($employeeIds);

        return redirect()->route('permission-groups.index')->with('success', 'Asignación de empleados actualizada correctamente.');
    }

    public function assignProfesores(PermissionGroup $permissionGroup): View
    {
        $profesores = Profesor::query()
            ->orderBy('paterno')
            ->orderBy('materno')
            ->orderBy('nombre_profesor')
            ->get(['clave_profesor', 'nombre_profesor', 'paterno', 'materno', 'departamento']);

        $selectedProfesorKeys = $permissionGroup->profesores()->pluck('profesores.clave_profesor')->all();

        return view('permission-groups.assign-profesores', compact('permissionGroup', 'profesores', 'selectedProfesorKeys'));
    }

    public function saveProfesores(Request $request, PermissionGroup $permissionGroup): RedirectResponse
    {
        $profesorKeys = $request->input('profesor_keys', []);

        $permissionGroup->profesores()->sync($profesorKeys);

        return redirect()->route('permission-groups.index')->with('success', 'Asignación de profesores actualizada correctamente.');
    }
}
