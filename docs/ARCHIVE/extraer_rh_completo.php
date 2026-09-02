<?php
declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/core/Database.php';

// Get Firebird connection using Database singleton
$pdo = Database::firebird();

// Tablas usadas en rh-dashboard2.php
$TABLAS_RH = [
    'CFGSEDES','CFGPLANES_DET','PROFESORES','CFGSESIONES','GRUPOS',
    'CFGTURNOS','CFGNIVELES','CICLOS','ALUMNOS_GRUPOS','ALUMNOS',
    'ALUMNOS_KARDEX','CURSOS','CURSOS_DET','HORARIOS_DET','EMPLEADOS',
    'EMPLEADOS_ASISTENCIA','EMPLEADOS_HORARIOS','EMPLEADOS_CFGHORARIOS',
    'EMPLEADOS_CFGHORARIOS_DET','EMPLEADOS_CONTRATOS_CAT',
    'CFGPLANES_MST','CFGPLANES_EVAL','CFGPLANES_ETAPAS',
    'CFGSTATUS','CFGAULAS','ALUMNOS_NIVELES'
];

// Mapa de tipos Firebird
$tipo_map = [];
$rows = $pdo->query("SELECT RDB\$TYPE AS TID, TRIM(RDB\$TYPE_NAME) AS TNAME FROM RDB\$TYPES WHERE RDB\$FIELD_NAME = 'RDB\$FIELD_TYPE'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $tp) $tipo_map[(int)$tp['TID']] = trim($tp['TNAME']);

function fb_type_to_mysql(array $row, array $tipo_map): string {
    $type_id  = (int)$row['FIELD_TYPE'];
    $sub_type = (int)$row['FIELD_SUB_TYPE'];
    $fLen     = (int)$row['FIELD_LENGTH'];
    $fPrec    = (int)$row['FIELD_PRECISION'];
    $fScale   = abs((int)$row['FIELD_SCALE']);
    $charLen  = (int)$row['CHAR_LEN'];
    $size     = $charLen > 0 ? $charLen : $fLen;
    $fb       = $tipo_map[$type_id] ?? "TYPE_{$type_id}";
    $safe     = max($size, 255);

    return match(true) {
        $fb === 'VARCHAR' || $fb === 'VARYING' => "VARCHAR({$size})",
        $fb === 'CHAR' && $sub_type == 0 => "CHAR({$size})",
        $fb === 'CHAR' => "VARCHAR({$size})",
        $fb === 'SMALLINT' || $fb === 'SHORT' => "SMALLINT",
        $fb === 'INTEGER' || $fb === 'LONG' => "INT",
        $fb === 'BIGINT' => "BIGINT",
        $fb === 'FLOAT' && $fPrec > 0 => "DOUBLE",
        $fb === 'FLOAT' => "FLOAT",
        $fb === 'DOUBLE PRECISION' || $fb === 'DOUBLE' => "DOUBLE",
        $fb === 'DATE' => "DATE",
        $fb === 'TIME' => "TIME",
        $fb === 'TIMESTAMP' => "DATETIME",
        $fb === 'BOOLEAN' || $fb === 'BOOLEAN' => "TINYINT(1)",
        $fb === 'NUMERIC' && $fPrec > 0 => "NUMERIC({$fPrec},{$fScale})",
        $fb === 'DECIMAL' && $fPrec > 0 => "DECIMAL({$fPrec},{$fScale})",
        $fb === 'NUMERIC' => "NUMERIC(18,{$fScale})",
        $fb === 'DECIMAL' => "DECIMAL(18,{$fScale})",
        $fb === 'BLOB' && $sub_type == 1 => "TEXT",
        $fb === 'BLOB' => "LONGBLOB",
        $fb === 'TEXT' => "TEXT",
        str_contains($fb, 'BLOB') && $sub_type == 1 => "TEXT",
        default => "VARCHAR({$safe}) /* {$fb} */",
    };
}

// Funcion para obtener PKs
function get_pks(PDO $pdo, string $tabla): array {
    $stmt = $pdo->prepare("
        SELECT TRIM(s.RDB\$FIELD_NAME) AS COL
        FROM RDB\$INDEX_SEGMENTS s
        JOIN RDB\$RELATION_CONSTRAINTS rc ON s.RDB\$INDEX_NAME = rc.RDB\$INDEX_NAME
        WHERE rc.RDB\$RELATION_NAME = ? AND rc.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY s.RDB\$FIELD_POSITION
    ");
    $stmt->execute([$tabla]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Funcion para obtener indices
function get_indices(PDO $pdo, string $tabla): array {
    $stmt = $pdo->prepare("
        SELECT TRIM(i.RDB\$INDEX_NAME) AS NOMBRE, TRIM(s.RDB\$FIELD_NAME) AS COLUMNA, i.RDB\$UNIQUE_FLAG AS ES_UNICO
        FROM RDB\$INDICES i
        JOIN RDB\$INDEX_SEGMENTS s ON i.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
        WHERE i.RDB\$RELATION_NAME = ? AND i.RDB\$SYSTEM_FLAG = 0
          AND i.RDB\$INDEX_NAME NOT IN (
              SELECT rc.RDB\$INDEX_NAME FROM RDB\$RELATION_CONSTRAINTS rc
              WHERE rc.RDB\$RELATION_NAME = ? AND rc.RDB\$CONSTRAINT_TYPE IN ('PRIMARY KEY','FOREIGN KEY')
          )
        ORDER BY i.RDB\$INDEX_NAME, s.RDB\$FIELD_POSITION
    ");
    $stmt->execute([$tabla, $tabla]);
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $groups = [];
    foreach ($indices as $idx) $groups[trim($idx['NOMBRE'])][] = ['col' => trim($idx['COLUMNA']), 'uniq' => $idx['ES_UNICO']];
    return $groups;
}

// Funcion para contar registros
function count_rows(PDO $pdo, string $tabla): int {
    try {
        $r = $pdo->query("SELECT COUNT(*) AS N FROM " . $tabla)->fetch(PDO::FETCH_ASSOC);
        return (int)($r['N'] ?? 0);
    } catch (Throwable $e) {
        return -1;
    }
}

$sep = str_repeat('=', 70);

echo "-- {$sep}\n";
echo "-- ESQUEMA MYSQL COMPLETO — SOLO TABLAS USADAS EN rh-dashboard2.php\n";
echo "-- Extraido de Firebird: DATOS (1).FDB\n";
echo "-- Tablas: " . count($TABLAS_RH) . "\n";
echo "-- {$sep}\n\n";

echo "DROP DATABASE IF EXISTS GES;\n";
echo "CREATE DATABASE GES CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "USE GES;\n\n";

foreach ($TABLAS_RH as $tabla) {
    $tname = strtolower($tabla);
    $num_cols = 0;

    // Columnas
    $cols_stmt = $pdo->prepare("
        SELECT
            rf.RDB\$FIELD_NAME AS FIELD_NAME,
            rf.RDB\$FIELD_POSITION AS FIELD_POSITION,
            rf.RDB\$NULL_FLAG AS NULL_FLAG,
            f.RDB\$FIELD_TYPE AS FIELD_TYPE,
            f.RDB\$FIELD_SUB_TYPE AS FIELD_SUB_TYPE,
            f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
            f.RDB\$FIELD_PRECISION AS FIELD_PRECISION,
            f.RDB\$FIELD_SCALE AS FIELD_SCALE,
            f.RDB\$CHARACTER_LENGTH AS CHAR_LEN
        FROM RDB\$RELATION_FIELDS rf
        JOIN RDB\$FIELDS f ON rf.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
        WHERE rf.RDB\$RELATION_NAME = ?
        ORDER BY rf.RDB\$FIELD_POSITION
    ");
    $cols_stmt->execute([$tabla]);
    $columnas = $cols_stmt->fetchAll(PDO::FETCH_ASSOC);
    $num_cols = count($columnas);

    if (empty($columnas)) {
        echo "-- ============================================================\n";
        echo "-- TABLA: {$tabla} — NO ENCONTRADA EN FIREBIRD\n";
        echo "-- ============================================================\n\n";
        continue;
    }

    $pks     = get_pks($pdo, $tabla);
    $indices = get_indices($pdo, $tabla);
    $registros = count_rows($pdo, $tabla);

    echo "-- {$sep}\n";
    echo "-- TABLA: {$tabla}  |  {$num_cols} columnas  |  ~{$registros} registros\n";
    echo "-- {$sep}\n";
    echo "CREATE TABLE {$tname} (\n";

    $defs = [];
    $first_pk = !empty($pks) ? strtolower($pks[0]) : '';
    foreach ($columnas as $col) {
        $cname = strtolower(trim($col['FIELD_NAME']));
        $mysql = fb_type_to_mysql($col, $tipo_map);
        $nn    = ((int)$col['NULL_FLAG'] === 1) ? 'NOT NULL' : 'DEFAULT NULL';

        // Si es TEXT/BLOB y esta en PK, cambiar a VARCHAR(255)
        $in_pk = in_array(strtoupper(trim($col['FIELD_NAME'])), $pks);
        if ($in_pk && preg_match('/^(TEXT|LONGBLOB)$/i', $mysql)) {
            $mysql = 'VARCHAR(255)';
        }

        // Auto-detect: columnas como id_tipoeval, tipo, etc. son codigos cortos
        if (preg_match('/^(id_tipoeval|tipo)$/i', $cname) && $mysql === 'TEXT') {
            $mysql = 'VARCHAR(5)';
        }

        // Si es DOUBLE y esta en PK, usar INT
        if ($in_pk && $mysql === 'DOUBLE') {
            $mysql = 'INT';
        }

        // AUTO_INCREMENT solo en id_escuela
        $auto = ($cname === $first_pk && $cname === 'id_escuela' && preg_match('/^(INT|SMALLINT|BIGINT)$/i', $mysql)) ? ' AUTO_INCREMENT' : '';
        $defs[] = "  {$cname} {$mysql} {$nn}{$auto}";
    }

    if (!empty($pks)) {
        $pk_cols = implode(', ', array_map(fn($c) => strtolower($c), $pks));
        $defs[]  = "  PRIMARY KEY ({$pk_cols})";
    }

    echo implode(",\n", $defs);
    echo "\n) ENGINE=InnoDB;\n\n";

    // Indices
    foreach ($indices as $nombre => $cols_idx) {
        $uniq   = !empty($cols_idx[0]['uniq']) ? 'UNIQUE ' : '';
        $colstr = implode(', ', array_map(fn($c) => strtolower($c['col']), $cols_idx));
        $iname  = "idx_{$tname}_" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre));
        echo "CREATE {$uniq}INDEX {$iname} ON {$tname} ({$colstr});\n";
    }
    if (!empty($indices)) echo "\n";
}

echo "-- {$sep}\n";
echo "-- RESUMEN\n";
echo "-- {$sep}\n";

// Verificar integridad
echo "-- Verificacion de tablas:\n";
foreach ($TABLAS_RH as $tabla) {
    $tname = strtolower($tabla);
    $stmt = $pdo->prepare("SELECT COUNT(*) AS N FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ?");
    $stmt->execute([$tabla]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    $status = $exists ? $exists['N'] . ' cols' : 'NO EXISTE';
    echo "--   {$tname}: {$status}\n";
}

echo "-- {$sep}\n";
echo "-- FIN\n";
echo "-- {$sep}\n";
