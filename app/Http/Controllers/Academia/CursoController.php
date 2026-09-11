<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Curso;
use App\Models\Academia\CursoDet;
use App\Models\Academia\Materia;
use App\Models\Academia\Alumno;
use App\Models\Academia\Sede;
use App\Models\Academia\Nivel;
use App\Models\Academia\Turno;
use App\Services\CicloActualService;
use App\Http\Requests\CursoFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->with(['sede', 'nivelRel', 'plan.nivelRel', 'materia', 'profesor', 'materias.materia'])
            ->withCount('materias')
            ->orderBy('clave_curso')
            ->paginate((int) $request->query('per_page', 25));

        $cursos->each(function (Curso $curso): void {
            $materia = $curso->materia ?? $curso->materias->first()?->materia;
            $curso->setAttribute('docentes', $materia ? DB::table('horarios_det as h')
                ->join('profesores as p', 'p.clave_profesor', '=', 'h.clave_profesor')
                ->where('h.inicial', $curso->inicial)
                ->where('h.final', $curso->final)
                ->where('h.periodo', $curso->periodo)
                ->where('h.clave_asignatura', $materia->clave_asignatura)
                ->when($curso->codigo_grupo, fn ($query) => $query->where('h.codigo_grupo', $curso->codigo_grupo))
                ->where('h.activo', true)
                ->select('p.clave_profesor', 'p.nombre_profesor', 'p.paterno', 'p.materno')
                ->selectRaw("TRIM(CONCAT(COALESCE(p.paterno, ''), ' ', COALESCE(p.materno, ''), ' ', COALESCE(p.nombre_profesor, ''))) as nombre_completo")
                ->distinct()
                ->orderBy('nombre_completo')
                ->get() : collect());
            $curso->setAttribute('alumnos_count', DB::table('alumnos_cursos')
                ->where('inicial', $curso->inicial)
                ->where('final', $curso->final)
                ->where('periodo', $curso->periodo)
                ->where('codigo_curso', $curso->clave_curso)
                ->distinct('numero_alumno')
                ->count('numero_alumno') ?: ($materia ? DB::table('alumnos_grupos as ag')
                ->join('horarios_det as h', function ($join) use ($curso, $materia) {
                    $join->on('h.codigo_grupo', '=', 'ag.codigo_grupo')
                        ->on('h.inicial', '=', 'ag.inicial')
                        ->on('h.final', '=', 'ag.final')
                        ->on('h.periodo', '=', 'ag.periodo')
                        ->where('h.inicial', $curso->inicial)
                        ->where('h.final', $curso->final)
                        ->where('h.periodo', $curso->periodo)
                        ->where('h.clave_asignatura', $materia->clave_asignatura);
                    if ($curso->codigo_grupo) {
                        $join->where('h.codigo_grupo', $curso->codigo_grupo);
                    }
                })
                ->where('ag.inicial', $curso->inicial)
                ->where('ag.final', $curso->final)
                ->where('ag.periodo', $curso->periodo)
                ->where('ag.estatus', 'INSCRITO')
                ->when($curso->codigo_grupo, fn ($query) => $query->where('ag.codigo_grupo', $curso->codigo_grupo))
                ->distinct('ag.numero_alumno')
                ->count('ag.numero_alumno') : 0));
        });

        return view('academia.cursos.index', [
            'ciclo' => $ciclo,
            'cursos' => $cursos,
            'ciclos' => $this->cicloService->getAllForSelector(),
        ]);
    }

    public function show(Request $request, Curso $curso): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $curso->load(['sede', 'nivelRel', 'turnoRel', 'plan.nivelRel', 'materia', 'profesor', 'materias.materia'])
            ->loadCount('materias');
        
        $materias = CursoDet::where('curso_id', $curso->id)
            ->with('materia')
            ->activo()
            ->orderBy('semestre')
            ->get();

        $materia = $curso->materia ?? $materias->first()?->materia;
        $docentes = $curso->profesor ? collect([$curso->profesor]) : ($materia ? DB::table('horarios_det as h')
            ->join('profesores as p', 'p.clave_profesor', '=', 'h.clave_profesor')
            ->where('h.inicial', $curso->inicial)
            ->where('h.final', $curso->final)
            ->where('h.periodo', $curso->periodo)
            ->where('h.clave_asignatura', $materia->clave_asignatura)
            ->when($curso->codigo_grupo, fn ($query) => $query->where('h.codigo_grupo', $curso->codigo_grupo))
            ->where('h.activo', true)
            ->select('p.clave_profesor', 'p.nombre_profesor', 'p.paterno', 'p.materno')
            ->selectRaw("TRIM(CONCAT(COALESCE(p.paterno, ''), ' ', COALESCE(p.materno, ''), ' ', COALESCE(p.nombre_profesor, ''))) as nombre_completo")
            ->distinct()
            ->orderBy('nombre_completo')
            ->get() : collect());
        $alumnoRows = collect();

        $alumnoRows = DB::table('alumnos_cursos as ac')
            ->leftJoin('alumnos_grupos as ag', function ($join) use ($curso) {
                $join->on('ag.numero_alumno', '=', 'ac.numero_alumno')
                    ->where('ag.inicial', $curso->inicial)
                    ->where('ag.final', $curso->final)
                    ->where('ag.periodo', $curso->periodo)
                    ->where('ag.estatus', 'INSCRITO');
            })
            ->where('ac.inicial', $curso->inicial)
            ->where('ac.final', $curso->final)
            ->where('ac.periodo', $curso->periodo)
            ->where('ac.codigo_curso', $curso->clave_curso)
            ->select('ac.numero_alumno')
            ->selectRaw('MIN(ag.codigo_grupo) as codigo_grupo')
            ->groupBy('ac.numero_alumno')
            ->get();

        if ($alumnoRows->isEmpty() && $materia) {
            $alumnoRows = DB::table('alumnos as a')
                ->join('alumnos_grupos as ag', 'ag.numero_alumno', '=', 'a.numero_alumno')
                ->join('horarios_det as h', function ($join) use ($curso, $materia) {
                    $join->on('h.codigo_grupo', '=', 'ag.codigo_grupo')
                        ->on('h.inicial', '=', 'ag.inicial')
                        ->on('h.final', '=', 'ag.final')
                        ->on('h.periodo', '=', 'ag.periodo')
                        ->where('h.inicial', $curso->inicial)
                        ->where('h.final', $curso->final)
                        ->where('h.periodo', $curso->periodo)
                        ->where('h.clave_asignatura', $materia->clave_asignatura);
                    if ($curso->codigo_grupo) {
                        $join->where('h.codigo_grupo', $curso->codigo_grupo);
                    }
                })
                ->where('ag.inicial', $curso->inicial)
                ->where('ag.final', $curso->final)
                ->where('ag.periodo', $curso->periodo)
                ->where('ag.estatus', 'INSCRITO')
                ->when($curso->codigo_grupo, fn ($query) => $query->where('ag.codigo_grupo', $curso->codigo_grupo))
                ->select('a.numero_alumno')
                ->selectRaw('MIN(ag.codigo_grupo) as codigo_grupo')
                ->groupBy('a.numero_alumno')
                ->orderBy('a.numero_alumno')
                ->get();
        }

        $alumnos = Alumno::query()
            ->whereIn('numero_alumno', $alumnoRows->pluck('numero_alumno'))
            ->with(['nivelRel', 'turnoRel', 'sede'])
            ->orderBy('paterno')
            ->orderBy('materno')
            ->orderBy('nombre')
            ->get()
            ->keyBy('numero_alumno');

        $alumnosConGrupo = $alumnoRows->map(function ($row) use ($alumnos) {
            $alumno = $alumnos->get($row->numero_alumno);
            if ($alumno) {
                $alumno->curso_codigo_grupo = $row->codigo_grupo;
            }
            return $alumno;
        })->filter()->values();

        return view('academia.cursos.show', [
            'ciclo' => $ciclo,
            'curso' => $curso,
            'materias' => $materias,
            'materia' => $materia,
            'docentes' => $docentes,
            'alumnos' => $alumnosConGrupo,
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
        
        $request->validate((new CursoFormRequest)->rules(), (new CursoFormRequest)->messages());

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
        $request->validate((new CursoFormRequest)->rules(), (new CursoFormRequest)->messages());

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
        if (CursoDet::where('curso_id', $curso->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cada curso solo puede tener una materia.',
            ], 422);
        }

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