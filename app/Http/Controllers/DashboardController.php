<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Fingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Rangos disponibles para la gráfica de tendencia */
    private const RANGES = [
        'hoy' => 'Hoy',
        '7d' => '7 días',
        '30d' => '30 días',
        '12m' => '12 meses',
    ];

    public function index(Request $request): View
    {
        $rango = $request->query('rango');
        if (! array_key_exists($rango, self::RANGES)) {
            $rango = '7d';
        }

        return view('dashboard', [
            'kpis' => $this->kpis(),
            'pipeline' => $this->pipeline(),
            'donut' => $this->donut(),
            'trend' => $this->trend($rango),
            'rango' => $rango,
            'rangos' => self::RANGES,
            'recent' => Attendance::with(['employee', 'device'])
                ->orderByDesc('recorded_at')
                ->limit(8)
                ->get(),
            'todayInfo' => $this->todayInfo(),
        ]);
    }

    private function kpis(): array
    {
        // Analíticas centradas en el día en curso (lo que RH necesita ver
        // primero), con conectividad de checadores como quinto indicador.
        // Optimization: conditional aggregation reduces 7+ queries to 3.
        $todayAgg = Attendance::whereDate('recorded_at', today())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN type IN (0, 4) THEN 1 ELSE 0 END) as ins,
                SUM(CASE WHEN type IN (1, 5) THEN 1 ELSE 0 END) as outs,
                COUNT(DISTINCT employee_id) as employees
            ')->first();

        $yesterdayAgg = Attendance::whereDate('recorded_at', today()->subDay())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN type IN (0, 4) THEN 1 ELSE 0 END) as ins,
                SUM(CASE WHEN type IN (1, 5) THEN 1 ELSE 0 END) as outs
            ')->first();

        $employeesTotal = Employee::count();

        $deviceAgg = Device::selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "online" THEN 1 ELSE 0 END) as online')
            ->first();

        $today = (int) $todayAgg->total;
        $yesterday = (int) $yesterdayAgg->total;
        $employeesToday = (int) $todayAgg->employees;
        $ins = (int) $todayAgg->ins;
        $insYesterday = (int) $yesterdayAgg->ins;
        $outs = (int) $todayAgg->outs;
        $outsYesterday = (int) $yesterdayAgg->outs;
        $deviceCount = (int) $deviceAgg->total;
        $online = (int) $deviceAgg->online;

        return [
            [
                'value' => $today,
                'label' => 'Chequeos de hoy',
                'color' => 'orange',
                'icon' => 'bi-calendar-check',
                'trend' => $this->pct($today, max(1, $yesterday)),
                'spark' => [12, 15, 18, 20, 19, $today ?: 0],
            ],
            [
                'value' => $employeesToday,
                'label' => 'Empleados que checaron hoy',
                'caption' => "de {$employeesTotal} sincronizados",
                'color' => 'purple',
                'icon' => 'bi-people',
                'trend' => ['dir' => 'flat', 'value' => 0],
                'spark' => [5, 8, 9, 12, 14, $employeesToday ?: 0],
            ],
            [
                'value' => $ins,
                'label' => 'Entradas hoy',
                'color' => 'green',
                'icon' => 'bi-box-arrow-in-right',
                'trend' => $this->pct($ins, max(1, $insYesterday)),
                'spark' => [8, 11, 9, 14, 13, $ins ?: 0],
            ],
            [
                'value' => $outs,
                'label' => 'Salidas hoy',
                'color' => 'blue',
                'icon' => 'bi-box-arrow-right',
                'trend' => $this->pct($outs, max(1, $outsYesterday)),
                'spark' => [6, 9, 12, 10, 14, $outs ?: 0],
            ],
            [
                'value' => $online,
                'label' => 'Checadores en línea',
                'caption' => "de {$deviceCount} registrados",
                'color' => 'teal',
                'icon' => 'bi-wifi',
                'trend' => ['dir' => 'flat', 'value' => 0],
                'spark' => [8, 10, 11, 15, 16, $online ?: 0],
            ],
        ];
    }

    /** KPIs del día con tendencia (hoy vs ayer) para refresco externo */
    /** Endpoint del refresco periódico: lectura DIRECTA de BD, sin caché */
    public function kpisJson(): JsonResponse
    {
        return response()->json([
            'kpis' => collect($this->kpis())
                ->map(fn (array $kpi): array => [
                    'value' => $kpi['value'],
                    'label' => $kpi['label'],
                    'caption' => $kpi['caption'] ?? null,
                    'trend' => $kpi['trend'],
                ])->all(),
        ]);
    }

    /** Pipeline con las 6 categorías fijas del sistema */
    private function pipeline(): array
    {
        // Optimization: combine attendance queries into one conditional aggregation.
        $attAgg = Attendance::selectRaw('
            SUM(CASE WHEN employee_id IS NULL THEN 1 ELSE 0 END) as unassigned,
            SUM(CASE WHEN DATE(recorded_at) = CURDATE() THEN 1 ELSE 0 END) as today
        ')->first();

        $unassigned = (int) $attAgg->unassigned;
        $today = (int) $attAgg->today;

        // Activo = con al menos un enrolamiento vivo (la columna active vive
        // ahora en la pivote device_employee).
        // OJO: wherePivot() solo existe en el objeto relación; dentro del
        // closure de whereHas() hay que calificar la columna manualmente o el
        // dynamic-where lo convierte en where('pivot', ...) y MySQL revienta.
        $active = Employee::query()
            ->whereHas('devices', fn ($q) => $q->where('device_employee.active', true))
            ->count();
        $fingerprints = Fingerprint::count();
        $offline = Device::where('status', 'offline')->count();
        $deviceTotal = Device::count();
        $employeeTotal = Employee::count();

        return [
            ['label' => 'Dispositivos registrados', 'value' => $deviceTotal,           'color' => 'blue',   'icon' => 'bi-hdd-network',   'bar' => 100],
            ['label' => 'Checadas sin asignar',     'value' => $unassigned,            'color' => 'orange', 'icon' => 'bi-question-circle', 'bar' => min(100, $unassigned * 4)],
            ['label' => 'Empleados activos',        'value' => $active,                'color' => 'purple', 'icon' => 'bi-person-check',   'bar' => min(100, (int) (($active / max(1, $employeeTotal)) * 100))],
            ['label' => 'Huellas protegidas',       'value' => $fingerprints,          'color' => 'pink',   'icon' => 'bi-fingerprint',    'bar' => min(100, $fingerprints * 3)],
            ['label' => 'Checadores sin conexión',  'value' => $offline,               'color' => 'red',    'icon' => 'bi-wifi-off',       'bar' => min(100, (int) (($offline / max(1, $deviceTotal)) * 100))],
            ['label' => 'Chequeos hoy',             'value' => $today,                 'color' => 'green',  'icon' => 'bi-calendar-check', 'bar' => min(100, $today * 2)],
        ];
    }

    /** Donut: distribución por tipo de marcado SOLO del día en curso */
    private function donut(): array
    {
        // El modo real del checado viaja en "type" (state viene constante en
        // este firmware — ver Attendance::punchStatus()).
        $counts = Attendance::whereDate('recorded_at', today())
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type')
            ->all();

        $defs = [
            0 => ['Entrada', 'blue'],
            1 => ['Salida', 'green'],
            2 => ['Salida a descanso', 'orange'],
            3 => ['Regreso de descanso', 'purple'],
            4 => ['Entrada hrs extra', 'pink'],
            5 => ['Salida hrs extra', 'lavender'],
            255 => ['Sin definir', 'gray'],
        ];

        $segments = [];
        foreach ($defs as $state => [$label, $color]) {
            $value = $counts[$state] ?? 0;
            if ($value > 0) {
                $segments[] = ['label' => $label, 'value' => $value, 'color' => $color];
            }
        }
        usort($segments, fn ($a, $b) => $b['value'] <=> $a['value']);

        return [
            'segments' => $segments,
            'total' => array_sum(array_column($segments, 'value')),
        ];
    }

    /** Tendencia de chequeos según el rango elegido (hoy / 7d / 30d / 12m) */
    private function trend(string $range): array
    {
        return match ($range) {
            'hoy' => $this->hourlyToday(),
            '12m' => $this->monthlyTrend(12),
            '30d' => $this->dailyTrend(30),
            default => $this->dailyTrend(7),
        };
    }

    /** Chequeos del día en curso, hora por hora (00:00–23:00) */
    private function hourlyToday(): array
    {
        $counts = Attendance::whereDate('recorded_at', today())
            ->selectRaw('HOUR(recorded_at) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        return collect(range(0, 23))->map(fn (int $hour): array => [
            'label' => sprintf('%02d:00', $hour),
            'value' => (int) ($counts[$hour] ?? 0),
        ])->all();
    }

    /** Chequeos diarios de los últimos N días (incluye hoy) */
    private function dailyTrend(int $days): array
    {
        $start = today()->subDays($days - 1)->startOfDay();
        $counts = Attendance::whereBetween('recorded_at', [$start, now()])
            ->selectRaw('DATE(recorded_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range($days - 1, 0, -1))->map(function (int $back) use ($counts): array {
            $day = today()->subDays($back);

            return [
                'label' => $day->locale('es')->isoFormat('D MMM'),
                'value' => (int) ($counts[$day->toDateString()] ?? 0),
            ];
        })->all();
    }

    /** Chequeos mensuales de los últimos N meses (incluye el mes actual) */
    private function monthlyTrend(int $months): array
    {
        $start = today()->subMonths($months - 1)->startOfMonth();
        $counts = Attendance::whereBetween('recorded_at', [$start, now()])
            ->selectRaw("DATE_FORMAT(recorded_at, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect(range($months - 1, 0, -1))->map(function (int $back) use ($counts): array {
            $month = today()->subMonths($back);

            return [
                'label' => $month->locale('es')->isoFormat('MMM YY'),
                'value' => (int) ($counts[$month->format('Y-m')] ?? 0),
            ];
        })->all();
    }

    /** Banner de contexto: el día en curso, con última actividad global como respaldo */
    private function todayInfo(): array
    {
        // Optimization: single query with aggregation for all today's metrics.
        $todayAgg = Attendance::whereDate('recorded_at', today())
            ->selectRaw('
                COUNT(*) as checks,
                COUNT(DISTINCT employee_id) as employees,
                MIN(recorded_at) as first_check,
                MAX(recorded_at) as last_check
            ')->first();

        $checks = (int) $todayAgg->checks;
        $employees = (int) $todayAgg->employees;
        $firstToday = $todayAgg->first_check ? \Illuminate\Support\Carbon::parse($todayAgg->first_check) : null;
        $lastToday = $todayAgg->last_check ? \Illuminate\Support\Carbon::parse($todayAgg->last_check) : null;

        return [
            'label' => 'Ciclo activo de hoy',
            'date' => today()->locale('es')->isoFormat('D MMMM YYYY'),
            'checks' => $checks,
            'employees' => $employees,
            'hasChecksToday' => $checks > 0,
            'firstCheck' => $firstToday,
            'lastCheck' => $lastToday,
            // Respaldo informativo: aunque hoy no haya checadas, el usuario
            // quiere saber cuándo fue la última actividad real del sistema.
            'lastActivity' => $lastToday ?? \Illuminate\Support\Carbon::parse(Attendance::latest('recorded_at')->value('recorded_at')),
        ];
    }

    private function pct(int $current, int $previous, bool $invert = false): array
    {
        if ($previous <= 0) {
            return ['dir' => 'flat', 'value' => 0];
        }
        $diff = (int) round((($current - $previous) / $previous) * 100);
        if ($diff === 0) {
            return ['dir' => 'flat', 'value' => 0];
        }
        $dir = $diff > 0 ? ($invert ? 'down' : 'up') : ($invert ? 'up' : 'down');

        return ['dir' => $dir, 'value' => abs($diff)];
    }
}
