<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Academia\DocenteAsistencia;
use App\Models\Academia\Profesor;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Incidencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PuntualidadController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : Carbon::today()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : Carbon::today()->endOfDay();

        $graciaMinutos = max(0, (int) $request->query('gracia_minutos', 10));
        $horaEntradaBase = (string) $request->query('hora_entrada_base', '08:00');
        $horaSalidaBase = (string) $request->query('hora_salida_base', '17:00');

        $employeeRows = $this->buildEmployeeRows($from, $to, $graciaMinutos, $horaEntradaBase, $horaSalidaBase);
        $professorRows = $this->buildProfessorRows($from, $to);

        return view('puntualidad.index', compact(
            'employeeRows',
            'professorRows',
            'from',
            'to',
            'graciaMinutos',
            'horaEntradaBase',
            'horaSalidaBase'
        ));
    }

    private function buildEmployeeRows(
        Carbon $from,
        Carbon $to,
        int $graciaMinutos,
        string $horaEntradaBase,
        string $horaSalidaBase
    ): Collection {
        $records = Attendance::query()
            ->with('employee')
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at')
            ->get();

        $incidencias = Incidencia::query()
            ->whereNotNull('empleado_id')
            ->whereBetween('fecha_justificacion', [$from->toDateString(), $to->toDateString()])
            ->get();

        return $records
            ->groupBy(fn (Attendance $attendance) => ($attendance->employee_id ?? 'user-'.$attendance->user_id).':'.$attendance->recorded_at->toDateString())
            ->map(function (Collection $group) use ($incidencias, $graciaMinutos, $horaEntradaBase, $horaSalidaBase): array {
                $first = $group->first();
                $employee = $first->employee ?? Employee::query()->where('user_id', $first->user_id)->first();

                $entrada = $group
                    ->filter(fn (Attendance $attendance) => in_array($attendance->punchStatus(), [0, 4], true))
                    ->sortBy('recorded_at')
                    ->first();

                $salida = $group
                    ->filter(fn (Attendance $attendance) => in_array($attendance->punchStatus(), [1, 5], true))
                    ->sortByDesc('recorded_at')
                    ->first();

                $fecha = $first->recorded_at->toDateString();
                $incidenciaEstado = $this->incidenciaEstadoEmpleado($incidencias, (int) ($employee?->id ?? 0), $fecha);

                return [
                    'fecha' => $fecha,
                    'empleado' => $employee?->name ?? 'Sin asignar',
                    'user_id' => $first->user_id,
                    'tipo_persona' => $employee?->type_label ?? 'Empleado',
                    'area' => $employee?->area?->identificador ?? $employee?->departamento ?? 'Sin área',
                    'puesto' => $employee?->puesto?->identificador ?? $employee?->cargo ?? 'Sin puesto',
                    'entrada' => $entrada?->recorded_at?->format('H:i:s'),
                    'salida' => $salida?->recorded_at?->format('H:i:s'),
                    'llegada_estado' => $this->estadoLlegada($entrada, $horaEntradaBase, $graciaMinutos),
                    'salida_estado' => $this->estadoSalida($salida, $horaSalidaBase),
                    'incidencia_estado' => $incidenciaEstado,
                ];
            })
            ->sortByDesc('fecha')
            ->values();
    }

    private function buildProfessorRows(Carbon $from, Carbon $to): Collection
    {
        $records = DocenteAsistencia::query()
            ->whereBetween('fecha', [$from->toDateString(), $to->toDateString()])
            ->orderBy('fecha')
            ->get();

        $incidencias = Incidencia::query()
            ->whereNotNull('profesor_clave')
            ->whereBetween('fecha_justificacion', [$from->toDateString(), $to->toDateString()])
            ->get();

        return $records
            ->groupBy(fn (DocenteAsistencia $attendance) => $attendance->clave_profesor.':'.$attendance->fecha->toDateString())
            ->map(function (Collection $group) use ($incidencias): array {
                $first = $group->first();
                $profesor = Profesor::query()->find($first->clave_profesor);

                $estados = $group->pluck('estado')->all();
                $estado = $this->estadoProfesor($estados);
                $incidenciaEstado = $this->incidenciaEstadoProfesor($incidencias, $first->clave_profesor, $first->fecha->toDateString());

                return [
                    'fecha' => $first->fecha->toDateString(),
                    'profesor' => trim(($profesor?->paterno ?? '').' '.($profesor?->materno ?? '').' '.($profesor?->nombre_profesor ?? '')) ?: $first->clave_profesor,
                    'clave_profesor' => $first->clave_profesor,
                    'tipo_persona' => 'Profesor',
                    'area' => $profesor?->area?->identificador ?? 'Sin área',
                    'puesto' => $profesor?->puesto?->identificador ?? 'Sin puesto',
                    'estado' => $estado,
                    'incidencia_estado' => $incidenciaEstado,
                    'detalle' => $group->pluck('estado')->unique()->implode(', '),
                ];
            })
            ->sortByDesc('fecha')
            ->values();
    }

    private function estadoLlegada(?Attendance $entrada, string $horaEntradaBase, int $graciaMinutos): string
    {
        if (! $entrada) {
            return 'falta';
        }

        $base = Carbon::parse($horaEntradaBase);
        $tope = $base->copy()->addMinutes($graciaMinutos);

        if ($entrada->recorded_at->lt($base)) {
            return 'llego_temprano';
        }

        if ($entrada->recorded_at->lte($base)) {
            return 'puntual';
        }

        if ($entrada->recorded_at->lte($tope)) {
            return 'atraso';
        }

        return 'falta';
    }

    private function estadoSalida(?Attendance $salida, string $horaSalidaBase): string
    {
        if (! $salida) {
            return 'sin_salida';
        }

        $base = Carbon::parse($horaSalidaBase);

        if ($salida->recorded_at->lt($base)) {
            return 'salio_temprano';
        }

        if ($salida->recorded_at->gt($base)) {
            return 'se_fue_tarde';
        }

        return 'puntual';
    }

    private function estadoProfesor(array $estados): string
    {
        if (in_array('RETARDO', $estados, true)) {
            return 'retardo';
        }

        if (in_array('JUSTIFICADO', $estados, true)) {
            return 'justificado';
        }

        if (in_array('AUSENTE', $estados, true)) {
            return 'ausente';
        }

        return 'presente';
    }

    private function incidenciaEstadoEmpleado(Collection $incidencias, int $empleadoId, string $fecha): string
    {
        if ($empleadoId === 0) {
            return 'ninguna';
        }

        $datos = $incidencias->filter(function (Incidencia $incidencia) use ($empleadoId, $fecha): bool {
            return (int) $incidencia->empleado_id === $empleadoId
                && $incidencia->fecha_justificacion->toDateString() === $fecha;
        });

        if ($datos->contains('estado', 'aprobada')) {
            return 'autorizada';
        }

        if ($datos->contains('estado', 'pendiente')) {
            return 'pendiente';
        }

        return 'ninguna';
    }

    private function incidenciaEstadoProfesor(Collection $incidencias, string $claveProfesor, string $fecha): string
    {
        $datos = $incidencias->filter(function (Incidencia $incidencia) use ($claveProfesor, $fecha): bool {
            return $incidencia->profesor_clave === $claveProfesor
                && $incidencia->fecha_justificacion->toDateString() === $fecha;
        });

        if ($datos->contains('estado', 'aprobada')) {
            return 'autorizada';
        }

        if ($datos->contains('estado', 'pendiente')) {
            return 'pendiente';
        }

        return 'ninguna';
    }
}
