<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FirebirdSync;
use App\Jobs\FirebirdSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FirebirdSyncCommand extends Command
{
    protected $signature = 'firebird:sync 
                            {operation? : Operación (sync_ciclo, sync_catalogos, sync_all)}
                            {ciclo? : Ciclo escolar (ej: 2025-2025-3)}
                            {--delete-orphans : Eliminar huérfanos en catálogos}
                            {--force : Forzar ejecución sin confirmación}
                            {--async : Ejecutar en background (queue)}';

    protected $description = 'Sincroniza Firebird → MySQL (ETL desde ProyectoBase)';

    public function handle(): int
    {
        $operation = $this->argument('operation') ?? 'sync_all';
        $ciclo = $this->argument('ciclo');
        $deleteOrphans = $this->option('delete-orphans');
        $force = $this->option('force');
        $async = $this->option('async');

        // Validar operación
        $validOperations = ['sync_ciclo', 'sync_catalogos', 'sync_all'];
        if (! in_array($operation, $validOperations, true)) {
            $this->error("Operación inválida: {$operation}. Válidas: " . implode(', ', $validOperations));
            return self::FAILURE;
        }

        // Validar ciclo si es sync_ciclo
        if ($operation === 'sync_ciclo' && ! $ciclo) {
            $this->error("La operación 'sync_ciclo' requiere el argumento <ciclo> (ej: 2025-2025-3)");
            return self::FAILURE;
        }

        // Confirmación
        if (! $force) {
            $msg = "Ejecutar {$operation}" . ($ciclo ? " para ciclo {$ciclo}" : '') . ($deleteOrphans ? ' CON eliminación de huérfanos' : '');
            if (! $this->confirm("¿{$msg}?")) {
                $this->info('Cancelado por el usuario.');
                return self::SUCCESS;
            }
        }

        // Crear registro de tracking
        $sync = FirebirdSync::create([
            'operation' => $operation,
            'ciclo' => $ciclo,
            'status' => 'pending',
            'options' => [
                'delete_orphans' => $deleteOrphans,
                'force' => $force,
            ],
        ]);

        $this->info("Sync creado: ID {$sync->id}");

        if ($async) {
            FirebirdSyncJob::dispatch($sync, $operation, $ciclo, $deleteOrphans);
            $this->info('Job despachado a queue. Ver progreso en BD o: php artisan queue:work');
            return self::SUCCESS;
        }

        // Síncrono (para testing/debug)
        $this->info('Ejecutando síncronamente...');
        $job = new \App\Jobs\FirebirdSyncJob($sync, $operation, $ciclo, $deleteOrphans);
        $job->handle();

        $sync->refresh();
        $this->printResult($sync);

        return $sync->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    protected function printResult(FirebirdSync $sync): void
    {
        $this->newLine();
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $sync->id],
                ['Operación', $sync->operation_label],
                ['Ciclo', $sync->ciclo ?? 'N/A'],
                ['Estado', $sync->status_label],
                ['Fase final', $sync->stage],
                ['Procesados', $sync->processed],
                ['Total', $sync->total],
                ['Insertados', $sync->created_count],
                ['Actualizados', $sync->updated_count],
                ['Eliminados', $sync->deleted_count],
                ['Duración', $sync->started_at && $sync->finished_at
                    ? $sync->started_at->diffForHumans($sync->finished_at)
                    : 'N/A'],
            ]
        );

        if ($sync->error_message) {
            $this->error("Error: {$sync->error_message}");
        }

        if (! empty($sync->log)) {
            $this->newLine();
            $this->info('Log detallado:');
            foreach ($sync->log as $entry) {
                $type = $entry['tipo'] ?? 'info';
                $msg = $entry['msg'] ?? json_encode($entry);
                match ($type) {
                    'ok' => $this->line("<fg=green>✓</> {$msg}"),
                    'error' => $this->line("<fg=red>✗</> {$msg}"),
                    'info' => $this->line("<fg=cyan>ℹ</> {$msg}"),
                    'skip' => $this->line("<fg=yellow>⊘</> {$msg}"),
                    default => $this->line("  {$msg}"),
                };
            }
        }
    }
}
