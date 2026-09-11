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
use App\Http\Requests\CicloFormRequest;
use App\Exceptions\NoCiclosConfiguradosException;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CicloController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService
    ) {}

    public function index(Request $request): View
    {
        $ciclos = Ciclo::query()
            ->latest('inicial')
            ->latest('final')
            ->latest('periodo')
            ->paginate(20);

        foreach ($ciclos as $ciclo) {
            $ciclo->setAttribute('grupos_count', Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->count());
            $ciclo->setAttribute('horarios_count', HorarioDet::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->count());
            $ciclo->setAttribute('cursos_count', Curso::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->count());
        }

        try {
            $cicloActual = $this->cicloService->resolve($request);
        } catch (NoCiclosConfiguradosException $e) {
            return view('academia.empty-ciclos');
        }

        return view('academia.ciclos.index', [
            'ciclo' => $cicloActual,
            'ciclos' => $ciclos,
        ]);
    }

    public function show(Request $request, Ciclo $ciclo): View
    {
        // Estadísticas del ciclo
        $stats = [
            'grupos' => Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->count(),
            'alumnos' => \App\Models\Academia\Alumno::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->activo()->count(),
            'profesores' => \App\Models\Academia\Profesor::whereHas('horarios', fn ($q) => $q->where('inicial', $ciclo->inicial)
                ->where('final', $ciclo->final)
                ->where('periodo', $ciclo->periodo)
                ->where('activo', true))->count(),
            'horarios' => HorarioDet::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->count(),
            'cursos' => Curso::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->count(),
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
        $request->validate((new CicloFormRequest)->rules(), (new CicloFormRequest)->messages());

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
        $request->validate((new CicloFormRequest)->rules(), (new CicloFormRequest)->messages());

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