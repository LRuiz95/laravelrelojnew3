<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Grupo;
use App\Models\Academia\Alumno;
use App\Models\Academia\AlumnoGrupo;
use App\Models\Academia\AlumnoKardex;
use App\Services\CicloActualService;
use App\Services\HorarioResolver;
use App\Services\KardexCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GrupoController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService,
        protected HorarioResolver $horarioResolver,
        protected KardexCalculator $kardexCalculator
    ) {}

    public function index(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $grupos = Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
            ->activo()
            ->with(['nivelRel', 'turnoRel', 'sede'])
            ->withCount('alumnos')
            ->orderBy('grado')
            ->orderBy('turno')
            ->orderBy('codigo_grupo')
            ->paginate(25);

        return view('academia.grupos.index', [
            'ciclo' => $ciclo,
            'grupos' => $grupos,
            'ciclos' => $this->cicloService->getAllForSelector(),
        ]);
    }

    public function show(Request $request, Grupo $grupo): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $grupo->load(['ciclo', 'nivelRel', 'turnoRel', 'sede']);
        
        // Alumnos inscritos
        $alumnos = $grupo->alumnos()
            ->withPivot('fecha_inscripcion', 'estatus')
            ->orderBy('paterno')
            ->orderBy('materno')
            ->orderBy('nombre')
            ->paginate(30);

        // Horarios del grupo
        $horarios = $grupo->horarios()
            ->with(['materia', 'profesor', 'sede', 'sesionBase'])
            ->activo()
            ->orderBy('dia')
            ->orderBy('sesion')
            ->get()
            ->groupBy('dia');

        // Conflictos de aula
        $conflictos = $this->horarioResolver->detectarConflictosAula(
            $ciclo->inicial, $ciclo->final, $ciclo->periodo
        );

        return view('academia.grupos.show', [
            'ciclo' => $ciclo,
            'grupo' => $grupo,
            'alumnos' => $alumnos,
            'horarios' => $horarios,
            'conflictos' => $conflictos,
            'ciclos' => $this->cicloService->getAllForSelector(),
        ]);
    }

    public function asistencia(Request $request, Grupo $grupo): View
    {
        $ciclo = $this->cicloService->resolve($request);
        $dia = $request->get('dia', now()->dayOfWeekIso);
        $fecha = $request->get('fecha', now()->toDateString());

        $horarios = $this->horarioResolver->getClaseAsistenciaGrid(
            $ciclo->inicial, $ciclo->final, $ciclo->periodo,
            $grupo->nivel, $grupo->turno, $dia, $fecha
        );

        $stats = $this->horarioResolver->getClaseAsistenciaStats(
            $ciclo->inicial, $ciclo->final, $ciclo->periodo,
            $grupo->nivel, $grupo->turno, $dia, $fecha
        );

        return view('academia.grupos.asistencia', [
            'ciclo' => $ciclo,
            'grupo' => $grupo,
            'horarios' => $horarios,
            'stats' => $stats,
            'dia' => $dia,
            'fecha' => $fecha,
            'diasSemana' => [
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
            ],
        ]);
    }

    public function guardarAsistencia(Request $request): RedirectResponse
    {
        // Validación y guardado de asistencia
        // Se implementará con AJAX
        return back()->with('success', 'Asistencia guardada');
    }
}