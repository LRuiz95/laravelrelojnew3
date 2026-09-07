<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

        $attendances = $query
            ->orderByDesc('recorded_at')
            ->paginate(25)
            ->withQueryString();

        return view('attendances.index', [
            'attendances' => $attendances,
            'devices' => Device::orderBy('name')->get(),
            'states' => Attendance::states(),
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
