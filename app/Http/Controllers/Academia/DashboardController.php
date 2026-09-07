<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Grupo;
use App\Models\Academia\Alumno;
use App\Models\Academia\HorarioDet;
use App\Models\Academia\Profesor;
use App\Models\Academia\Curso;
use App\Services\CicloActualService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService
    ) {}

    public function index(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        // KPIs del ciclo
        $kpis = [
            'grupos' => Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->activo()->count(),
            'alumnos' => Alumno::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->activo()->count(),
            'profesores' => \App\Models\Academia\Profesor::activo()->count(),
            'horarios' => HorarioDet::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->activo()->count(),
            'cursos' => Curso::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)->activo()->count(),
        ];

        // Horarios por día (para gráfico)
        $horariosPorDia = \App\Models\Academia\HorarioDet::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
            ->activo()
            ->selectRaw('dia, COUNT(*) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->mapWithKeys(fn ($h) => [$h->dia => $h->total])
            ->toArray();

        // Distribución por origen horario
        $porOrigen = HorarioDet::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
            ->activo()
            ->selectRaw('origen_horario, COUNT(*) as total')
            ->groupBy('origen_horario')
            ->get()
            ->mapWithKeys(fn ($h) => [$h->origen_horario ?: 'SIN_DEFINIR' => $h->total])
            ->toArray();

        return view('academia.dashboard', [
            'ciclo' => $ciclo,
            'kpis' => $kpis,
            'horariosPorDia' => $horariosPorDia,
            'porOrigen' => $porOrigen,
            'ciclos' => $this->cicloService->getAllForSelector(),
        ]);
    }
}