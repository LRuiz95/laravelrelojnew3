<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Puesto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PuestoController extends Controller
{
    public function index(): View
    {
        $puestos = Puesto::query()
            ->with(['area', 'empleados'])
            ->orderBy('identificador')
            ->get();

        return view('puestos.index', compact('puestos'));
    }

    public function create(): View
    {
        $areas = Area::query()->orderBy('identificador')->get(['id', 'identificador', 'descripcion']);

        return view('puestos.create', compact('areas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identificador' => ['required', 'string', 'max:50', 'unique:puestos,identificador'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'area_id' => ['nullable', 'exists:areas,id'],
        ], [
            'identificador.unique' => 'Ya existe un puesto con ese identificador.',
            'area_id.exists' => 'El área seleccionada no existe.',
        ]);

        Puesto::create($data);

        return redirect()->route('puestos.index')->with('success', 'Puesto creado correctamente.');
    }

    public function show(Puesto $puesto): View
    {
        $puesto->load(['area', 'empleados']);

        return view('puestos.show', compact('puesto'));
    }

    public function edit(Puesto $puesto): View
    {
        $puesto->load('area');

        $areas = Area::query()->orderBy('identificador')->get(['id', 'identificador', 'descripcion']);

        return view('puestos.edit', compact('puesto', 'areas'));
    }

    public function update(Request $request, Puesto $puesto): RedirectResponse
    {
        $data = $request->validate([
            'identificador' => ['required', 'string', 'max:50', 'unique:puestos,identificador,' . $puesto->id],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'area_id' => ['nullable', 'exists:areas,id'],
        ], [
            'identificador.unique' => 'Ya existe un puesto con ese identificador.',
            'area_id.exists' => 'El área seleccionada no existe.',
        ]);

        $puesto->update($data);

        return redirect()->route('puestos.index')->with('success', 'Puesto actualizado correctamente.');
    }

    public function destroy(Puesto $puesto): RedirectResponse
    {
        $puesto->delete();

        return redirect()->route('puestos.index')->with('success', 'Puesto eliminado correctamente.');
    }
}
