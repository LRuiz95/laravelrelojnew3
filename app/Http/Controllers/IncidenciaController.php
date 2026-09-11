<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Academia\Profesor;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Incidencia;
use App\Models\Puesto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class IncidenciaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $q = trim((string) $request->query('q', ''));

        $incidencias = Incidencia::query()
            ->with(['empleado', 'profesor', 'area', 'puesto', 'director', 'responsableArea'])
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($innerQuery) use ($q) {
                    $innerQuery->where('asunto', 'like', "%{$q}%")
                        ->orWhere('tipo_justificacion', 'like', "%{$q}%")
                        ->orWhere('motivo', 'like', "%{$q}%")
                        ->orWhere('numero_empleado', 'like', "%{$q}%");
                });
            })
            ->latest('fecha_creacion')
            ->paginate(20)
            ->withQueryString();

        return view('incidencias.index', compact('incidencias', 'estado', 'q'));
    }

    public function create(): View
    {
        $empleados = Employee::query()
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name', 'user_id', 'area_id', 'puesto_id']);

        $profesores = Profesor::query()
            ->orderByRaw('LOWER(nombre_profesor)')
            ->get(['clave_profesor', 'nombre_profesor', 'paterno', 'materno', 'area_id', 'puesto_id']);

        $areas = Area::query()->orderBy('identificador')->get(['id', 'identificador', 'descripcion', 'empleado_responsable_id']);
        $puestos = Puesto::query()->with('area')->orderBy('identificador')->get(['id', 'identificador', 'descripcion', 'area_id']);
        $directores = Employee::query()->orderByRaw('LOWER(name)')->get(['id', 'name', 'user_id']);

        return view('incidencias.create', compact('empleados', 'profesores', 'areas', 'puestos', 'directores'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo_persona' => ['required', 'in:empleado,profesor'],
            'empleado_id' => ['nullable', 'required_if:tipo_persona,empleado', 'exists:employees,id'],
            'profesor_clave' => ['nullable', 'required_if:tipo_persona,profesor', 'exists:profesores,clave_profesor'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'puesto_id' => ['nullable', 'exists:puestos,id'],
            'director_id' => ['nullable', 'exists:employees,id'],
            'asunto' => ['required', 'string', 'max:150'],
            'tipo_justificacion' => ['required', 'string', 'max:80'],
            'fecha_justificacion' => ['required', 'date'],
            'motivo' => ['required', 'string'],
        ], [
            'empleado_id.required_if' => 'Debe seleccionar un empleado cuando el tipo de persona es empleado.',
            'profesor_clave.required_if' => 'Debe seleccionar un profesor cuando el tipo de persona es profesor.',
        ]);

        $empleado = null;
        $profesor = null;

        if ($data['tipo_persona'] === 'empleado') {
            $empleado = Employee::query()->findOrFail($data['empleado_id']);
            $data['numero_empleado'] = $empleado->numero_empleado ?? $empleado->user_id;
            $data['area_id'] = $data['area_id'] ?? $empleado->area_id;
            $data['puesto_id'] = $data['puesto_id'] ?? $empleado->puesto_id;
            $data['empleado_id'] = $empleado->id;
            $data['profesor_clave'] = null;
        } else {
            $profesor = Profesor::query()->findOrFail($data['profesor_clave']);
            $data['numero_empleado'] = $profesor->clave_profesor;
            $data['area_id'] = $data['area_id'] ?? $profesor->area_id;
            $data['puesto_id'] = $data['puesto_id'] ?? $profesor->puesto_id;
            $data['empleado_id'] = null;
            $data['director_id'] = $data['director_id'] ?? $profesor->director_id;
        }

        $area = $data['area_id'] ? Area::query()->find($data['area_id']) : null;

        Incidencia::create([
            'asunto' => $data['asunto'],
            'tipo_justificacion' => $data['tipo_justificacion'],
            'fecha_justificacion' => $data['fecha_justificacion'],
            'empleado_id' => $data['empleado_id'],
            'profesor_clave' => $data['profesor_clave'],
            'area_id' => $data['area_id'],
            'puesto_id' => $data['puesto_id'],
            'director_id' => $data['director_id'] ?? null,
            'numero_empleado' => $data['numero_empleado'] ?? null,
            'motivo' => $data['motivo'],
            'estado' => 'pendiente',
            'responsable_area_id' => $area?->empleado_responsable_id,
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('incidencias.index')->with('success', 'Incidencia registrada correctamente.');
    }

    public function updateStatus(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', 'in:pendiente,aprobada,rechazada'],
        ]);

        $incidencia->update([
            'estado' => $data['estado'],
            'autorizado_por_user_id' => auth()->id(),
            'autorizado_at' => now(),
        ]);

        return Redirect::route('incidencias.index')->with('success', 'Estado de incidencia actualizado correctamente.');
    }
}

