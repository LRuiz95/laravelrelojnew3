<?php

declare(strict_types=1);

namespace App\Services\SyncStrategies;

use App\Models\FirebirdSync;
use App\Services\FirebirdReader;
use PDO;

interface SyncStrategyInterface
{
    /**
     * Ejecuta la estrategia de sincronización
     *
     * @param FirebirdReader $firebirdReader Conexión a Firebird
     * @param FirebirdSync $sync Modelo de tracking
     * @param string|null $ciclo Ciclo escolar (ej: 2025-2025-3)
     * @param bool $deleteOrphans Eliminar huérfanos
     * @param callable $progressCallback Callback(procesados, total, etapa)
     * @return array ['created'=>int, 'updated'=>int, 'deleted'=>int, 'processed'=>int, 'total'=>int, 'log'=>array, 'errors'=>array]
     */
    public function execute(
        FirebirdReader $firebirdReader,
        FirebirdSync $sync,
        ?string $ciclo,
        bool $deleteOrphans,
        callable $progressCallback
    ): array;
}