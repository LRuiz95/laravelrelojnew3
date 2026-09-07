<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Grupo;
use App\Models\Academia\HorarioDet;
use App\Models\Academia\Alumno;
use App\Models\Academia\Curso;
use App\Services\CicloActualService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CicloController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService
    ) {}

    public function index(Request $request): View
    {
        $ciclos = Ciclo::withCount(['grupos', 'horarios', 'cursos'])
            ->latest('inicial')
            ->latest('final')
            ->latest('periodo')
            ->paginate(20);

        return view('academia.ciclos.index', [
            'ciclo' => $this->cicloService->resolve($request),
            'ciclos' => $ciclos,
        ]);
    }

    public function show(Request $request, Ciclo $ciclo): View
    {
        $ciclo->loadCount(['grupos', 'horarios', 'cursos', 'alumnos']);
        
        // Estadísticas del ciclo
        $stats = [
            'grupos' => $ciclo->grupos_count,
            'alumnos' => \App\Models\Academia\Alumno::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->activo()->count(),
            'profesores' => \App\Models\Academia\Profesor::whereHas('horarios', fn ($q) => $q->where('inicial', $ciclo->inicial)
                ->where('final', $ciclo->final)
                ->where('periodo', $ciclo->periodo)
                ->where('activo', true))->count(),
            'horarios' => $ciclo->horarios_count,
            'cursos' => $ciclo->cursos_count,
        ];

        // Grupos por nivel/turno
        $gruposPorNivelTurno = \App\Models\Academia\Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
            ->activo()
            ->selectRaw('nivel, turno, COUNT(*) as total')
            ->groupBy('nivel', 'turno')
            ->orderBy('nivel')
            ->orderBy('turno')
            ->get();

        return view('academia.ciclos.show', [
            'ciclo' => $ciclo,
            'stats' => $stats,
            'gruposPorNivelTurno' => $gruposPorNivelTurno,
        ]);
    }

    public function create(): View
    {
        return view('academia.ciclos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'inicial' => 'required|integer|min:2000|max:2100',
            'final' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|integer|min:1|max:4',
            'descripcion' => 'nullable|string|max:100',
            'fecha_inicial' => 'nullable|date',
            'fecha_final' => 'nullable|date|after:fecha_inicial',
        ]);

        Ciclo::create($request->only([
            'inicial', 'final', 'periodo', 'descripcion', 'fecha_inicial', 'fecha_final', 'activo'
        ]));

        return redirect()->route('academia.ciclos.index')
            ->with('success', 'Ciclo creado correctamente');
    }

    public function edit(Ciclo $ciclo): View
    {
        return view('academia.ciclos.edit', compact('ciclo'));
    }

    public function update(Request $request, Ciclo $ciclo): RedirectResponse
    {
        $request->validate([
            'descripcion' => 'nullable|string|max:100',
            'fecha_inicial' => 'nullable|date',
            'fecha_final' => 'nullable|date|after:fecha_inicial',
            'activo' => 'boolean',
        ]);

        $ciclo->update($request->only(['descripcion', 'fecha_inicial', 'fecha_final', 'activo']));

        return redirect()->route('academia.ciclos.show', $ciclo)
            ->with('success', 'Ciclo actualizado correctamente');
    }

    public function destroy(Ciclo $ciclo): RedirectResponse
    {
        $ciclo->delete();
        return redirect()->route('academia.ciclos.index')
            ->with('success', 'Ciclo eliminado');
    }

    public function setActivo(Request $request, Ciclo $ciclo): JsonResponse
    {
        $ciclo->update(['activo' => $request->boolean('activo')]);
        return response()->json(['success' => true, 'activo' => $ciclo->activo]);
    }
}