<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Plan;
use App\Models\Academia\Materia;
use App\Models\Academia\MetodoEval;
use App\Models\Academia\Nivel;
use App\Models\Academia\Contrato;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Plan::with(['materias' => fn ($q) => $q->activa()])
            ->withCount('materias');

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->get('nivel'));
        }

        if ($request->filled('buscar')) {
            $buscar = $request->get('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_plan', 'like', "%{$buscar}%")
                  ->orWhere('id_plan', 'like', "%{$buscar}%");
            });
        }

        $planes = $query->with('nivelRel')
            ->orderBy('nivel')
            ->orderBy('nombre_plan')
            ->paginate(25);

        return view('academia.planes.index', [
            'planes' => $planes,
            'niveles' => \App\Models\Academia\Nivel::activo()->get(),
        ]);
    }

    public function show(Request $request, Plan $plan): View
    {
        $plan->load(['materias' => fn ($q) => $q->activa()->orderBy('semestre')]);

        $materias = $plan->materias->groupBy('semestre');

        return view('academia.planes.show', [
            'plan' => $plan,
            'materiasPorSemestre' => $materias,
        ]);
    }

    public function create(): View
    {
        $niveles = Nivel::activo()->get();
        return view('academia.planes.create', compact('niveles'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'id_plan' => 'required|integer|unique:planes,id_plan',
            'nombre_plan' => 'required|string|max:100',
            'nivel' => 'required|string|max:10|exists:niveles,nivel',
            'modalidad' => 'nullable|string|max:20',
            'duracion_semestres' => 'nullable|integer|min:1|max:12',
        ]);

        Plan::create($request->only([
            'id_plan', 'nombre_plan', 'nivel', 'modalidad', 'duracion_semestres'
        ]));

        return redirect()->route('academia.planes.index')
            ->with('success', 'Plan creado correctamente');
    }

    public function edit(Plan $plan): View
    {
        $niveles = Nivel::activo()->get();
        return view('academia.planes.edit', [
            'plan' => $plan,
            'niveles' => $niveles,
        ]);
    }

    public function update(Request $request, Plan $plan): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'nombre_plan' => 'required|string|max:100',
            'nivel' => 'required|string|max:10|exists:niveles,nivel',
            'modalidad' => 'nullable|string|max:20',
            'duracion_semestres' => 'nullable|integer|min:1|max:12',
            'activo' => 'boolean',
        ]);

        $plan->update($request->only([
            'nombre_plan', 'nivel', 'modalidad', 'duracion_semestres', 'activo'
        ]));

        return redirect()->route('academia.planes.show', $plan)
            ->with('success', 'Plan actualizado correctamente');
    }

    public function destroy(Plan $plan): \Illuminate\Http\RedirectResponse
    {
        $plan->delete();
        return redirect()->route('academia.planes.index')
            ->with('success', 'Plan eliminado');
    }

    // AJAX: Materias por plan
    public function materias(Plan $plan): JsonResponse
    {
        $materias = $plan->materias()
            ->activa()
            ->orderBy('semestre')
            ->orderBy('nombre_asignatura')
            ->get(['id', 'clave_asignatura', 'nombre_asignatura', 'nombre_corto', 'semestre', 'horas_teoria', 'horas_practica', 'creditos', 'tipo']);

        return response()->json($materias);
    }

    // AJAX: Métodos de evaluación
    public function metodosEval(): JsonResponse
    {
        $metodos = \App\Models\Academia\MetodoEval::activo()
            ->get(['id_eval', 'nombre_corto', 'descripcion', 'tipo_examen', 'es_final']);

        return response()->json($metodos);
    }

    // AJAX: Niveles
    public function niveles(): JsonResponse
    {
        $niveles = Nivel::activo()->get(['nivel', 'descripcion']);
        return response()->json($niveles);
    }
}