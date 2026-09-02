<?php
/**
 * Obtener esquema completo con todas las columnas para diseño MySQL
 */

// Load environment variables
require_once __DIR__ . '/core/Database.php';

// Get Firebird connection using Database singleton
$pdo = Database::firebird();

function q(PDO $pdo, string $sql, array $params = []): array {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

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

$allSchemas = [];

foreach ($keyTables as $targetTable) {
    $targetTable = trim($targetTable);
    
    $tables = q($pdo, "SELECT TRIM(RDB\$RELATION_NAME) as TABLE_NAME FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL");
    $realNames = array_map('trim', array_column($tables, 'TABLE_NAME'));
    
    $realTable = null;
    foreach ($realNames as $rn) {
        if (strcasecmp($rn, $targetTable) === 0) { $realTable = $rn; break; }
    }
    
    if (!$realTable) continue;
    
    $columns = q($pdo, "
        SELECT 
            TRIM(RF.RDB\$FIELD_NAME) as COLUMN_NAME,
            F.RDB\$FIELD_TYPE as FIELD_TYPE,
            F.RDB\$FIELD_LENGTH as FIELD_LENGTH,
            F.RDB\$FIELD_PRECISION as FIELD_PRECISION,
            F.RDB\$FIELD_SCALE as FIELD_SCALE,
            RF.RDB\$NULL_FLAG as NOT_NULL,
            RF.RDB\$DEFAULT_SOURCE as DEFAULT_VALUE,
            RF.RDB\$FIELD_POSITION as FIELD_POSITION,
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
    
    $pk = q($pdo, "
        SELECT TRIM(SEG.RDB\$FIELD_NAME) as COLUMN_NAME
        FROM RDB\$INDEX_SEGMENTS SEG
        JOIN RDB\$INDICES IDX ON SEG.RDB\$INDEX_NAME = IDX.RDB\$INDEX_NAME
        JOIN RDB\$RELATION_CONSTRAINTS RC ON IDX.RDB\$INDEX_NAME = RC.RDB\$INDEX_NAME
        WHERE TRIM(RC.RDB\$RELATION_NAME) = ? AND RC.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY SEG.RDB\$FIELD_POSITION
    ", [$realTable]);
    
    $fks = q($pdo, "
        SELECT 
            TRIM(SEG.RDB\$FIELD_NAME) as COLUMN_NAME,
            TRIM(RC2.RDB\$RELATION_NAME) as REF_TABLE,
            TRIM(SEG2.RDB\$FIELD_NAME) as REF_COLUMN
        FROM RDB\$RELATION_CONSTRAINTS RC
        JOIN RDB\$INDEX_SEGMENTS SEG ON RC.RDB\$INDEX_NAME = SEG.RDB\$INDEX_NAME
        JOIN RDB\$REF_CONSTRAINTS REFC ON RC.RDB\$CONSTRAINT_NAME = REFC.RDB\$CONSTRAINT_NAME
        JOIN RDB\$RELATION_CONSTRAINTS RC2 ON REFC.RDB\$CONST_NAME_UQ = RC2.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS SEG2 ON RC2.RDB\$INDEX_NAME = SEG2.RDB\$INDEX_NAME
        WHERE TRIM(RC.RDB\$RELATION_NAME) = ? AND RC.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
        ORDER BY RC.RDB\$CONSTRAINT_NAME, SEG.RDB\$FIELD_POSITION
    ", [$realTable]);
    
    $indexes = q($pdo, "
        SELECT 
            TRIM(IDX.RDB\$INDEX_NAME) as INDEX_NAME,
            IDX.RDB\$UNIQUE_FLAG as IS_UNIQUE,
            LIST(TRIM(SEG.RDB\$FIELD_NAME), ', ') as COLUMNS
        FROM RDB\$INDICES IDX
        JOIN RDB\$INDEX_SEGMENTS SEG ON IDX.RDB\$INDEX_NAME = SEG.RDB\$INDEX_NAME
        WHERE TRIM(IDX.RDB\$RELATION_NAME) = ? AND IDX.RDB\$SYSTEM_FLAG = 0
        GROUP BY IDX.RDB\$INDEX_NAME, IDX.RDB\$UNIQUE_FLAG
        ORDER BY IDX.RDB\$INDEX_NAME
    ", [$realTable]);
    
    $allSchemas[$realTable] = [
        'columns' => $columns,
        'pk' => array_map('trim', array_column($pk, 'COLUMN_NAME')),
        'fks' => $fks,
        'indexes' => $indexes
    ];
}

// Guardar JSON para análisis
file_put_contents('firebird_full_schema.json', json_encode($allSchemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Esquema completo guardado en firebird_full_schema.json\n";

// Mostrar resumen
foreach ($allSchemas as $table => $data) {
    echo "\n=== $table ===\n";
    echo "PK: " . implode(', ', $data['pk']) . "\n";
    echo "Columnas (" . count($data['columns']) . "):\n";
    foreach ($data['columns'] as $c) {
        $cn = $c['COLUMN_NAME'];
        $type = $c['TYPE_NAME'];
        $len = $c['FIELD_LENGTH'];
        $nn = $c['NOT_NULL'] ? ' NOT NULL' : '';
        $isPk = in_array($cn, $data['pk']) ? ' 🔑' : '';
        $typeStr = $type;
        if (in_array($type, ['CHAR', 'VARCHAR'])) $typeStr .= "($len)";
        echo "  $cn $typeStr$nn$isPk\n";
    }
    if (!empty($data['fks'])) {
        echo "FKs:\n";
        foreach ($data['fks'] as $fk) {
            echo "  {$fk['COLUMN_NAME']} -> {$fk['REF_TABLE']}.{$fk['REF_COLUMN']}\n";
        }
    }
}