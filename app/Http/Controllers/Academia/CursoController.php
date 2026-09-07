<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Curso;
use App\Models\Academia\CursoDet;
use App\Models\Academia\Materia;
use App\Models\Academia\Sede;
use App\Models\Academia\Nivel;
use App\Models\Academia\Turno;
use App\Services\CicloActualService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CursoController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService
    ) {}

    public function index(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $cursos = Curso::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
            ->activo()
            ->with(['sede', 'materias.materia'])
            ->withCount('materias')
            ->orderBy('clave_curso')
            ->paginate(25);

        return view('academia.cursos.index', [
            'ciclo' => $ciclo,
            'cursos' => $cursos,
            'ciclos' => $this->cicloService->getAllForSelector(),
        ]);
    }

    public function show(Request $request, Curso $curso): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $curso->load(['sede', 'materias.materia']);
        
        $materias = CursoDet::where('curso_id', $curso->id)
            ->with('materia')
            ->activo()
            ->orderBy('semestre')
            ->get();

        return view('academia.cursos.show', [
            'ciclo' => $ciclo,
            'curso' => $curso,
            'materias' => $materias,
        ]);
    }

    public function create(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $sedes = Sede::activo()->get();
        $niveles = \App\Models\Academia\Nivel::activo()->get();
        $turnos = \App\Models\Academia\Turno::activo()->get();

        return view('academia.cursos.create', [
            'ciclo' => $ciclo,
            'sedes' => $sedes,
            'niveles' => $niveles,
            'turnos' => $turnos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $request->validate([
            'clave_curso' => 'required|string|max:20|unique:cursos,clave_curso',
            'nombre_curso' => 'required|string|max:100',
            'nivel' => 'required|string|max:10|exists:niveles,nivel',
            'turno' => 'required|string|max:10|exists:turnos,turno',
            'id_campus' => 'nullable|string|max:20|exists:sedes,id_campus',
        ]);

        Curso::create(array_merge($request->only([
            'clave_curso', 'nombre_curso', 'nivel', 'turno', 'id_campus'
        ]), [
            'inicial' => $ciclo->inicial,
            'final' => $ciclo->final,
            'periodo' => $ciclo->periodo,
        ]));

        return redirect()->route('academia.cursos.index')
            ->with('success', 'Curso creado correctamente');
    }

    public function edit(Curso $curso): View
    {
        $sedes = Sede::activo()->get();
        $niveles = \App\Models\Academia\Nivel::activo()->get();
        $turnos = \App\Models\Academia\Turno::activo()->get();

        return view('academia.cursos.edit', [
            'curso' => $curso,
            'sedes' => $sedes,
            'niveles' => $niveles,
            'turnos' => $turnos,
        ]);
    }

    public function update(Request $request, Curso $curso): RedirectResponse
    {
        $request->validate([
            'nombre_curso' => 'required|string|max:100',
            'nivel' => 'required|string|max:10|exists:niveles,nivel',
            'turno' => 'required|string|max:10|exists:turnos,turno',
            'id_campus' => 'nullable|string|max:20|exists:sedes,id_campus',
            'activo' => 'boolean',
        ]);

        $curso->update($request->only([
            'nombre_curso', 'nivel', 'turno', 'id_campus', 'activo'
        ]));

        return redirect()->route('academia.cursos.show', $curso)
            ->with('success', 'Curso actualizado correctamente');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        $curso->delete();
        return redirect()->route('academia.cursos.index')
            ->with('success', 'Curso eliminado');
    }

    // AJAX: Agregar materia al curso
    public function addMateria(Request $request, Curso $curso): JsonResponse
    {
        $request->validate([
            'clave_asignatura' => 'required|string|max:20|exists:materias,clave_asignatura',
            'semestre' => 'nullable|integer|min:1|max:12',
            'horas_teoria' => 'nullable|integer|min:0',
            'horas_practica' => 'nullable|integer|min:0',
            'tipo' => 'required|in:obligatoria,optativa',
        ]);

        CursoDet::updateOrCreate(
            ['curso_id' => $curso->id, 'clave_asignatura' => $request->get('clave_asignatura')],
            [
                'semestre' => $request->get('semestre'),
                'horas_teoria' => $request->get('horas_teoria', 0),
                'horas_practica' => $request->get('horas_practica', 0),
                'tipo' => $request->get('tipo'),
                'activo' => true,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function removeMateria(Curso $curso, CursoDet $materia): JsonResponse
    {
        $materia->delete();
        return response()->json(['success' => true]);
    }
}