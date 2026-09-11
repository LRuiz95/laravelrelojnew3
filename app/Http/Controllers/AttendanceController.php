<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Attendance::with(['employee', 'device']);

        // Usar asistencias únicas (por empleado + fecha) solo sin filtros;
        // CON filtros se listan los registros crudos que cumplan el criterio.
        $hasFilters = collect(['device_id', 'type', 'from', 'to'])
            ->map(fn ($key) => $request->query($key))
            ->contains(fn ($value) => $value !== null && $value !== '');

        if ($hasFilters) {
            $this->applyFilters($query, $request);
        }

        $rawAttendances = $query
            ->orderBy('recorded_at')
            ->get();

        $employeesByUserId = Employee::query()
            ->whereIn('user_id', $rawAttendances->pluck('user_id')->filter()->unique())
            ->get()
            ->keyBy(fn (Employee $employee) => (string) $employee->user_id);

        $rawAttendances->each(function (Attendance $attendance) use ($employeesByUserId): void {
            if (! $attendance->relationLoaded('employee') || ! $attendance->employee) {
                $employee = $employeesByUserId->get((string) $attendance->user_id);
                if ($employee) {
                    $attendance->setRelation('employee', $employee);
                }
            }
        });

        $dailyRows = $rawAttendances
            ->groupBy(fn (Attendance $attendance) => ($attendance->employee?->id ?? 'user-'.$attendance->user_id).':'.$attendance->recorded_at->toDateString())
            ->map(function ($records) {
                $first = $records->first();
                $punches = $records->groupBy(fn (Attendance $attendance) => $attendance->punchStatus())
                    ->map(fn ($items) => $items->sortBy('recorded_at')->first());

                return (object) [
                    'date' => $first->recorded_at->toDateString(),
                    'employee' => $first->employee,
                    'user_id' => $first->user_id,
                    'device_names' => $records->map(fn ($item) => $item->device?->name)->filter()->unique()->values(),
                    'punches' => $punches,
                ];
            })
            ->sortByDesc(fn ($row) => $row->date.' '.($row->employee?->name ?? $row->user_id));

        $page = LengthAwarePaginator::resolveCurrentPage('employee_page');
        $perPage = 25;
        $attendances = new LengthAwarePaginator(
            $dailyRows->forPage($page, $perPage)->values(),
            $dailyRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'employee_page', 'query' => $request->query()],
        );

        $classAttendances = DB::table('docentes_asistencias as da')
            ->leftJoin('profesores as p', 'p.clave_profesor', '=', 'da.clave_profesor')
            ->leftJoin('grupos as g', function ($join) {
                $join->on('g.codigo_grupo', '=', 'da.codigo_grupo')
                    ->on('g.inicial', '=', 'da.inicial')
                    ->on('g.final', '=', 'da.final')
                    ->on('g.periodo', '=', 'da.periodo');
            })
            ->leftJoin('materias as m', 'm.clave_asignatura', '=', 'da.clave_asignatura')
            ->leftJoin('sedes as s', 's.id_campus', '=', 'g.id_campus')
            ->leftJoin('horarios_det as h', function ($join) {
                $join->on('h.codigo_grupo', '=', 'da.codigo_grupo')
                    ->on('h.inicial', '=', 'da.inicial')
                    ->on('h.final', '=', 'da.final')
                    ->on('h.periodo', '=', 'da.periodo')
                    ->on('h.clave_profesor', '=', 'da.clave_profesor')
                    ->on('h.clave_asignatura', '=', 'da.clave_asignatura')
                    ->on('h.dia', '=', 'da.dia')
                    ->on('h.sesion', '=', 'da.sesion');
            })
            ->leftJoin('sesiones_base as sb', function ($join) {
                $join->on('sb.sesion', '=', 'da.sesion')
                    ->on('sb.nivel', '=', 'g.nivel')
                    ->on('sb.turno', '=', 'g.turno');
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('da.fecha', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('da.fecha', '<=', $request->query('to')))
            ->select([
                'da.*',
                'p.nombre_profesor', 'p.paterno as profesor_paterno', 'p.materno as profesor_materno',
                'g.grado', 'g.turno', 'g.nivel', 'g.id_campus',
                'g.carrera', 'h.edificio', 'h.aula', 'sb.hora_inicio', 'sb.hora_fin',
                'm.nombre_asignatura', 's.descripcion as sede_nombre',
            ])
            ->orderByDesc('da.fecha')
            ->orderBy('da.sesion')
            ->paginate(25, ['*'], 'class_page')
            ->appends($request->query());

        return view('attendances.index', [
            'attendances' => $attendances,
            'devices' => Device::orderBy('name')->get(),
            'states' => Attendance::states(),
            'classAttendances' => $classAttendances,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Attendance::with(['employee', 'device'])->orderBy('recorded_at');
        $this->applyFilters($query, $request);

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha y hora', 'Empleado', 'ID', 'Marcado', 'Tipo', 'Dispositivo']);
            $query->chunk(500, function ($attendances) use ($handle): void {
                foreach ($attendances as $attendance) {
                    fputcsv($handle, [
                        $attendance->recorded_at->format('Y-m-d H:i:s'),
                        $attendance->employee?->name ?? 'Sin asignar',
                        $attendance->user_id,
                        $attendance->stateLabel(),
                        $attendance->type,
                        $attendance->device?->name ?? '',
                    ]);
                }
            });
            fclose($handle);
        }, 'asistencias-'.now()->format('Y-m-d_H-i').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request): View
    {
        $query = Attendance::with(['employee', 'device'])->orderBy('recorded_at');
        $this->applyFilters($query, $request);

        return view('attendances.print', ['attendances' => $query->get()]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($deviceId = $request->query('device_id')) {
            $query->where('device_id', $deviceId);
        }
        if (($type = $request->query('type')) !== null && $type !== '') {
            // El parámetro "type" del formulario selecciona el MODO de
            // checado, que en este firmware vive en la columna "type"
            // (state viene constante en 1 — ver Attendance::punchStatus()).
            $query->where('type', (int) $type);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('recorded_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('recorded_at', '<=', $to);
        }
    }
}
