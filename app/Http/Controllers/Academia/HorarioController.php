<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\HorarioDet;
use App\Models\Academia\Grupo;
use App\Models\Academia\Profesor;
use App\Models\Academia\Materia;
use App\Models\Academia\Sede;
use App\Models\Academia\Alumno;
use App\Models\Academia\SesionBase;
use App\Services\CicloActualService;
use App\Services\HorarioResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class HorarioController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService,
        protected HorarioResolver $horarioResolver
    ) {}

    public function clase(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        // Filtros
        $nivel = $request->get('nivel');
        $turno = $request->get('turno');
        $dia = (int)($request->get('dia', now()->dayOfWeekIso));
        $fecha = $request->get('fecha', now()->toDateString());
        $sede = $request->get('sede');
        $edificio = $request->get('edificio');

        $niveles = \App\Models\Academia\Nivel::activo()->get();
        $turnos = \App\Models\Academia\Turno::activo()->get();
        $sedes = Sede::activo()->orderBy('descripcion')->get();
        $edificios = collect();

        if ($nivel && $turno) {
            $edificios = HorarioDet::query()
                ->where('horarios_det.inicial', $ciclo->inicial)
                ->where('horarios_det.final', $ciclo->final)
                ->where('horarios_det.periodo', $ciclo->periodo)
                ->where('horarios_det.activo', true)
                ->whereNotNull('horarios_det.edificio')
                ->when($sede, fn ($query) => $query->where('horarios_det.id_campus', $sede))
                ->join('grupos as g', function ($join) {
                    $join->on('horarios_det.codigo_grupo', '=', 'g.codigo_grupo')
                        ->on('horarios_det.inicial', '=', 'g.inicial')
                        ->on('horarios_det.final', '=', 'g.final')
                        ->on('horarios_det.periodo', '=', 'g.periodo');
                })
                ->where('g.nivel', $nivel)
                ->whereRaw('UPPER(g.turno) LIKE ?', [strtoupper(substr($turno, 0, 1)) . '%'])
                ->distinct()
                ->orderBy('horarios_det.edificio')
                ->pluck('horarios_det.edificio');
        }

        $horarios = [];
        $stats = ['total_clases'=>0,'capturadas'=>0,'presentes'=>0,'ausentes'=>0,'retardos'=>0,'justificados'=>0];

        if ($nivel && $turno) {
            $horarios = $this->horarioResolver->getClaseAsistenciaGrid(
                $ciclo->inicial, $ciclo->final, $ciclo->periodo,
                $nivel, $turno, $dia, $fecha, $sede, $edificio
            );
            $stats = $this->horarioResolver->getClaseAsistenciaStats(
                $ciclo->inicial, $ciclo->final, $ciclo->periodo,
                $nivel, $turno, $dia, $fecha, $sede, $edificio
            );
        }

        return view('academia.horarios.clase', [
            'ciclo' => $ciclo,
            'niveles' => $niveles,
            'turnos' => $turnos,
            'horarios' => $horarios,
            'stats' => $stats,
            'sedes' => $sedes,
            'edificios' => $edificios,
            'filtros' => compact('nivel', 'turno', 'dia', 'fecha', 'sede', 'edificio'),
            'diasSemana' => [
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
            ],
        ]);
    }

    public function profesor(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $profesores = \App\Models\Academia\Profesor::activo()
            ->whereHas('horarios', fn ($q) => $q->where('inicial', $ciclo->inicial)
                ->where('final', $ciclo->final)
                ->where('periodo', $ciclo->periodo))
            ->get();

        return view('academia.horarios.profesor', [
            'ciclo' => $ciclo,
            'profesores' => $profesores,
        ]);
    }

    public function aula(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $conflictos = $this->horarioResolver->detectarConflictosAula(
            $ciclo->inicial, $ciclo->final, $ciclo->periodo
        );

        return view('academia.horarios.aula', [
            'ciclo' => $ciclo,
            'conflictos' => $conflictos,
        ]);
    }

    public function base(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $sesiones = SesionBase::with(['nivelRel', 'turnoRel'])
            ->activo()
            ->orderBy('nivel')
            ->orderBy('turno')
            ->orderBy('sesion')
            ->get();

        return view('academia.horarios.base', [
            'ciclo' => $ciclo,
            'sesiones' => $sesiones,
        ]);
    }

    public function persona(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $buscar = $request->get('buscar');
        $tipo = $request->get('tipo', 'profesor'); // profesor, alumno
        
        $resultados = [];
        
        if ($buscar) {
            if ($tipo === 'profesor') {
                $profesor = \App\Models\Academia\Profesor::where('clave_profesor', $buscar)
                    ->orWhere('nombre_profesor', 'like', "%{$buscar}%")
                    ->first();
                
                if ($profesor) {
                    $resultados = $this->horarioResolver->getHorarioProfesor(
                        $profesor->clave_profesor,
                        $ciclo->inicial, $ciclo->final, $ciclo->periodo
                    );
                }
            }
        }

        return view('academia.horarios.persona', [
            'ciclo' => $ciclo,
            'resultados' => $resultados,
            'buscar' => $buscar,
            'tipo' => $tipo,
        ]);
    }
}