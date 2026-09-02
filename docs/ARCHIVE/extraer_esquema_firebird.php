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

// 1. Mapa de tipos Firebird → MySQL
$tipo_map = [];
$types = $pdo->query("SELECT RDB\$TYPE AS TID, TRIM(RDB\$TYPE_NAME) AS TNAME FROM RDB\$TYPES WHERE RDB\$FIELD_NAME = 'RDB\$FIELD_TYPE'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($types as $tp) {
    $tipo_map[(int)$tp['TID']] = trim($tp['TNAME']);
}

function fb_type_to_mysql(array $row, array $tipo_map): string {
    $type_id   = (int)$row['FIELD_TYPE'];
    $sub_type  = (int)$row['FIELD_SUB_TYPE'];
    $fLen      = (int)$row['FIELD_LENGTH'];
    $fPrec     = (int)$row['FIELD_PRECISION'];
    $fScale    = abs((int)$row['FIELD_SCALE']);
    $charLen   = (int)$row['CHAR_LEN'];
    $size      = $charLen > 0 ? $charLen : $fLen;
    $fb_name   = $tipo_map[$type_id] ?? "TYPE_{$type_id}";
    $safe_size = max($size, 255);

    return match(true) {
        $fb_name === 'VARCHAR' || $fb_name === 'VARYING' => "VARCHAR({$size})",
        $fb_name === 'CHAR' => "CHAR({$size})",
        $fb_name === 'SMALLINT' => "SMALLINT",
        $fb_name === 'INTEGER' => "INT",
        $fb_name === 'LONG' => "INT",
        $fb_name === 'SHORT' => "SMALLINT",
        $fb_name === 'BIGINT' => "BIGINT",
        $fb_name === 'FLOAT' && $fPrec > 0 => "DOUBLE",
        $fb_name === 'FLOAT' => "FLOAT",
        $fb_name === 'DOUBLE PRECISION' => "DOUBLE",
        $fb_name === 'DATE' => "DATE",
        $fb_name === 'TIME' => "TIME",
        $fb_name === 'TIMESTAMP' => "DATETIME",
        $fb_name === 'BOOLEAN' || $fb_name === 'BOOLEAN' => "TINYINT(1)",
        $fb_name === 'NUMERIC' && $fPrec > 0 => "NUMERIC({$fPrec},{$fScale})",
        $fb_name === 'DECIMAL' && $fPrec > 0 => "DECIMAL({$fPrec},{$fScale})",
        $fb_name === 'NUMERIC' => "NUMERIC(18,{$fScale})",
        $fb_name === 'DECIMAL' => "DECIMAL(18,{$fScale})",
        $fb_name === 'BLOB' && $sub_type == 1 => "TEXT",
        $fb_name === 'BLOB' => "LONGBLOB",
        $fb_name === 'TEXT' => "TEXT",
        str_contains($fb_name, 'BLOB') && $sub_type == 1 => "TEXT",
        default => "VARCHAR({$safe_size}) /* {$fb_name} */",
    };
}

// 2. Obtener todas las tablas de usuario
$tablas = $pdo->query("SELECT TRIM(RDB\$RELATION_NAME) AS TABLA FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL ORDER BY RDB\$RELATION_NAME")->fetchAll(PDO::FETCH_ASSOC);

$out = fopen('php://output', 'w');

fwrite($out, "-- ============================================================\n");
fwrite($out, "-- ESQUEMA COMPLETO EXTRAIDO DE FIREBIRD\n");
fwrite($out, "-- Base de datos: DATOS (1).FDB — " . count($tablas) . " tablas de usuario\n");
fwrite($out, "-- Generado: " . date('Y-m-d H:i:s') . "\n");
fwrite($out, "-- ============================================================\n\n");
fwrite($out, "CREATE DATABASE IF NOT EXISTS rh_dashboard_completo\n");
fwrite($out, "  CHARACTER SET utf8mb4\n");
fwrite($out, "  COLLATE utf8mb4_unicode_ci;\n\n");
fwrite($out, "USE rh_dashboard_completo;\n\n");

foreach ($tablas as $t) {
    $tabla = trim($t['TABLA']);

    // Obtener columnas con tipos desde RDB$FIELDS
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

    if (empty($columnas)) continue;

    // PKs
    $pk_stmt = $pdo->prepare("
        SELECT TRIM(s.RDB\$FIELD_NAME) AS COL
        FROM RDB\$INDEX_SEGMENTS s
        JOIN RDB\$RELATION_CONSTRAINTS rc ON s.RDB\$INDEX_NAME = rc.RDB\$INDEX_NAME
        WHERE rc.RDB\$RELATION_NAME = ? AND rc.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY s.RDB\$FIELD_POSITION
    ");
    $pk_stmt->execute([$tabla]);
    $pks = $pk_stmt->fetchAll(PDO::FETCH_COLUMN);

    // FKs
    $fk_stmt = $pdo->prepare("
        SELECT
            TRIM(s.RDB\$FIELD_NAME) AS COL,
            TRIM(ref.RDB\$RELATION_NAME) AS REF_TABLE,
            TRIM(ref_s.RDB\$FIELD_NAME) AS REF_COL
        FROM RDB\$RELATION_CONSTRAINTS rc
        JOIN RDB\$INDEX_SEGMENTS s ON rc.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
        JOIN RDB\$REF_CONSTRAINTS refc ON rc.RDB\$CONSTRAINT_NAME = refc.RDB\$CONSTRAINT_NAME
        JOIN RDB\$RELATION_CONSTRAINTS ref ON refc.RDB\$CONST_NAME_UQ = ref.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS ref_s ON ref.RDB\$INDEX_NAME = ref_s.RDB\$INDEX_NAME
        WHERE rc.RDB\$RELATION_NAME = ? AND rc.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
        ORDER BY s.RDB\$FIELD_POSITION
    ");
    $fk_stmt->execute([$tabla]);
    $fks = $fk_stmt->fetchAll(PDO::FETCH_ASSOC);

    // FKs agrupadas por constraint
    $fk_groups = [];
    foreach ($fks as $fk) {
        $fk_groups[$fk['COL']] = $fk;
    }

    // Indices no-PK, no-FK
    $idx_stmt = $pdo->prepare("
        SELECT TRIM(i.RDB\$INDEX_NAME) AS NOMBRE, TRIM(s.RDB\$FIELD_NAME) AS COLUMNA, i.RDB\$UNIQUE_FLAG AS ES_UNICO
        FROM RDB\$INDICES i
        JOIN RDB\$INDEX_SEGMENTS s ON i.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
        WHERE i.RDB\$RELATION_NAME = ?
          AND i.RDB\$SYSTEM_FLAG = 0
          AND i.RDB\$INDEX_NAME NOT IN (
              SELECT rc.RDB\$INDEX_NAME FROM RDB\$RELATION_CONSTRAINTS rc
              WHERE rc.RDB\$RELATION_NAME = ? AND rc.RDB\$CONSTRAINT_TYPE IN ('PRIMARY KEY','FOREIGN KEY')
          )
        ORDER BY i.RDB\$INDEX_NAME, s.RDB\$FIELD_POSITION
    ");
    $idx_stmt->execute([$tabla, $tabla]);
    $indices = $idx_stmt->fetchAll(PDO::FETCH_ASSOC);
    $idx_groups = [];
    foreach ($indices as $idx) {
        $idx_groups[$idx['NOMBRE']][] = ['col' => $idx['COLUMNA'], 'unico' => $idx['ES_UNICO']];
    }

    // ============================================================
    // Generar SQL
    // ============================================================
    $tname = strtolower($tabla);
    fwrite($out, "-- ============================================================\n");
    fwrite($out, "-- TABLA: {$tabla}\n");
    fwrite($out, "-- ============================================================\n");
    fwrite($out, "CREATE TABLE {$tname} (\n");

    $defs = [];
    foreach ($columnas as $col) {
        $cname = strtolower(trim($col['FIELD_NAME']));
        $mysql = fb_type_to_mysql($col, $tipo_map);
        $nn    = ((int)$col['NULL_FLAG'] === 1) ? 'NOT NULL' : 'DEFAULT NULL';

        // Detectar autoincrement (campo INTEGER/SMALLINT con PK)
        $is_auto = (in_array(strtoupper(trim($col['FIELD_NAME'])), $pks)
                    && preg_match('/^(INT|SMALLINT|BIGINT|INTEGER)$/i', $mysql)
                    && $col['FIELD_TYPE'] == 5);

        $auto_str = $is_auto ? ' AUTO_INCREMENT' : '';
        $defs[]   = "  {$cname} {$mysql} {$nn}{$auto_str}";
    }

    // PK
    if (!empty($pks)) {
        $pk_cols = implode(', ', array_map(fn($c) => strtolower($c), $pks));
        $defs[]  = "  PRIMARY KEY ({$pk_cols})";
    }

    // FK inline
    foreach ($fk_groups as $col => $fk) {
        $defs[] = "  CONSTRAINT fk_{$tname}_" . strtolower($col)
                . " FOREIGN KEY (" . strtolower($col) . ")"
                . " REFERENCES " . strtolower($fk['REF_TABLE']) . "(" . strtolower($fk['REF_COL']) . ")";
    }

    fwrite($out, implode(",\n", $defs));
    fwrite($out, "\n) ENGINE=InnoDB;\n\n");

    // Indices
    foreach ($idx_groups as $nombre => $cols_idx) {
        $uniq   = !empty($cols_idx[0]['unico']) ? 'UNIQUE ' : '';
        $colstr = implode(', ', array_map(fn($c) => strtolower($c['col']), $cols_idx));
        $iname  = "idx_{$tname}_" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre));
        fwrite($out, "CREATE {$uniq}INDEX {$iname} ON {$tname} ({$colstr});\n");
    }
    if (!empty($idx_groups)) fwrite($out, "\n");
}

fwrite($out, "-- ============================================================\n");
fwrite($out, "-- FIN DEL ESQUEMA (" . count($tablas) . " tablas)\n");
fwrite($out, "-- ============================================================\n");

fclose($out);
