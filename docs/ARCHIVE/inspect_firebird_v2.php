<?php
/**
 * Inspector de esquema Firebird 2.5 - Versión corregida
 */

// Load environment variables
require_once __DIR__ . '/core/Database.php';

try {
    // Get Firebird connection using Database singleton
    $pdo = Database::firebird();
    echo "✅ Conexión exitosa a Firebird\n\n";
} catch (Throwable $e) {
    die('❌ Error de conexión: ' . $e->getMessage());
}

function q(PDO $pdo, string $sql, array $params = []): array {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// Tablas clave del proyecto (nombres exactos como aparecen en PHP)
$keyTables = [
    'EMPLEADOS', 'PROFESORES', 'ALUMNOS', 'EMPLEADOS_ASISTENCIA',
    'GRUPOS', 'HORARIOS_DET', 'CURSOS', 'CURSOS_DET',
    'ALUMNOS_GRUPOS', 'ALUMNOS_KARDEX', 'ALUMNOS_NIVELES',
    'CICLOS', 'CFGSEDES', 'CFGTURNOS', 'CFGNIVELES',
    'CFGSESIONES', 'CFGPLANES_MST', 'CFGPLANES_DET',
    'CFGPLANES_ETAPAS', 'CFGPLANES_EVAL', 'CFGSTATUS',
    'CFGAULAS', 'EMPLEADOS_HORARIOS', 'EMPLEADOS_CFGHORARIOS',
    'EMPLEADOS_CFGHORARIOS_DET', 'EMPLEADOS_CONTRATOS_CAT'
];

echo "=== ESQUEMA REAL DE TABLAS DEL PROYECTO ===\n\n";

$report = [];
$report[] = "# Esquema Real Firebird - " . date('Y-m-d H:i:s');
$report[] = "";
$report[] = "| Tabla | Registros | PK | Timestamp/Fecha Modif | Sync Recomendada |";
$report[] = "|-------|-----------|-----|----------------------|------------------|";

foreach ($keyTables as $targetTable) {
    $targetTable = trim($targetTable);
    
    // Buscar tabla real (con trim)
    $tables = q($pdo, "
        SELECT TRIM(RDB\$RELATION_NAME) as TABLE_NAME
        FROM RDB\$RELATIONS
        WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL
    ");
    $realNames = array_map('trim', array_column($tables, 'TABLE_NAME'));
    
    $realTable = null;
    foreach ($realNames as $rn) {
        if (strcasecmp($rn, $targetTable) === 0) {
            $realTable = $rn;
            break;
        }
    }
    
    if (!$realTable) {
        echo "❌ $targetTable — NO ENCONTRADA\n";
        $report[] = "| $targetTable | — | — | — | **NO EXISTE** |";
        continue;
    }
    
    // Conteo
    $count = q($pdo, "SELECT COUNT(*) as CNT FROM $realTable");
    $rowCount = $count[0]['CNT'] ?? 0;
    
    // Columnas
    $columns = q($pdo, "
        SELECT 
            TRIM(RF.RDB\$FIELD_NAME) as COLUMN_NAME,
            F.RDB\$FIELD_TYPE as FIELD_TYPE,
            F.RDB\$FIELD_LENGTH as FIELD_LENGTH,
            F.RDB\$FIELD_PRECISION as FIELD_PRECISION,
            F.RDB\$FIELD_SCALE as FIELD_SCALE,
            RF.RDB\$NULL_FLAG as NOT_NULL,
            RF.RDB\$DEFAULT_SOURCE as DEFAULT_VALUE,
            CASE F.RDB\$FIELD_TYPE
                WHEN 7 THEN 'SMALLINT'
                WHEN 8 THEN 'INTEGER'
                WHEN 10 THEN 'FLOAT'
                WHEN 12 THEN 'DATE'
                WHEN 13 THEN 'TIME'
                WHEN 14 THEN 'CHAR'
                WHEN 16 THEN 'BIGINT'
                WHEN 27 THEN 'DOUBLE PRECISION'
                WHEN 35 THEN 'TIMESTAMP'
                WHEN 37 THEN 'VARCHAR'
                WHEN 261 THEN 'BLOB'
                ELSE 'UNKNOWN(' || F.RDB\$FIELD_TYPE || ')'
            END as TYPE_NAME
        FROM RDB\$RELATION_FIELDS RF
        JOIN RDB\$FIELDS F ON RF.RDB\$FIELD_SOURCE = F.RDB\$FIELD_NAME
        WHERE TRIM(RF.RDB\$RELATION_NAME) = ?
        ORDER BY RF.RDB\$FIELD_POSITION
    ", [$realTable]);
    
    // Primary Key
    $pk = q($pdo, "
        SELECT TRIM(SEG.RDB\$FIELD_NAME) as COLUMN_NAME
        FROM RDB\$INDEX_SEGMENTS SEG
        JOIN RDB\$INDICES IDX ON SEG.RDB\$INDEX_NAME = IDX.RDB\$INDEX_NAME
        JOIN RDB\$RELATION_CONSTRAINTS RC ON IDX.RDB\$INDEX_NAME = RC.RDB\$INDEX_NAME
        WHERE TRIM(RC.RDB\$RELATION_NAME) = ?
          AND RC.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY SEG.RDB\$FIELD_POSITION
    ", [$realTable]);
    
    // Buscar columnas timestamp/fecha modificación
    $tsCols = [];
    foreach ($columns as $col) {
        $colName = trim($col['COLUMN_NAME']);
        $type = $col['TYPE_NAME'];
        if (stripos($colName, 'FECHA') !== false 
            || stripos($colName, 'MODIFIC') !== false 
            || stripos($colName, 'ACTUALIZ') !== false
            || stripos($colName, 'TIMESTAMP') !== false
            || stripos($colName, 'VERSION') !== false
            || stripos($colName, 'CONSEC') !== false
            || stripos($colName, 'ULT') !== false
            || stripos($colName, 'CAMBIO') !== false) {
            if (in_array($type, ['TIMESTAMP', 'DATE', 'TIME'])) {
                $tsCols[] = $colName . ' (' . $type . ')';
            }
        }
    }
    
    $pkCols = array_map('trim', array_column($pk, 'COLUMN_NAME'));
    $pkStr = implode(', ', $pkCols) ?: 'SIN PK';
    $tsStr = implode(', ', $tsCols) ?: 'NINGUNO';
    
    // Determinar tipo de sync
    $syncType = 'COMPLETA';
    if (!empty($tsCols)) {
        if ($rowCount > 50000) {
            $syncType = 'INCREMENTAL (por ' . $tsCols[0] . ')';
        } else {
            $syncType = 'INCREMENTAL OPCIONAL';
        }
    } elseif ($rowCount < 1000) {
        $syncType = 'COMPLETA (catálogo)';
    }
    
    echo "✅ $realTable: $rowCount registros | PK: $pkStr | TS: $tsStr | Sync: $syncType\n";
    
    $report[] = "| $realTable | $rowCount | $pkStr | $tsStr | $syncType |";
}

// Guardar reporte
$reportContent = implode("\n", $report);
file_put_contents('firebird_schema_report.md', $reportContent);
echo "\n📄 Reporte guardado en firebird_schema_report.md\n";