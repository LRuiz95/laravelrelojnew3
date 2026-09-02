<?php
/**
 * Inspector de esquema Firebird 2.5
 * Genera reporte completo de tablas, columnas, PKs, FKs, índices y conteos
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

// ============================================================
// 1. LISTAR TODAS LAS TABLAS DE USUARIO
// ============================================================
echo "=== 1. TABLAS DE USUARIO ===\n";
$tables = q($pdo, "
    SELECT RDB\$RELATION_NAME as TABLE_NAME
    FROM RDB\$RELATIONS
    WHERE RDB\$SYSTEM_FLAG = 0
      AND RDB\$VIEW_BLR IS NULL
    ORDER BY RDB\$RELATION_NAME
");

$tableNames = array_column($tables, 'TABLE_NAME');
echo "Total tablas: " . count($tableNames) . "\n";
echo implode(", ", $tableNames) . "\n\n";

// ============================================================
// 2. ESQUEMA DETALLADO POR TABLA
// ============================================================
echo "=== 2. ESQUEMA DETALLADO ===\n\n";

foreach ($tableNames as $tableName) {
    $tableName = trim($tableName);
    
    // Columnas
    $columns = q($pdo, "
        SELECT 
            RF.RDB\$FIELD_NAME as COLUMN_NAME,
            F.RDB\$FIELD_TYPE as FIELD_TYPE,
            F.RDB\$FIELD_LENGTH as FIELD_LENGTH,
            F.RDB\$FIELD_PRECISION as FIELD_PRECISION,
            F.RDB\$FIELD_SCALE as FIELD_SCALE,
            RF.RDB\$NULL_FLAG as NOT_NULL,
            RF.RDB\$DEFAULT_SOURCE as DEFAULT_VALUE,
            CASE F.RDB\$FIELD_TYPE
                WHEN 7 THEN 'SMALLINT'
                WHEN 8 THEN 'INTEGER'
                WHEN 9 THEN 'QUAD'
                WHEN 10 THEN 'FLOAT'
                WHEN 12 THEN 'DATE'
                WHEN 13 THEN 'TIME'
                WHEN 14 THEN 'CHAR'
                WHEN 16 THEN 'BIGINT'
                WHEN 27 THEN 'DOUBLE PRECISION'
                WHEN 35 THEN 'TIMESTAMP'
                WHEN 37 THEN 'VARCHAR'
                WHEN 40 THEN 'CSTRING'
                WHEN 261 THEN 'BLOB'
                ELSE 'UNKNOWN(' || F.RDB\$FIELD_TYPE || ')'
            END as TYPE_NAME
        FROM RDB\$RELATION_FIELDS RF
        JOIN RDB\$FIELDS F ON RF.RDB\$FIELD_SOURCE = F.RDB\$FIELD_NAME
        WHERE RF.RDB\$RELATION_NAME = ?
        ORDER BY RF.RDB\$FIELD_POSITION
    ", [$tableName]);
    
    // Primary Key
    $pk = q($pdo, "
        SELECT SEG.RDB\$FIELD_NAME as COLUMN_NAME
        FROM RDB\$INDEX_SEGMENTS SEG
        JOIN RDB\$INDICES IDX ON SEG.RDB\$INDEX_NAME = IDX.RDB\$INDEX_NAME
        JOIN RDB\$RELATION_CONSTRAINTS RC ON IDX.RDB\$INDEX_NAME = RC.RDB\$INDEX_NAME
        WHERE RC.RDB\$RELATION_NAME = ?
          AND RC.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY SEG.RDB\$FIELD_POSITION
    ", [$tableName]);
    
    // Foreign Keys
    $fks = q($pdo, "
        SELECT 
            RC.RDB\$CONSTRAINT_NAME as FK_NAME,
            SEG.RDB\$FIELD_NAME as COLUMN_NAME,
            RC2.RDB\$RELATION_NAME as REF_TABLE,
            SEG2.RDB\$FIELD_NAME as REF_COLUMN
        FROM RDB\$RELATION_CONSTRAINTS RC
        JOIN RDB\$INDEX_SEGMENTS SEG ON RC.RDB\$INDEX_NAME = SEG.RDB\$INDEX_NAME
        JOIN RDB\$REF_CONSTRAINTS REFC ON RC.RDB\$CONSTRAINT_NAME = REFC.RDB\$CONSTRAINT_NAME
        JOIN RDB\$RELATION_CONSTRAINTS RC2 ON REFC.RDB\$CONST_NAME_UQ = RC2.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS SEG2 ON RC2.RDB\$INDEX_NAME = SEG2.RDB\$INDEX_NAME
        WHERE RC.RDB\$RELATION_NAME = ?
          AND RC.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
        ORDER BY RC.RDB\$CONSTRAINT_NAME, SEG.RDB\$FIELD_POSITION
    ", [$tableName]);
    
    // Índices
    $indexes = q($pdo, "
        SELECT 
            IDX.RDB\$INDEX_NAME as INDEX_NAME,
            IDX.RDB\$UNIQUE_FLAG as IS_UNIQUE,
            LIST(SEG.RDB\$FIELD_NAME, ', ') as COLUMNS
        FROM RDB\$INDICES IDX
        JOIN RDB\$INDEX_SEGMENTS SEG ON IDX.RDB\$INDEX_NAME = SEG.RDB\$INDEX_NAME
        WHERE IDX.RDB\$RELATION_NAME = ?
          AND IDX.RDB\$SYSTEM_FLAG = 0
        GROUP BY IDX.RDB\$INDEX_NAME, IDX.RDB\$UNIQUE_FLAG
        ORDER BY IDX.RDB\$INDEX_NAME
    ", [$tableName]);
    
    // Conteo de filas
    $count = q($pdo, "SELECT COUNT(*) as CNT FROM " . $tableName);
    $rowCount = $count[0]['CNT'] ?? 0;
    
    // Buscar columnas de timestamp/fecha de modificación
    $timestampCols = [];
    foreach ($columns as $col) {
        $colName = trim($col['COLUMN_NAME']);
        if (stripos($colName, 'FECHA') !== false 
            || stripos($colName, 'MODIFIC') !== false 
            || stripos($colName, 'ACTUALIZ') !== false
            || stripos($colName, 'TIMESTAMP') !== false
            || stripos($colName, 'VERSION') !== false
            || stripos($colName, 'CONSEC') !== false
            || stripos($colName, 'ULT') !== false) {
            $timestampCols[] = $colName . ' (' . $col['TYPE_NAME'] . ')';
        }
    }
    
    // Output
    echo "┌─────────────────────────────────────────────────────────────\n";
    echo "│ TABLE: $tableName ($rowCount registros)\n";
    if (!empty($timestampCols)) {
        echo "│ ⏰ CANDIDATAS A TIMESTAMP: " . implode(', ', $timestampCols) . "\n";
    }
    echo "├─────────────────────────────────────────────────────────────\n";
    
    foreach ($columns as $col) {
        $colName = trim($col['COLUMN_NAME']);
        $type = $col['TYPE_NAME'];
        $len = $col['FIELD_LENGTH'];
        $prec = $col['FIELD_PRECISION'];
        $scale = $col['FIELD_SCALE'];
        $notNull = $col['NOT_NULL'] ? 'NOT NULL' : '';
        $def = $col['DEFAULT_VALUE'] ? ' DEFAULT ' . $col['DEFAULT_VALUE'] : '';
        
        $typeStr = $type;
        if (in_array($type, ['CHAR', 'VARCHAR'])) {
            $typeStr .= "($len)";
        } elseif (in_array($type, ['NUMERIC', 'DECIMAL'])) {
            $typeStr .= "($prec,$scale)";
        }
        
        $isPk = false;
        foreach ($pk as $p) { if (trim($p['COLUMN_NAME']) === $colName) { $isPk = true; break; } }
        $pkMark = $isPk ? ' 🔑' : '';
        
        echo "│   $colName $typeStr $notNull$def$pkMark\n";
    }
    
    if (!empty($pk)) {
        echo "│   PRIMARY KEY: " . implode(', ', array_column($pk, 'COLUMN_NAME')) . "\n";
    }
    
    if (!empty($fks)) {
        echo "│   FOREIGN KEYS:\n";
        $currentFk = '';
        foreach ($fks as $fk) {
            if ($fk['FK_NAME'] !== $currentFk) {
                $currentFk = trim($fk['FK_NAME']);
                echo "│     $currentFk: " . trim($fk['COLUMN_NAME']) . " → " . trim($fk['REF_TABLE']) . "." . trim($fk['REF_COLUMN']) . "\n";
            }
        }
    }
    
    if (!empty($indexes)) {
        echo "│   INDEXES:\n";
        foreach ($indexes as $idx) {
            $unique = $idx['IS_UNIQUE'] ? ' UNIQUE' : '';
            echo "│     " . trim($idx['INDEX_NAME']) . "$unique: " . $idx['COLUMNS'] . "\n";
        }
    }
    
    echo "└─────────────────────────────────────────────────────────────\n\n";
}

// ============================================================
// 3. RESUMEN DE TABLAS CLAVE DEL PROYECTO
// ============================================================
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

echo "=== 3. RESUMEN TABLAS DEL PROYECTO ===\n\n";

foreach ($keyTables as $t) {
    $t = trim($t);
    if (!in_array($t, $tableNames)) {
        echo "❌ $t — NO EXISTE EN FIREBIRD\n";
        continue;
    }
    $count = q($pdo, "SELECT COUNT(*) as CNT FROM $t");
    $rowCount = $count[0]['CNT'] ?? 0;
    
    // Verificar columnas timestamp
    $cols = q($pdo, "
        SELECT RF.RDB\$FIELD_NAME as COLUMN_NAME
        FROM RDB\$RELATION_FIELDS RF
        WHERE RF.RDB\$RELATION_NAME = ?
    ", [$t]);
    
    $colNames = array_map('trim', array_column($cols, 'COLUMN_NAME'));
    $hasTimestamp = false;
    $tsCols = [];
    foreach ($colNames as $cn) {
        if (stripos($cn, 'FECHA') !== false 
            || stripos($cn, 'MODIFIC') !== false 
            || stripos($cn, 'ACTUALIZ') !== false
            || stripos($cn, 'TIMESTAMP') !== false
            || stripos($cn, 'VERSION') !== false
            || stripos($cn, 'CONSEC') !== false
            || stripos($cn, 'ULT') !== false) {
            $hasTimestamp = true;
            $tsCols[] = $cn;
        }
    }
    
    $tsStr = $hasTimestamp ? " ⏰ [" . implode(', ', $tsCols) . "]" : " ❌ SIN TIMESTAMP";
    echo "$t: $rowCount registros$tsStr\n";
}

echo "\n=== FIN ===\n";