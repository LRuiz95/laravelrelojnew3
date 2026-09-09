<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\DeviceSync;
use App\Models\Employee;
use App\Models\Fingerprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Provee datos contextuales al layout administrativo:
 * notificaciones, búsqueda global (Ctrl+K) y alertas globales.
 */
class AdminLayoutComposer
{
    public function compose(View $view): void
    {
        if (! Auth::check()) {
            return;
        }

        $view->with('dash', [
            'notifications' => $this->notifications(),
            'search' => $this->search(),
            'alerts' => $this->alerts(),
        ]);
    }

    public function notifications(): array
    {
        $user = Auth::user();
        $items = [];

        $devices = Device::count();
        $offline = Device::where('status', 'offline')->count();
        $unassigned = Attendance::whereNull('employee_id')->count();
        $employeesNoFp = Employee::whereDoesntHave('fingerprints')->count();
        $todayChecks = Attendance::whereDate('recorded_at', today())->count();
        $lastSync = DeviceSync::query()->latest()->first();
        $failedSyncs = DeviceSync::where('status', 'failed')->where('updated_at', '>=', now()->subDay())->count();
        $pendingFirebird = \App\Models\FirebirdSync::where('status', 'pending')->where('created_at', '<', now()->subMinutes(5))->count();
        $failedFirebird = \App\Models\FirebirdSync::where('status', 'failed')->where('updated_at', '>=', now()->subDay())->count();
        $lastCompletedFirebird = \App\Models\FirebirdSync::where('status', 'completed')->latest('finished_at')->first();
        $fingerprints = Fingerprint::count();

        $seed = 1;

        if ($pendingFirebird > 0) {
            $items[] = $this->item(
                'warning',
                $seed++,
                "{$pendingFirebird} sincronización(es) Firebird pendiente(s) >5 min",
                'La cola Firebird está detenida. Procesa la cola en /firebird.',
                'Firebird',
                route('firebird.index'),
                false,
                'FB'
            );
        }

        if ($failedFirebird > 0) {
            $failedMsg = \App\Models\FirebirdSync::where('status', 'failed')->latest()->first()?->error_message ?? 'Revisa el log.';
            $items[] = $this->item(
                'danger',
                $seed++,
                "{$failedFirebird} sincronización(es) Firebird fallida(s)",
                \Illuminate\Support\Str::limit($failedMsg, 80),
                'Firebird',
                route('firebird.index'),
                false,
                '!'
            );
        }

        if ($lastCompletedFirebird && $lastCompletedFirebird->finished_at && $lastCompletedFirebird->finished_at->gt(now()->subDay())) {
            $when = $lastCompletedFirebird->finished_at->diffForHumans();
            $items[] = $this->item(
                'success',
                $seed++,
                'Firebird sincronizado',
                " {$lastCompletedFirebird->created_count} creados, {$lastCompletedFirebird->updated_count} actualizados. {$when}.",
                'Firebird',
                route('firebird.sync', $lastCompletedFirebird),
                true,
                'OK'
            );
        }

        if ($failedSyncs > 0) {
            $items[] = $this->item(
                'alert',
                $seed++,
                "{$failedSyncs} sincronización(es) fallida(s)",
                'Revisa el estado de tus checadores y reintenta la operación.',
                'Sincronización',
                route('devices.index'),
                false,
                strtoupper(substr((string) $user->name, 0, 1))
            );
        }

        if ($offline > 0) {
            $items[] = $this->item(
                'danger',
                $seed++,
                $offline === 1 ? '1 checador sin conexión' : "{$offline} checadores sin conexión",
                'La conectividad parece interrumpida. Revisa la red biométrica.',
                'Red',
                route('devices.index'),
                false,
                strtoupper(substr((string) $user->name, 0, 1))
            );
        }

        if ($unassigned > 0) {
            $items[] = $this->item(
                'warning',
                $seed++,
                "{$unassigned} checada(s) sin asignar",
                'Existen registros que no corresponden a ningún empleado activo.',
                'Asistencias',
                route('attendances.index'),
                true,
                'ID'
            );
        }

        if ($employeesNoFp > 0) {
            $items[] = $this->item(
                'info',
                $seed++,
                "{$employeesNoFp} empleado(s) sin huella en el dispositivo",
                'Los empleados sin huella no pueden marcar en el checador.',
                'Empleados',
                route('employees.index'),
                true,
                'FP'
            );
        }

        if ($lastSync && $lastSync->status === 'completed') {
            $when = $lastSync->finished_at?->diffForHumans() ?? 'recientemente';
            $items[] = $this->item(
                'success',
                $seed++,
                'Sincronización completada',
                "Última operación: {$lastSync->operation_label}. Ejecutada {$when}.",
                'Sincronización',
                $lastSync->device_id ? route('devices.show', $lastSync->device_id) : route('devices.index'),
                true,
                'OK'
            );
        }

        $items[] = $this->item(
            'info',
            $seed++,
            $todayChecks === 1 ? '1 chequeo registrado hoy' : "{$todayChecks} chequeos registrados hoy",
            "Red activa con {$devices} checador(es), {$fingerprints} huella(s) y {$offline} fuera de línea.",
            'Resumen',
            route('dashboard'),
            true,
            'HOY'
        );

        return $items;
    }

    private function item(
        string $type,
        int $id,
        string $title,
        string $desc,
        string $category,
        ?string $url,
        bool $read,
        string $initials
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'desc' => $desc,
            'category' => $category,
            'url' => $url,
            'read' => $read,
            'initials' => $initials,
            'time' => now()->diffForHumans(),
        ];
    }

    private function search(): array
    {
        $isAdmin = Auth::user()->isAdmin();

        $devices = Device::orderBy('name')->limit(30)->get()
            ->map(fn (Device $d) => [
                'name' => $d->name,
                'ip' => $d->ip,
                'url' => route('devices.show', $d),
            ])->values();

        // Catálogo central: cada empleado puede estar enrolado en varios
        // checadores vía la pivote device_employee. La relación legada
        // device() ya no existe — usar devices() y derivar etiqueta/URL.
        $employees = Employee::query()
            ->with('devices:id,name')
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(function (Employee $e) use ($isAdmin): array {
                $firstDevice = $e->devices->first();

                return [
                    'id' => $e->user_id,
                    'name' => $e->name,
                    'device' => $e->devices->pluck('name')->implode(', ') ?: null,
                    'url' => $isAdmin
                        ? route('employees.edit', $e)
                        : ($firstDevice ? route('devices.show', $firstDevice) : '#'),
                ];
            })->values();

        $pages = [
            ['label' => 'Panel de control', 'keywords' => 'inicio dashboard resumen', 'url' => route('dashboard'), 'icon' => 'bi-speedometer2'],
            ['label' => 'Dispositivos', 'keywords' => 'checadores red biometrica', 'url' => route('devices.index'), 'icon' => 'bi-hdd-network'],
            ['label' => 'Empleados', 'keywords' => 'personas usuarios', 'url' => route('employees.index'), 'icon' => 'bi-people'],
            ['label' => 'Asistencias', 'keywords' => 'checadas registros marcado', 'url' => route('attendances.index'), 'icon' => 'bi-calendar-check'],
        ];

        if ($isAdmin) {
            array_push($pages,
                ['label' => 'Registrar dispositivo', 'keywords' => 'nuevo agregar checador', 'url' => route('devices.create'), 'icon' => 'bi-plus-circle'],
                ['label' => 'Nuevo empleado', 'keywords' => 'agregar persona', 'url' => route('employees.create'), 'icon' => 'bi-person-plus']
            );
        }
        array_push($pages,
            ['label' => 'Exportar asistencias (CSV)', 'keywords' => 'descargar excel reporte', 'url' => route('attendances.export'), 'icon' => 'bi-file-earmark-spreadsheet'],
            ['label' => 'Imprimir reporte', 'keywords' => 'pdf imprimir', 'url' => route('attendances.print'), 'icon' => 'bi-printer']
        );

        return [
            'pages' => $pages,
            'devices' => $devices,
            'employees' => $employees,
        ];
    }

    private function alerts(): array
    {
        $alerts = [];

        if (Device::count() === 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'bi-info-circle',
                'text' => 'Aún no hay checadores registrados. Empieza agregando tu primer dispositivo.',
                'action' => ['label' => 'Registrar dispositivo', 'url' => route('devices.create')],
                'key' => '',
            ];
        }

        $offline = Device::where('status', 'offline')->count();
        $total = Device::count();
        if ($offline > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-exclamation-triangle',
                'text' => "{$offline} de {$total} checador(es) sin conexión.",
                'action' => ['label' => 'Ver dispositivos', 'url' => route('devices.index')],
                'key' => 'dash-alert-offline',
            ];
        }

        return $alerts;
    }
}
