<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Alumno;
use App\Models\Academia\AlumnoKardex;
use App\Models\Academia\Materia;
use App\Models\Academia\MetodoEval;
use App\Services\CicloActualService;
use App\Services\KardexCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class KardexController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService,
        protected KardexCalculator $kardexCalculator
    ) {}

    public function index(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        return view('academia.kardex.index', [
            'ciclo' => $ciclo,
            'ciclos' => $this->cicloService->getAllForSelector(),
        ]);
    }

    public function show(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        $buscar = $request->get('buscar');
        
        $alumnos = [];
        
        if ($buscar) {
            $alumnos = \App\Models\Academia\Alumno::where(function ($q) use ($buscar) {
                $q->where('numero_alumno', 'like', "%{$buscar}%")
                  ->orWhere('paterno', 'like', "%{$buscar}%")
                  ->orWhere('materno', 'like', "%{$buscar}%")
                  ->orWhere('nombre', 'like', "%{$buscar}%");
            })
            ->limit(20)
            ->get();
        }

        $kardex = null;
        $alumnoSeleccionado = null;
        
        if ($request->filled('alumno_id')) {
            $alumnoSeleccionado = \App\Models\Academia\Alumno::findOrFail($request->get('alumno_id'));
            $kardex = $this->kardexCalculator->calcularKardexCompleto(
                $alumnoSeleccionado->numero_alumno,
                $ciclo->inicial,
                $ciclo->final,
                $ciclo->periodo
            );
        }

        return view('academia.kardex.show', [
            'ciclo' => $ciclo,
            'alumnos' => $alumnos,
            'alumnoSeleccionado' => $alumnoSeleccionado,
            'kardex' => $kardex,
        ]);
    }

    public function historial(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        $buscar = $request->get('buscar');
        
        $alumno = null;
        $historial = [];
        
        if ($buscar) {
            $alumno = \App\Models\Academia\Alumno::where(function ($q) use ($buscar) {
                $q->where('numero_alumno', 'like', "%{$buscar}%")
                  ->orWhere('paterno', 'like', "%{$buscar}%")
                  ->orWhere('materno', 'like', "%{$buscar}%")
                  ->orWhere('nombre', 'like', "%{$buscar}%");
            })->first();
            
            if ($alumno) {
                $historial = $this->kardexCalculator->getHistorialAlumno($alumno->numero_alumno);
            }
        }

        return view('academia.kardex.historial', [
            'ciclo' => $ciclo,
            'alumno' => $alumno,
            'historial' => $historial,
        ]);
    }

    public function print(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        $alumno = \App\Models\Academia\Alumno::findOrFail($request->get('alumno_id'));
        
        $kardex = $this->kardexCalculator->calcularKardexCompleto(
            $alumno->numero_alumno,
            $ciclo->inicial,
            $ciclo->final,
            $ciclo->periodo
        );

        return view('academia.kardex.print', [
            'ciclo' => $ciclo,
            'alumno' => $alumno,
            'kardex' => $kardex,
        ]);
    }
}