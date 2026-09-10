<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Academia\HorarioDet;
use App\Models\Academia\SesionBase;
use App\Models\Academia\Grupo;
use App\Models\Academia\Profesor;
use App\Models\Academia\Materia;
use App\Models\Academia\DocenteAsistencia;
use App\Models\Academia\Alumno;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HorarioResolver
{
    /**
     * Obtiene la cuadrícula de horarios para un grupo/ciclo/día
     * Equivalente a get_clase_asistencia_grid() de ProyectoBase
     */
    public function getClaseAsistenciaGrid(
        int $inicial,
        int $final,
        int $periodo,
        string $nivel,
        string $turno,
        int $dia,
        string $fechaClase,
        ?string $sede = null,
        ?string $edificio = null
    ): array {
        $horarios = HorarioDet::with([
            'grupo', 'profesor', 'materia', 'sede'
        ])
            ->join('grupos', function ($join) {
                $join->on('horarios_det.codigo_grupo', '=', 'grupos.codigo_grupo')
                    ->on('horarios_det.inicial', '=', 'grupos.inicial')
                    ->on('horarios_det.final', '=', 'grupos.final')
                    ->on('horarios_det.periodo', '=', 'grupos.periodo');
            })
            ->where('horarios_det.inicial', $inicial)
            ->where('horarios_det.final', $final)
            ->where('horarios_det.periodo', $periodo)
            ->where('grupos.nivel', $nivel)
            ->whereRaw('UPPER(grupos.turno) LIKE ?', [strtoupper(substr($turno, 0, 1)) . '%'])
            ->where('horarios_det.dia', $dia)
            ->where('horarios_det.activo', true)
            ->when($sede, fn ($query) => $query->where('horarios_det.id_campus', $sede))
            ->when($edificio, fn ($query) => $query->where('horarios_det.edificio', $edificio))
            ->orderBy('horarios_det.id_campus')
            ->orderBy('horarios_det.edificio')
            ->orderBy('horarios_det.sesion')
            ->orderBy('horarios_det.aula')
            ->get();

        $alumnosPorGrupo = DB::table('alumnos_grupos')
            ->where('inicial', $inicial)
            ->where('final', $final)
            ->where('periodo', $periodo)
            ->where('estatus', 'INSCRITO')
            ->select('codigo_grupo')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('codigo_grupo')
            ->pluck('total', 'codigo_grupo');

        return $horarios->map(function (HorarioDet $h) use ($fechaClase, $alumnosPorGrupo) {
            $asistencia = DocenteAsistencia::where('codigo_grupo', $h->codigo_grupo)
                ->where('clave_profesor', $h->clave_profesor)
                ->where('clave_asignatura', $h->clave_asignatura)
                ->where('inicial', $h->inicial)
                ->where('final', $h->final)
                ->where('periodo', $h->periodo)
                ->where('dia', $h->dia)
                ->where('sesion', $h->sesion)
                ->whereDate('fecha', $fechaClase)
                ->first();

            return [
                'INICIAL' => $h->inicial,
                'FINAL' => $h->final,
                'PERIODO' => $h->periodo,
                'CODIGO_GRUPO' => $h->codigo_grupo,
                'ALUMNOS_TOTAL' => (int) ($alumnosPorGrupo[$h->codigo_grupo] ?? $h->grupo?->inscritos ?? 0),
                'CLAVEPROFESOR' => $h->clave_profesor,
                'CLAVEASIGNATURA' => $h->clave_asignatura,
                'DIA' => $h->dia,
                'SESION' => $h->sesion,
                'ID_CAMPUS' => $h->id_campus,
                'SEDE_NOMBRE' => $h->sede?->descripcion,
                'EDIFICIO' => $h->edificio,
                'AULA' => $h->aula,
                'NOMBREPROFESOR' => $h->profesor?->nombre_completo,
                'GRADO' => $h->grupo?->grado,
                'TURNO' => $h->grupo?->turno,
                'NIVEL' => $h->grupo?->nivel,
                'MATERIA_NOMBRE' => $h->materia?->nombre_asignatura,
                'SESION_INI' => $h->sesionBase?->hora_inicio?->format('H:i'),
                'SESION_FIN' => $h->sesionBase?->hora_fin?->format('H:i'),
                'RECESO' => $h->sesionBase?->receso ? 'S' : 'N',
                'ASISTENCIA_ESTADO' => $asistencia?->estado,
                'ASISTENCIA_OBS' => $asistencia?->observaciones,
                'CAPTURADO_POR' => null,
                'CAPTURADO_EN' => null,
            ];
        })->toArray();
    }

    /**
     * Estadísticas de asistencia del día
     * Equivalente a get_clase_asistencia_stats() de ProyectoBase
     */
    public function getClaseAsistenciaStats(
        int $inicial,
        int $final,
        int $periodo,
        string $nivel,
        string $turno,
        int $dia,
        string $fechaClase,
        ?string $sede = null,
        ?string $edificio = null
    ): array {
        $defaults = [
            'total_clases' => 0,
            'capturadas' => 0,
            'presentes' => 0,
            'ausentes' => 0,
            'retardos' => 0,
            'justificados' => 0,
        ];

        $stats = DB::table('horarios_det as h')
            ->leftJoin('grupos as g', function ($join) {
                $join->on('h.codigo_grupo', '=', 'g.codigo_grupo')
                    ->on('h.inicial', '=', 'g.inicial')
                    ->on('h.final', '=', 'g.final')
                    ->on('h.periodo', '=', 'g.periodo');
            })
            ->leftJoin('docentes_asistencias as ca', function ($join) use ($fechaClase) {
                $join->on('h.codigo_grupo', '=', 'ca.codigo_grupo')
                    ->on('h.clave_profesor', '=', 'ca.clave_profesor')
                    ->on('h.clave_asignatura', '=', 'ca.clave_asignatura')
                    ->on('h.inicial', '=', 'ca.inicial')
                    ->on('h.final', '=', 'ca.final')
                    ->on('h.periodo', '=', 'ca.periodo')
                    ->on('h.dia', '=', 'ca.dia')
                    ->on('h.sesion', '=', 'ca.sesion')
                    ->whereDate('ca.fecha', $fechaClase);
            })
            ->where('h.inicial', $inicial)
            ->where('h.final', $final)
            ->where('h.periodo', $periodo)
            ->where('g.nivel', $nivel)
            ->whereRaw('UPPER(g.turno) LIKE ?', [strtoupper(substr($turno, 0, 1)) . '%'])
            ->where('h.dia', $dia)
            ->where('h.activo', true)
            ->when($sede, fn ($query) => $query->where('h.id_campus', $sede))
            ->when($edificio, fn ($query) => $query->where('h.edificio', $edificio))
            ->selectRaw('
                COUNT(*) as total_clases,
                SUM(CASE WHEN ca.estado IS NOT NULL THEN 1 ELSE 0 END) as capturadas,
                SUM(CASE WHEN ca.estado = "PRESENTE" THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN ca.estado = "AUSENTE" THEN 1 ELSE 0 END) as ausentes,
                SUM(CASE WHEN ca.estado = "RETARDO" THEN 1 ELSE 0 END) as retardos,
                SUM(CASE WHEN ca.estado = "JUSTIFICADO" THEN 1 ELSE 0 END) as justificados
            ')
            ->first();

        if (! $stats) {
            return $defaults;
        }

        return [
            'total_clases' => (int)($stats->total_clases ?? 0),
            'capturadas' => (int)($stats->capturadas ?? 0),
            'presentes' => (int)($stats->presentes ?? 0),
            'ausentes' => (int)($stats->ausentes ?? 0),
            'retardos' => (int)($stats->retardos ?? 0),
            'justificados' => (int)($stats->justificados ?? 0),
        ];
    }

    /**
     * Construye la cuadrícula base 7 días × sesiones
     * Equivalente a build_horario_grid() de ProyectoBase
     */
    public function buildHorarioGrid(array $horarios, array $horarioBase): array
    {
        $dias = range(1, 7);
        $grid = [];

        foreach ($dias as $dia) {
            foreach ($horarioBase as $sesion => $info) {
                $key = "$dia-$sesion";
                $grid[$key] = [
                    'dia' => $dia,
                    'sesion' => $sesion,
                    'inicio' => $info['inicio'],
                    'fin' => $info['fin'],
                    'receso' => $info['receso'],
                    'descripcion' => $info['descripcion'],
                    'clases' => [],
                ];
            }
        }

        foreach ($horarios as $h) {
            $key = "{$h->dia}-{$h->sesion}";
            if (isset($grid[$key])) {
                $grid[$key]['clases'][] = [
                    'grupo' => $h->codigo_grupo ?? '',
                    'materia' => $h->materia?->nombre_asignatura ?? $h->clave_asignatura ?? '',
                    'profesor' => $h->profesor?->nombre_completo ?? $h->clave_profesor ?? '',
                    'aula' => $h->ubicacion,
                    'tipo' => $h->origen_horario === 'HD' ? 'PTC' : 'PA',
                ];
            }
        }

        return $grid;
    }

    /**
     * Obtiene horario base por nivel y turno
     * Equivalente a get_horario_base() de ProyectoBase
     */
    public function getHorarioBase(string $nivel, string $turno): array
    {
        return SesionBase::where('nivel', $nivel)
            ->where('turno', $turno)
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->mapWithKeys(function (SesionBase $s) {
                return [$s->sesion => [
                    'inicio' => $s->hora_inicio?->format('H:i') ?? '??:??',
                    'fin' => $s->hora_fin?->format('H:i') ?? '??:??',
                    'receso' => $s->receso,
                    'descripcion' => $s->descripcion,
                ]];
            })->toArray();
    }

    /**
     * Obtiene horarios de un profesor en un ciclo
     */
    public function getHorarioProfesor(string $claveProfesor, int $inicial, int $final, int $periodo): Collection
    {
        return HorarioDet::with(['grupo', 'materia', 'sede'])
            ->where('clave_profesor', $claveProfesor)
            ->where('inicial', $inicial)
            ->where('final', $final)
            ->where('periodo', $periodo)
            ->where('activo', true)
            ->orderBy('dia')
            ->orderBy('sesion')
            ->get();
    }

    /**
     * Detecta conflictos de aula (mismo aula, mismo día, misma sesión)
     */
    public function detectarConflictosAula(int $inicial, int $final, int $periodo): array
    {
        return DB::table('horarios_det')
            ->where('inicial', $inicial)
            ->where('final', $final)
            ->where('periodo', $periodo)
            ->where('activo', true)
            ->whereNotNull('aula')
            ->whereNotNull('edificio')
            ->select('dia', 'sesion', 'id_campus', 'edificio', 'aula')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('dia', 'sesion', 'id_campus', 'edificio', 'aula')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->toArray();
    }
}