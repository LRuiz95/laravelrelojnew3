<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academia;

use App\Http\Controllers\Controller;
use App\Models\Academia\Ciclo;
use App\Models\Academia\Grupo;
use App\Models\Academia\Alumno;
use App\Models\Academia\AlumnoGrupo;
use App\Models\Academia\AlumnoAsistencia;
use App\Models\Academia\GrupoAsistencia;
use App\Models\Academia\HorarioDet;
use App\Services\CicloActualService;
use App\Services\HorarioResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GrupoController extends Controller
{
    public function __construct(
        protected CicloActualService $cicloService,
        protected HorarioResolver $horarioResolver
    ) {}

    public function index(Request $request): View
    {
        $ciclo = $this->cicloService->resolve($request);
        
        $grupos = Grupo::porCiclo($ciclo->inicial, $ciclo->final, $ciclo->periodo)
            ->activo()
            ->when($request->filled('nivel'), fn ($query) => $query->where('nivel', $request->string('nivel')->toString()))
            ->when($request->filled('turno'), fn ($query) => $query->whereRaw(
                'UPPER(turno) LIKE ?',
                [strtoupper(substr($request->string('turno')->toString(), 0, 1)) . '%'],
            ))
            ->when($request->filled('sede'), fn ($query) => $query->where('id_campus', $request->string('sede')->toString()))
            ->with(['nivelRel', 'turnoRel', 'sede'])
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
            ->with(['nivelRel', 'turnoRel', 'sede'])
            ->orderBy('paterno')
            ->orderBy('materno')
            ->orderBy('nombre')
            ->paginate(30);

        // Horarios del grupo
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
        $fecha = $request->get('fecha', now()->toDateString());

        abort_unless(
            $grupo->inicial === $ciclo->inicial
                && $grupo->final === $ciclo->final
                && $grupo->periodo === $ciclo->periodo,
            404
        );

        $horariosPorDia = HorarioDet::query()
            ->where('codigo_grupo', $grupo->codigo_grupo)
            ->where('inicial', $ciclo->inicial)
            ->where('final', $ciclo->final)
            ->where('periodo', $ciclo->periodo)
            ->where('activo', true)
            ->with(['materia', 'profesor', 'sede', 'sesionBase'])
            ->orderBy('dia')
            ->orderBy('sesion')
            ->get()
            ->groupBy('dia');

        $clases = $horariosPorDia->flatten(1)->values();
        $claseSeleccionada = $clases->firstWhere('id', (int) $request->get('horario_id'))
            ?? $clases->first();

        $alumnos = $grupo->alumnos()
            ->where('alumnos_grupos.estatus', 'INSCRITO')
            ->orderBy('paterno')
            ->orderBy('materno')
            ->orderBy('nombre')
            ->get();

        $asistencias = collect();
        $grupoAsistencia = null;

        if ($claseSeleccionada) {
            $contexto = $this->contextoClase($ciclo, $grupo, $claseSeleccionada, $fecha);
            $asistencias = AlumnoAsistencia::query()
                ->where($contexto)
                ->get()
                ->keyBy('numero_alumno');
            $grupoAsistencia = GrupoAsistencia::query()->where($contexto)->first();
        }

        $stats = [
            'total_alumnos' => $alumnos->count(),
            'capturadas' => $asistencias->count(),
            'presentes' => $asistencias->where('estado', 'PRESENTE')->count(),
            'ausentes' => $asistencias->where('estado', 'AUSENTE')->count(),
            'retardos' => $asistencias->where('estado', 'RETARDO')->count(),
            'justificados' => $asistencias->where('estado', 'JUSTIFICADO')->count(),
        ];

        return view('academia.grupos.asistencia', [
            'ciclo' => $ciclo,
            'grupo' => $grupo,
            'horariosPorDia' => collect(range(1, 7))->mapWithKeys(
                fn (int $dia) => [$dia => $horariosPorDia->get($dia, collect())]
            ),
            'claseSeleccionada' => $claseSeleccionada,
            'alumnos' => $alumnos,
            'asistencias' => $asistencias,
            'grupoAsistencia' => $grupoAsistencia,
            'stats' => $stats,
            'fecha' => $fecha,
            'diasSemana' => [
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
            ],
        ]);
    }

    public function guardarAsistencia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inicial' => ['required', 'integer'],
            'final' => ['required', 'integer'],
            'periodo' => ['required', 'integer'],
            'horario_id' => ['required', 'integer'],
            'codigo_grupo' => ['required', 'string', 'max:50'],
            'fecha' => ['required', 'date'],
            'observacion_grupo' => ['nullable', 'string', 'max:1000'],
            'alumnos' => ['required', 'array'],
        ]);

        $horario = HorarioDet::query()
            ->whereKey($data['horario_id'])
            ->where('codigo_grupo', $data['codigo_grupo'])
            ->where('inicial', $data['inicial'])
            ->where('final', $data['final'])
            ->where('periodo', $data['periodo'])
            ->firstOrFail();

        $grupo = Grupo::porCiclo($data['inicial'], $data['final'], $data['periodo'])
            ->where('codigo_grupo', $data['codigo_grupo'])
            ->firstOrFail();

        $contexto = $this->contextoClaseFromHorario($data, $horario);
        $inscritos = AlumnoGrupo::query()
            ->where($this->cicloWhere($data))
            ->where('codigo_grupo', $grupo->codigo_grupo)
            ->where('estatus', 'INSCRITO')
            ->pluck('numero_alumno');

        DB::transaction(function () use ($data, $contexto, $inscritos) {
            GrupoAsistencia::updateOrCreate(
                $contexto,
                ['observaciones' => $data['observacion_grupo'] ?? null],
            );

            foreach ($inscritos as $numeroAlumno) {
                $registro = $data['alumnos'][(string) $numeroAlumno] ?? $data['alumnos'][$numeroAlumno] ?? null;
                if (! is_array($registro)) {
                    continue;
                }

                $estado = $registro['estado'] ?? null;
                if (! in_array($estado, ['PRESENTE', 'AUSENTE', 'RETARDO', 'JUSTIFICADO'], true)) {
                    continue;
                }

                AlumnoAsistencia::updateOrCreate(
                    array_merge($contexto, ['numero_alumno' => $numeroAlumno]),
                    ['estado' => $estado, 'observaciones' => $registro['observaciones'] ?? null],
                );
            }
        });

        return back()->with('success', 'Asistencia del grupo y sus alumnos guardada correctamente.');
    }

    private function cicloWhere(array $data): array
    {
        return [
            'inicial' => $data['inicial'],
            'final' => $data['final'],
            'periodo' => $data['periodo'],
        ];
    }

    private function contextoClase($ciclo, Grupo $grupo, HorarioDet $horario, string $fecha): array
    {
        return [
            'inicial' => $ciclo->inicial,
            'final' => $ciclo->final,
            'periodo' => $ciclo->periodo,
            'codigo_grupo' => $grupo->codigo_grupo,
            'clave_profesor' => $horario->clave_profesor,
            'clave_asignatura' => $horario->clave_asignatura,
            'dia' => $horario->dia,
            'sesion' => $horario->sesion,
            'fecha' => $fecha,
        ];
    }

    private function contextoClaseFromHorario(array $data, HorarioDet $horario): array
    {
        return [
            'inicial' => $data['inicial'],
            'final' => $data['final'],
            'periodo' => $data['periodo'],
            'codigo_grupo' => $data['codigo_grupo'],
            'clave_profesor' => $horario->clave_profesor,
            'clave_asignatura' => $horario->clave_asignatura,
            'dia' => $horario->dia,
            'sesion' => $horario->sesion,
            'fecha' => $data['fecha'],
        ];
    }
}