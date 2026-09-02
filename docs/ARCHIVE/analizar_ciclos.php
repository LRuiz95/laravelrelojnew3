<?php
declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/core/Database.php';

// Get Firebird connection using Database singleton
$pdo = Database::firebird();

$TABLAS_RH = [
    'CFGSEDES','CFGPLANES_DET','PROFESORES','CFGSESIONES','GRUPOS',
    'CFGTURNOS','CFGNIVELES','CICLOS','ALUMNOS_GRUPOS','ALUMNOS',
    'ALUMNOS_KARDEX','CURSOS','CURSOS_DET','HORARIOS_DET','EMPLEADOS',
    'EMPLEADOS_ASISTENCIA','EMPLEADOS_HORARIOS','EMPLEADOS_CFGHORARIOS',
    'EMPLEADOS_CFGHORARIOS_DET','EMPLEADOS_CONTRATOS_CAT',
    'CFGPLANES_MST','CFGPLANES_EVAL','CFGPLANES_ETAPAS',
    'CFGSTATUS','CFGAULAS','ALUMNOS_NIVELES'
];

// Buscar columnas que contengan inicial, final, periodo
$target_cols = ['INICIAL', 'FINAL', 'PERIODO'];

echo "=== ANALISIS: COLUMNAS DE CICLO (INICIAL/FINAL/PERIODO) ===\n\n";

$con_ciclo = [];
$sin_ciclo = [];

foreach ($TABLAS_RH as $tabla) {
    $stmt = $pdo->prepare("
        SELECT TRIM(rf.RDB\$FIELD_NAME) AS COL
        FROM RDB\$RELATION_FIELDS rf
        WHERE rf.RDB\$RELATION_NAME = ?
        ORDER BY rf.RDB\$FIELD_POSITION
    ");
    $stmt->execute([$tabla]);
    $cols = array_map(fn($r) => strtoupper(trim($r['COL'])), $stmt->fetchAll(PDO::FETCH_ASSOC));

    $tiene = array_intersect($target_cols, $cols);
    $faltan = array_diff($target_cols, $tiene);

    // Contar registros
    try {
        $cnt = $pdo->query("SELECT COUNT(*) AS N FROM " . $tabla)->fetch(PDO::FETCH_ASSOC);
        $registros = (int)($cnt['N'] ?? 0);
    } catch (Throwable $e) {
        $registros = -1;
    }

    if (count($tiene) === 3) {
        // Tiene las 3 columnas — verificar si el PK las incluye
        $pk_stmt = $pdo->prepare("
            SELECT TRIM(s.RDB\$FIELD_NAME) AS COL
            FROM RDB\$INDEX_SEGMENTS s
            JOIN RDB\$RELATION_CONSTRAINTS rc ON s.RDB\$INDEX_NAME = rc.RDB\$INDEX_NAME
            WHERE rc.RDB\$RELATION_NAME = ? AND rc.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
            ORDER BY s.RDB\$FIELD_POSITION
        ");
        $pk_stmt->execute([$tabla]);
        $pks = array_map(fn($r) => strtoupper(trim($r['COL'])), $pk_stmt->fetchAll(PDO::FETCH_ASSOC));
        $pk_tiene_ciclo = count(array_intersect(['INICIAL','FINAL','PERIODO'], $pks));

        $con_ciclo[$tabla] = [
            'registros' => $registros,
            'pk_ciclo' => $pk_tiene_ciclo === 3,
        ];
    } else {
        $sin_ciclo[$tabla] = [
            'registros' => $registros,
            'tiene' => $tiene,
            'faltan' => $faltan,
        ];
    }
}

// ============================================================
// RESUMEN
// ============================================================
echo "--------------------------------------------------------------\n";
echo " TABLAS RIGIDAS POR CICLO (tienen INICIAL + FINAL + PERIODO)\n";
echo "--------------------------------------------------------------\n";
printf("  %-30s %10s  %s\n", "TABLA", "REGISTROS", "EN PK DEL CICLO");
echo "  " . str_repeat("-", 65) . "\n";

foreach ($con_ciclo as $t => $info) {
    $pk = $info['pk_ciclo'] ? 'SI (parte de PK)' : 'NO (solo columna)';
    printf("  %-30s %10d  %s\n", $t, $info['registros'], $pk);
}
echo "\n  Total: " . count($con_ciclo) . " tablas atadas a ciclo\n\n";

echo "--------------------------------------------------------------\n";
echo " TABLAS SIN CICLO (catálogos / configuración)\n";
echo "--------------------------------------------------------------\n";
printf("  %-30s %10s\n", "TABLA", "REGISTROS");
echo "  " . str_repeat("-", 42) . "\n";

foreach ($sin_ciclo as $t => $info) {
    printf("  %-30s %10d\n", $t, $info['registros']);
}
echo "\n  Total: " . count($sin_ciclo) . " tablas independientes de ciclo\n";
