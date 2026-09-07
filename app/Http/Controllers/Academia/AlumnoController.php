<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Alumno;
use App\Models\Academia\AlumnoKardex;
use App\Models\Academia\AlumnoGrupo;
use App\Models\Academia\Grupo;
use App\Services\CicloActualService;
use App\Services\KardexCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumnoController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService,
        protected KardexCalculator $kardexCalculator
    ) {}

    public function index(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $query = Alumno::query();
        
        if ($request->filled('buscar')) {
            $buscar = $request->get('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_alumno', 'like', "%{$buscar}%")
                  ->orWhere('paterno', 'like', "%{$buscar}%")
                  ->orWhere('materno', 'like', "%{$buscar}%")
                  ->orWhere('nombre', 'like', "%{$buscar}%")
                  ->orWhere('curp', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->get('estatus'));
        }

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->get('nivel'));
        }

        if ($request->filled('turno')) {
            $query->where('turno', $request->get('turno'));
        }

        $alumnos = $query->with(['sede', 'nivelRel', 'turnoRel'])
            ->orderBy('paterno')
            ->orderBy('materno')
            ->orderBy('nombre')
            ->paginate(25);

        return view('academia.alumnos.index', [
            'ciclo' => $this->cicloService->resolve($request),
            'alumnos' => $alumnos,
            'estatusOptions' => ['ACTIVO', 'BAJA', 'EGRESADO', 'TITULADO', 'IRREGULAR'],
        ]);
    }

    public function show(Request $request, Alumno $alumno): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $alumno->load(['sede', 'nivelRel', 'turnoRel']);
        
        // Inscripciones del alumno en el ciclo actual
        $inscripciones = AlumnoGrupo::where('numero_alumno', $alumno->numero_alumno)
            ->where('inicial', $ciclo->inicial)
            ->where('final', $ciclo->final)
            ->where('periodo', $ciclo->periodo)
            ->with(['grupo.ciclo', 'grupo.nivelRel', 'grupo.turnoRel'])
            ->get();

        // Kardex del ciclo
        $kardex = $this->kardexCalculator->calcularKardexCompleto(
            $alumno->numero_alumno,
            $ciclo->inicial,
            $ciclo->final,
            $ciclo->periodo
        );

        return view('academia.alumnos.show', [
            'ciclo' => $ciclo,
            'alumno' => $alumno,
            'inscripciones' => $inscripciones,
            'kardex' => $kardex,
        ]);
    }

    public function kardex(Request $request, Alumno $alumno): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $kardex = $this->kardexCalculator->calcularKardexCompleto(
            $alumno->numero_alumno,
            $ciclo->inicial,
            $ciclo->final,
            $ciclo->periodo
        );

        return view('academia.alumnos.kardex', [
            'ciclo' => $ciclo,
            'alumno' => $alumno,
            'kardex' => $kardex,
        ]);
    }

    public function historial(Request $request, Alumno $alumno): View
    {
        $historial = $this->kardexCalculator->getHistorialAlumno($alumno->numero_alumno);
        
        return view('academia.alumnos.historial', [
            'ciclo' => $this->cicloService->resolve($request),
            'alumno' => $alumno,
            'historial' => $historial,
        ]);
    }
}