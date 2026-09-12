<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $modules = Module::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $permissionGroups = PermissionGroup::query()->orderBy('name')->get();

        return view('permissions.index', compact('modules', 'permissionGroups'));
    }

    public function create(): View
    {
        $modules = Module::query()->orderBy('name')->get();

        return view('permissions.create', compact('modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'slug' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Permission::create($data);

        return redirect()->route('permissions.index')->with('success', 'Permiso creado correctamente.');
    }

    public function edit(Permission $permission): View
    {
        $modules = Module::query()->orderBy('name')->get();

        return view('permissions.edit', compact('permission', 'modules'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'slug' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission->update($data);

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado correctamente.');
    }

    public function assignGroups(Permission $permission): View
    {
        $groups = PermissionGroup::query()->orderBy('name')->get();
        $selectedGroupIds = $permission->groups()->pluck('permission_groups.id')->all();

        return view('permissions.assign-groups', compact('permission', 'groups', 'selectedGroupIds'));
    }

    public function saveGroups(Request $request, Permission $permission): RedirectResponse
    {
        $groupIds = array_map('intval', $request->input('group_ids', []));

        $permission->groups()->sync($groupIds);

        return redirect()->route('permissions.index')->with('success', 'Grupos asignados correctamente.');
    }
}
