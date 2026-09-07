<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FirebirdSync;
use App\Jobs\FirebirdSyncJob;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FirebirdController extends Controller
{
    /**
     * Definición de grupos de tablas para la UI granular
     */
    protected function getCatalogGroups(): array
    {
        return [
            'base' => [
                'Sedes y Configuración' => [
                    ['fb' => 'CFGSEDES', 'mysql' => 'sedes', 'recommended' => true],
                    ['fb' => 'CFGNIVELES', 'mysql' => 'niveles', 'recommended' => true],
                    ['fb' => 'CFGTURNOS', 'mysql' => 'turnos', 'recommended' => true],
                    ['fb' => 'CICLOS', 'mysql' => 'ciclos', 'recommended' => true],
                ],
                'Planes y Materias' => [
                    ['fb' => 'CFGPLANES_MST', 'mysql' => 'planes', 'recommended' => true],
                    ['fb' => 'CFGPLANES_DET', 'mysql' => 'materias', 'recommended' => true],
                ],
                'Configuración Académica' => [
                    ['fb' => 'CFGSESIONES', 'mysql' => 'sesiones_base', 'recommended' => true],
                    ['fb' => 'CFGTIPOSEVALUACION', 'mysql' => 'metodos_eval', 'recommended' => true],
                    ['fb' => 'EMPLEADOS_CONTRATOS_CAT', 'mysql' => 'contratos', 'recommended' => true],
                ],
                'Catálogos Principales' => [
                    ['fb' => 'ALUMNOS', 'mysql' => 'alumnos', 'recommended' => true],
                    ['fb' => 'PROFESORES', 'mysql' => 'profesores', 'recommended' => true],
                ],
            ],
            'ciclo' => [
                'Grupos y Horarios' => [
                    ['fb' => 'GRUPOS', 'mysql' => 'grupos', 'recommended' => true],
                    ['fb' => 'HORARIOS_DET', 'mysql' => 'horarios_det', 'recommended' => true],
                    ['fb' => 'CURSOS', 'mysql' => 'cursos', 'recommended' => true],
                ],
                'Cursos y Materias' => [
                    ['fb' => 'CURSOS_DET', 'mysql' => 'cursos_det', 'recommended' => true],
                ],
                'Inscripciones por Ciclo' => [
                    ['fb' => 'ALUMNOS_GRUPOS', 'mysql' => 'alumnos_grupos', 'recommended' => true],
                ],
            ],
            'alumnos' => [
                'Datos de Alumnos' => [
                    ['fb' => 'ALUMNOS_NIVELES', 'mysql' => 'alumnos_niveles', 'recommended' => true],
                ],
            ],
        ];
    }

    public function index(): View
    {
        $syncs = FirebirdSync::latestFirst()->paginate(15);
        $ciclos = \App\Models\Academia\Ciclo::orderByDesc('inicial')->get();
        
        $stats = [
            'total' => FirebirdSync::count(),
            'completed' => FirebirdSync::where('status', 'completed')->count(),
            'failed' => FirebirdSync::where('status', 'failed')->count(),
            'running' => FirebirdSync::where('status', 'running')->count(),
        ];

        return view('firebird.index', [
            'syncs' => $syncs,
            'ciclos' => $ciclos,
            'stats' => $stats,
            'catalogGroups' => $this->getCatalogGroups(),
        ]);
    }

    public function startSync(Request $request): RedirectResponse
    {
        $request->validate([
            'operation' => 'required|in:sync_catalogos,sync_ciclo,sync_all,sync_custom',
            'ciclo' => 'nullable|string',
            'tables' => 'nullable|array',
            'tables.*' => 'string',
            'delete_orphans' => 'nullable|boolean',
            'skip_existing' => 'nullable|boolean',
        ]);

        $operation = $request->input('operation');
        $ciclo = $request->input('ciclo');
        $tables = $request->input('tables', []);
        $deleteOrphans = $request->boolean('delete_orphans', false);
        $skipExisting = $request->boolean('skip_existing', true);

        // Validate FK dependencies for custom sync
        $depWarnings = [];
        if ($operation === 'sync_custom' && !empty($tables)) {
            $depWarnings = \App\Services\SyncStrategies\CustomSyncStrategy::validateDependenciesStatic($tables);
        }

        // Create sync record
        $sync = FirebirdSync::create([
            'operation' => $operation,
            'ciclo' => $ciclo,
            'status' => 'pending',
            'options' => [
                'delete_orphans' => $deleteOrphans,
                'skip_existing' => $skipExisting,
                'tables' => $tables,
                'dep_warnings' => $depWarnings,
            ],
        ]);

        // Dispatch job asynchronously
        FirebirdSyncJob::dispatch($sync, $operation, $ciclo, $deleteOrphans, $tables, $skipExisting);

        $tablesInfo = $tables ? " (" . count($tables) . " tabla(s))" : '';
        $operationLabel = match($operation) {
            'sync_catalogos' => 'sincronización de catálogos',
            'sync_ciclo' => "sincronización de ciclo {$ciclo}",
            'sync_all' => 'sincronización completa',
            'sync_custom' => "sincronización personalizada{$tablesInfo}",
            default => $operation,
        };

        return redirect()->route('firebird.index')
            ->with('success', "Sincronización iniciada: {$operationLabel}. ID: #{$sync->id}")
            ->with('dep_warnings', $depWarnings);
    }

    public function sync(FirebirdSync $sync, Request $request): View
    {
        $query = $sync->items();
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        $items = $query->orderBy('id')->paginate(50)->withQueryString();

        return view('firebird.sync', [
            'sync' => $sync,
            'items' => $items,
        ]);
    }
}