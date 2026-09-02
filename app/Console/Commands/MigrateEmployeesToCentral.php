<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EmployeeCatalogMover;
use Illuminate\Console\Command;

class MigrateEmployeesToCentral extends Command
{
    protected $signature = 'migrate:employees-to-central
                            {--dry-run : Muestra el plan y los conflictos detectados sin modificar datos}';

    protected $description = 'Mueve los empleados legados al catálogo central y crea sus vínculos por dispositivo en device_employee';

    public function handle(EmployeeCatalogMover $mover): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $mover->hasLegacyColumns()) {
            $this->info('Los empleados ya viven en el catálogo central (columnas legadas ausentes). Nada por hacer.');

            return self::SUCCESS;
        }

        $this->info($dryRun
            ? 'Simulación de migración al catálogo central (--dry-run):'
            : 'Migrando empleados al catálogo central…');

        $summary = $mover->runInTransaction($dryRun);

        $this->table(['Concepto', 'Cantidad'], [
            ['Empleados revisados', $summary['employees']],
            ['Duplicados fusionados por user_id', $summary['duplicates_merged']],
            ['Filas pivote creadas', $summary['pivots_created']],
            ['Asistencias re-vinculadas', $summary['attendances_repointed']],
            ['Huellas movidas al empleado conservado', $summary['fingerprints_moved']],
            ['Huellas duplicadas eliminadas', $summary['fingerprints_dropped']],
            ['Tarjetas anuladas por conflicto [device_id, card_number]', $summary['cards_nulled']],
            ['UIDs en conflicto dentro del mismo equipo (requieren revisión)', $summary['uid_conflicts']],
        ]);

        if ($summary['uid_conflicts'] > 0) {
            $this->warn('Hay empleados con UID duplicado dentro del mismo checador: quedaron sin vínculo. Revísalos manualmente.');
        }

        $this->info($dryRun
            ? 'Simulación completada. No se modificó ningún dato.'
            : 'Migración completada: catálogo central + vínculos por dispositivo listos.');

        return self::SUCCESS;
    }
}
