<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FirebirdSync;
use App\Jobs\FirebirdSyncJob;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
                'Nómina' => [
                    ['fb' => 'EMPLEADOS', 'mysql' => 'employees', 'recommended' => true],
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

        $runningSync = \App\Models\FirebirdSync::where('status', 'running')->latest()->first();

        $pendingCount = FirebirdSync::where('status','pending')->count();
        $hasWorker = $this->hasWorker();

        return view('firebird.index', [
            'syncs' => $syncs,
            'ciclos' => $ciclos,
            'stats' => $stats,
            'catalogGroups' => $this->getCatalogGroups(),
            'runningSync' => $runningSync,
            'pendingCount' => $pendingCount,
            'hasWorker' => $hasWorker,
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

    public function status(FirebirdSync $sync): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'id' => $sync->id,
            'status' => $sync->status,
            'stage' => $sync->stage,
            'processed' => $sync->processed,
            'total' => $sync->total,
            'operation' => $sync->operation,
            'operation_label' => $sync->operationLabel,
            'started_at' => $sync->started_at?->format('H:i:s'),
            'finished_at' => $sync->finished_at?->format('H:i:s'),
        ]);
    }

    /**
     * Health-check: detecta si hay un worker de cola corriendo actualmente.
     * Retorna true si hay jobs reservados hace menos de 2 minutos.
     */
    protected function hasWorker(): bool
    {
        return \Illuminate\Support\Facades\DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '>', now()->subMinutes(2))
            ->exists();
    }

    /**
     * Ejecuta sincronizaciones pendientes de forma sincrónica (Fase 1 fix).
     * Procesa uno por request para evitar bloqueos de 3600s en Apache.
     * Usa runSync() directo (set_time_limit 0) en lugar de Artisan::call queue:work:
     * - runSync evita payload LIKE y respeta handleFailure/finalizeSync sin pasar por queue.
     * - ETL catálogos típico ~5s (probado 13858 rows en 5s), dentro de max_execution_time.
     * - Si ETL grande (>300s) excede Apache, Chrome mostrará timeout pero sync ya completó en DB;
     *   usuario recarga y ve completed. Alternativa Artisan::call también bloquea request con mismo riesgo.
     *   Para producción con Supervisor, los jobs se procesan async sin usar este endpoint.
     */
    public function executePending(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $pending = FirebirdSync::where('status','pending')->orderBy('id')->get();
        if ($pending->isEmpty()) {
            $msg = 'No hay sincronizaciones pendientes.';
            return $request->expectsJson() ? response()->json(['message'=>$msg]) : redirect()->route('firebird.index')->with('info', $msg);
        }
        $processed = 0;
        foreach ($pending as $sync) {
            try {
                set_time_limit(0);
                // Ejecuta sincrónicamente el job asociado; respeta reintentos del job via handle()->catch
                \App\Jobs\FirebirdSyncJob::runSync($sync, $sync->options['operation'] ?? $sync->operation, $sync->ciclo, (bool)($sync->options['delete_orphans'] ?? false), (array)($sync->options['tables'] ?? []), (bool)($sync->options['skip_existing'] ?? true));
                // Limpia el job de la tabla jobs si aún existe (evita duplicado) — payload es PHP serialize con ModelIdentifier
                \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('payload', 'like', '%FirebirdSyncJob%')
                    ->where('payload', 'like', '%s:2:"id";i:'.$sync->id.';%')
                    ->delete();
                $processed++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('executePending failed', ['sync_id'=>$sync->id, 'error'=>$e->getMessage()]);
                // handleFailure ya marca failed; continua con siguiente
            }
            // Solo procesa uno por click para no bloquear demasiado (usuario puede re-clickear)
            break;
        }
        // Solo procesa uno por click para no bloquear demasiado (usuario puede re-clickear)
        $msg = $processed ? "Procesada sincronización pendiente (#{$pending->first()->id})." : 'No se pudo procesar.';
        return $request->expectsJson() ? response()->json(['message'=>$msg,'processed'=>$processed]) : redirect()->route('firebird.index')->with($processed ? 'success' : 'error', $msg);
    }

    public function cancel(FirebirdSync $sync, Request $request) { 
        if (!in_array($sync->status,['pending','running'],true)) return back()->with('info','Ya terminó.'); 
        $sync->update(['status'=>'cancelled','stage'=>'Cancelada por el usuario','finished_at'=>now(),'error_message'=>'Cancelada por el usuario.']); 
        \Illuminate\Support\Facades\DB::table('jobs')->where('payload','like','%FirebirdSyncJob%')->where('payload','like','%s:2:"id";i:'.$sync->id.';%')->delete(); 
        return $request->expectsJson()?response()->json(['message'=>'Cancelada.']):redirect()->route('firebird.index')->with('success','Sincronización cancelada.'); 
    }

    public function retry(FirebirdSync $sync, Request $request) { 
        if ($sync->status!=='failed') return back()->with('error','Solo fallidas.'); 
        $new = FirebirdSync::create(['operation'=>$sync->operation,'ciclo'=>$sync->ciclo,'status'=>'pending','options'=>$sync->options]); 
        \App\Jobs\FirebirdSyncJob::dispatch($new, $new->operation, $new->ciclo, (bool)($new->options['delete_orphans']??false), (array)($new->options['tables']??[]), (bool)($new->options['skip_existing']??true)); 
        return $request->expectsJson()?response()->json(['message'=>'Reintentando','id'=>$new->id]):redirect()->route('firebird.index')->with('success','Reintento encolado #'.$new->id); 
    }

    public function destroy(FirebirdSync $sync, Request $request) { 
        if (in_array($sync->status,['pending','running'],true)) return response()->json(['message'=>'Cancela antes de eliminar.'],422); 
        $sync->delete(); 
        return $request->expectsJson()?response()->json(['message'=>'Eliminado.']):redirect()->route('firebird.index')->with('success','Eliminado.'); 
    }
}