<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Grupo;
use App\Models\Academia\Alumno;
use App\Models\Academia\AlumnoGrupo;
use App\Models\Academia\AlumnoKardex;
use App\Models\Academia\Plan;
use App\Models\Academia\Materia;
use App\Models\Academia\MetodoEval;
use App\Models\Academia\Nivel;
use App\Models\Academia\Turno;
use App\Models\Academia\Sede;
use App\Models\Academia\HorarioDet;
use App\Services\CicloActualService;
use App\Services\HorarioResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService,
        protected HorarioResolver $horarioResolver
    ) {}

    // GET /api/academia/grupos-por-ciclo?ciclo=2025-2025-3
    public function gruposPorCiclo(Request $request): JsonResponse
    {
        $ciclo = $request->get('ciclo');
        if (! $ciclo) {
            $ciclo = $this->cicloService->getDefaultCiclo()->label;
        }

        $parts = explode('-', $ciclo);
        if (count($parts) !== 3) {
            return response()->json(['success' => false, 'data' => []]);
        }

        [$I, $F, $P] = array_map('intval', $parts);

        $grupos = Grupo::porCiclo($I, $F, $P)
            ->activo()
            ->with(['nivelRel', 'turnoRel', 'sede'])
            ->get(['codigo_grupo', 'grado', 'turno', 'nivel', 'inscritos', 'id_campus', 'inicial', 'final', 'periodo'])
            ->map(function ($g) {
                return [
                    'CODIGO_GRUPO' => $g->codigo_grupo,
                    'GRADO' => $g->grado,
                    'TURNO' => $g->turno,
                    'NIVEL' => $g->nivel,
                    'INSCRITOS' => $g->inscritos,
                    'ID_CAMPUS' => $g->id_campus,
                    'NIVEL_NOMBRE' => $g->nivelRel?->descripcion ?? $g->nivel,
                    'TURNO_NOMBRE' => $g->turnoRel?->descripcion ?? $g->turno,
                ];
            });

        return response()->json(['success' => true, 'data' => $grupos->values()]);
    }

    // GET /api/academia/alumnos-por-grupo?ciclo=2025-2025-3&grupo=101
    public function alumnosPorGrupo(Request $request): JsonResponse
    {
        $ciclo = $request->get('ciclo');
        $grupo = $request->get('grupo');

        if (! $ciclo || ! $grupo) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $parts = explode('-', $ciclo);
        if (count($parts) !== 3) {
            return response()->json(['success' => false, 'data' => []]);
        }

        [$I, $F, $P] = array_map('intval', $parts);

        $alumnos = AlumnoGrupo::where('codigo_grupo', $grupo)
            ->where('inicial', $I)
            ->where('final', $F)
            ->where('periodo', $P)
            ->with(['alumno' => fn ($q) => $q->select('numero_alumno', 'paterno', 'materno', 'nombre')])
            ->get()
            ->map(function ($ag) {
                return [
                    'NUMEROALUMNO' => $ag->numero_alumno,
                    'NOMBRE_ALUMNO' => $ag->alumno?->nombre_completo ?? 'Sin nombre',
                ];
            });

        return response()->json(['success' => true, 'data' => $alumnos]);
    }

    // GET /api/academia/ciclos-disponibles?tipo=horarios
    public function ciclosDisponibles(Request $request): JsonResponse
    {
        $tipo = $request->get('tipo', 'horarios');

        $query = Ciclo::query();

        if ($tipo === 'kardex') {
            $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('alumnos_kardex as ak')
                    ->whereColumn('ak.inicial', 'ciclos.inicial')
                    ->whereColumn('ak.final', 'ciclos.final')
                    ->whereColumn('ak.periodo', 'ciclos.periodo');
            });
        } elseif ($tipo === 'cursos') {
            $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('cursos as c')
                    ->whereColumn('c.inicial', 'ciclos.inicial')
                    ->whereColumn('c.final', 'ciclos.final')
                    ->whereColumn('c.periodo', 'ciclos.periodo');
            });
        } else {
            $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('horarios_det as h')
                    ->whereColumn('h.inicial', 'ciclos.inicial')
                    ->whereColumn('h.final', 'ciclos.final')
                    ->whereColumn('h.periodo', 'ciclos.periodo');
            });
        }

        $ciclos = $query->latest('inicial')
            ->latest('final')
            ->latest('periodo')
            ->get(['inicial', 'final', 'periodo', 'descripcion']);

        return response()->json(['success' => true, 'data' => $ciclos]);
    }

    // GET /api/academia/planes-por-nivel?nivel=MS
    public function planesPorNivel(Request $request): JsonResponse
    {
        $nivel = $request->get('nivel');

        if (! $nivel) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $planes = Plan::where('nivel', $nivel)
            ->activo()
            ->get(['id_plan', 'nombre_plan', 'nivel']);

        return response()->json(['success' => true, 'data' => $planes]);
    }

    // GET /api/academia/materias-por-plan?plan=123
    public function materiasPorPlan(Request $request): JsonResponse
    {
        $planId = $request->get('plan');

        if (! $planId) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $materias = \App\Models\Academia\Materia::where('id_plan', $planId)
            ->activa()
            ->orderBy('semestre')
            ->orderBy('nombre_asignatura')
            ->get(['clave_asignatura', 'nombre_asignatura', 'nombre_corto', 'semestre', 'horas_teoria', 'horas_practica', 'creditos', 'tipo']);

        return response()->json(['success' => true, 'data' => $materias]);
    }

    // GET /api/academia/metodos-eval
    public function metodosEval(): JsonResponse
    {
        $metodos = \App\Models\Academia\MetodoEval::activo()
            ->get(['id_eval', 'nombre_corto', 'descripcion', 'tipo_examen', 'es_final']);

        return response()->json(['success' => true, 'data' => $metodos]);
    }

    // GET /api/academia/niveles
    public function niveles(): JsonResponse
    {
        $niveles = \App\Models\Academia\Nivel::activo()
            ->get(['nivel', 'descripcion']);

        return response()->json(['success' => true, 'data' => $niveles]);
    }

    // GET /api/academia/turnos
    public function turnos(): JsonResponse
    {
        $turnos = \App\Models\Academia\Turno::activo()
            ->get(['turno', 'descripcion', 'descripcion_corta']);

        return response()->json(['success' => true, 'data' => $turnos]);
    }

    // GET /api/academia/sedes
    public function sedes(): JsonResponse
    {
        $sedes = \App\Models\Academia\Sede::activo()
            ->get(['id_campus', 'descripcion']);

        return response()->json(['success' => true, 'data' => $sedes]);
    }

    // GET /api/academia/horario-base?nivel=MS&turno=MA
    public function horarioBase(Request $request): JsonResponse
    {
        $nivel = $request->get('nivel');
        $turno = $request->get('turno');

        if (! $nivel || ! $turno) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $horario = $this->horarioResolver->getHorarioBase($nivel, $turno);

        return response()->json(['success' => true, 'data' => $horario]);
    }

    // GET /api/academia/grupo-detalle?grupo=101&ciclo=2025-2025-3
    public function grupoDetalle(Request $request): JsonResponse
    {
        $ciclo = $request->get('ciclo');
        $grupo = $request->get('grupo');

        if (! $ciclo || ! $grupo) {
            return response()->json(['success' => false, 'data' => null]);
        }

        $parts = explode('-', $ciclo);
        if (count($parts) !== 3) {
            return response()->json(['success' => false, 'data' => null]);
        }

        [$I, $F, $P] = array_map('intval', $parts);

        $grupo = \App\Models\Academia\Grupo::where('codigo_grupo', $grupo)
            ->where('inicial', $I)
            ->where('final', $F)
            ->where('periodo', $P)
            ->with(['ciclo', 'nivelRel', 'turnoRel', 'sede'])
            ->first();

        if (! $grupo) {
            return response()->json(['success' => false, 'data' => null]);
        }

        $horarios = HorarioDet::query()
            ->where('codigo_grupo', $grupo->codigo_grupo)
            ->where('inicial', $grupo->inicial)
            ->where('final', $grupo->final)
            ->where('periodo', $grupo->periodo)
            ->with(['materia', 'profesor', 'sede'])
            ->activo()
            ->orderBy('dia')
            ->orderBy('sesion')
            ->get()
            ->groupBy('dia');

        return response()->json([
            'success' => true,
            'data' => [
                'grupo' => [
                    'codigo_grupo' => $grupo->codigo_grupo,
                    'grado' => $grupo->grado,
                    'turno' => $grupo->turno,
                    'nivel' => $grupo->nivel,
                    'inscritos' => $grupo->inscritos,
                    'sede' => $grupo->sede?->descripcion,
                    'ciclo' => $ciclo,
                ],
                'horarios' => $horarios->map(function ($clases) {
                    return $clases->map(function ($h) {
                        return [
                            'dia' => $h->dia,
                            'dia_nombre' => $h->dia_nombre,
                            'sesion' => $h->sesion,
                            'inicio' => $h->sesionBase?->hora_inicio?->format('H:i'),
                            'fin' => $h->sesionBase?->hora_fin?->format('H:i'),
                            'materia' => $h->materia?->label,
                            'profesor' => $h->profesor?->nombre_completo,
                            'aula' => $h->ubicacion,
                            'tipo' => $h->tipoClase,
                        ];
                    });
                }),
            ],
        ]);
    }
}