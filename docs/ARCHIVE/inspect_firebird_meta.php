<?php
declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/core/Database.php';

try {
    // Get Firebird connection using Database singleton
    $pdo = Database::firebird();
} catch (Throwable $e) {
    die('Error de conexion: ' . $e->getMessage() . "\n");
}

// 1. Tablas
$tablas_rdb = $pdo->query("SELECT RDB\$RELATION_NAME AS T FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
echo "=== TABLAS DE USUARIO (" . count($tablas_rdb) . ") ===\n";
foreach ($tablas_rdb as $t) echo "  " . trim($t) . "\n";

// 2. RDB$FIELDS
echo "\n=== COLUMNAS DE RDB\$FIELDS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$FIELDS")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";

// 3. RDB$RELATION_FIELDS
echo "\n=== COLUMNAS DE RDB\$RELATION_FIELDS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$RELATION_FIELDS")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";

// 4. RDB$INDICES
echo "\n=== COLUMNAS DE RDB\$INDICES ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$INDICES")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";

// 5. RDB$RELATION_CONSTRAINTS
echo "\n=== COLUMNAS DE RDB\$RELATION_CONSTRAINTS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$RELATION_CONSTRAINTS")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";

// 6. RDB$INDEX_SEGMENTS
echo "\n=== COLUMNAS DE RDB\$INDEX_SEGMENTS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$INDEX_SEGMENTS")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";

// 7. RDB$TYPES
echo "\n=== RDB\$TYPES (tipos de campo) ===\n";
try {
    $types = $pdo->query("SELECT RDB\$TYPE, RDB\$TYPE_NAME FROM RDB\$TYPES WHERE RDB\$FIELD_NAME = 'RDB\$FIELD_TYPE'")->fetchAll(PDO::FETCH_ASSOC);
    echo "  " . count($types) . " tipos:\n";
    foreach ($types as $tp) echo "    " . trim($tp['RDB\$TYPE']) . " = " . trim($tp['RDB\$TYPE_NAME']) . "\n";
} catch (Throwable $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}

// 8. Sample: primera tabla
if (!empty($tablas_rdb)) {
    $primera = trim($tablas_rdb[0]);
    echo "\n=== CAMPOS DE: {$primera} ===\n";
    $sample = $pdo->query("SELECT FIRST 1 * FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = '{$primera}' ORDER BY RDB\$FIELD_POSITION")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($sample)) {
        echo "  Columnas: " . implode(', ', array_keys($sample[0])) . "\n";
        foreach ($sample[0] as $k => $v) {
            echo "    " . trim($k) . " = " . (is_null($v) ? 'NULL' : trim((string)$v)) . "\n";
        }
    }
}

// 9. Sample campos de EMPLEADOS
echo "\n=== CAMPOS DE: EMPLEADOS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = 'EMPLEADOS' ORDER BY RDB\$FIELD_POSITION")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) {
    echo "  Columnas: " . implode(', ', array_keys($rows[0])) . "\n";
    foreach ($rows[0] as $k => $v) {
        echo "    " . trim($k) . " = " . (is_null($v) ? 'NULL' : trim((string)$v)) . "\n";
    }
}
