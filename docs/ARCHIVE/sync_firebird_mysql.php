<?php
declare(strict_types=1);

// ============================================================
// CONFIGURACIÓN CENTRALIZADA DE BASE DE DATOS
// ============================================================
require_once __DIR__ . '/core/Database.php';

// Conexiones usando singleton centralizado
$fb = Database::firebird();
$mysql = Database::mysql();

// Tablas con ciclo DIRECTO (tienen INICIAL+FINAL+PERIODO, se filtran por ciclo)
$TABLAS_CICLO_DIRECTO = ['GRUPOS','HORARIOS_DET','CURSOS','CURSOS_DET','CICLOS'];

// Tablas de alumnos: se obtienen via ALUMNOS_NIVELES (ciclo → IDs → fetch completo)
$TABLAS_ALUMNOS_CICLO = ['ALUMNOS_NIVELES','ALUMNOS','ALUMNOS_GRUPOS','ALUMNOS_KARDEX'];

// Catálogos / personas (sin ciclo)
$TABLAS_SIN_CICLO = [
    'CFGSEDES','CFGPLANES_DET','PROFESORES','CFGSESIONES',
    'CFGTURNOS','CFGNIVELES','EMPLEADOS',
    'EMPLEADOS_ASISTENCIA','EMPLEADOS_HORARIOS','EMPLEADOS_CFGHORARIOS',
    'EMPLEADOS_CFGHORARIOS_DET','EMPLEADOS_CONTRATOS_CAT',
    'CFGPLANES_MST','CFGPLANES_EVAL','CFGPLANES_ETAPAS',
    'CFGSTATUS','CFGAULAS'
];

$ciclos_fb = $fb->query("
    SELECT INICIAL, FINAL, PERIODO, DESCRIPCION
    FROM CICLOS ORDER BY INICIAL DESC, FINAL DESC, PERIODO DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// HELPERS
// ============================================================

function get_fb_columns(PDO $fb, string $tabla): array {
    $stmt = $fb->prepare("
        SELECT TRIM(rf.RDB\$FIELD_NAME) AS COL
        FROM RDB\$RELATION_FIELDS rf
        WHERE rf.RDB\$RELATION_NAME = ?
        ORDER BY rf.RDB\$FIELD_POSITION
    ");
    $stmt->execute([$tabla]);
    return array_map(fn($r) => strtoupper(trim($r['COL'])), $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function get_mysql_columns(PDO $mysql, string $tabla): array {
    $stmt = $mysql->prepare("SHOW COLUMNS FROM `{$tabla}`");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_pk_columns(PDO $mysql, string $tabla): array {
    $cols = get_mysql_columns($mysql, $tabla);
    return array_map(fn($r) => strtolower($r['Field']),
        array_filter($cols, fn($r) => $r['Key'] === 'PRI'));
}

function count_rows(PDO $pdo, string $tabla, ?string $where = null, array $params = []): int {
    $sql = "SELECT COUNT(*) AS N FROM {$tabla}";
    if ($where) $sql .= " WHERE {$where}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['N'];
}

function count_rows_in(PDO $pdo, string $tabla, string $col, array $ids): int {
    $total = 0;
    foreach (array_chunk($ids, 1400) as $chunk) {
        $ph = implode(',', array_fill(0, count($chunk), '?'));
        $total += count_rows($pdo, $tabla, "{$col} IN ({$ph})", $chunk);
    }
    return $total;
}

function clean_rows(array &$datos): void {
    foreach ($datos as &$row) {
        foreach ($row as $k => &$v) {
            if (is_string($v)) $v = trim($v);
            if ($v === '' || $v === null) $v = null;
        }
        unset($v);
    }
    unset($row);
}

function fetch_fb(PDO $fb, string $tabla, array $fb_cols, ?string $where = null, array $params = []): array {
    $cols_select = implode(', ', array_map(fn($c) => "\"{$c}\"", $fb_cols));
    $sql = "SELECT {$cols_select} FROM {$tabla}";
    if ($where) $sql .= " WHERE {$where}";
    $stmt = $fb->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    clean_rows($datos);
    return $datos;
}

function fetch_fb_in(PDO $fb, string $tabla, array $fb_cols, string $col, array $ids): array {
    $all = [];
    foreach (array_chunk($ids, 1400) as $chunk) {
        $ph = implode(',', array_fill(0, count($chunk), '?'));
        $rows = fetch_fb($fb, $tabla, $fb_cols, "{$col} IN ({$ph})", $chunk);
        $all = array_merge($all, $rows);
    }
    return $all;
}

function safe_val($v): ?string {
    if ($v === null) return null;
    if (is_string($v) && $v === '') return null;
    return (string)$v;
}

/**
 * Smart sync comparando por PK.
 * - Nuevo → INSERT
 * - Existente con cambios → UPDATE
 * - Sin cambio → se omite
 * - Huérfanos → DELETE (opcional)
 * - Dedup en FB por PK (Firebird a veces tiene duplicados)
 */
function smart_sync(
    PDO $mysql,
    string $tabla,
    array $fb_cols,
    array $my_cols,
    array $datos_fb,
    array &$log,
    bool $delete_orphans = false
): void {
    if (empty($datos_fb)) {
        $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros en Firebird"];
        return;
    }

    // Mapear Firebird → MySQL
    $fb_map = [];
    foreach ($fb_cols as $fc) {
        $lc = strtolower($fc);
        if (in_array($lc, $my_cols)) {
            $fb_map[$lc] = $fc;
        }
    }
    if (empty($fb_map)) {
        $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: sin columnas comunes"];
        return;
    }

    $mapped_fb = [];
    foreach ($datos_fb as $row) {
        $m = [];
        foreach ($fb_map as $mysql_key => $fb_key) {
            $m[$mysql_key] = $row[$fb_key] ?? null;
        }
        $mapped_fb[] = $m;
    }

    $pk_cols = get_pk_columns($mysql, $tabla);
    if (empty($pk_cols)) {
        $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: sin PK definida"];
        return;
    }

    // Deduplicar por PK (Firebird puede tener duplicados)
    $indexed = [];
    foreach ($mapped_fb as $row) {
        $pk_key = '';
        foreach ($pk_cols as $pk) $pk_key .= '|' . ($row[$pk] ?? '');
        $indexed[$pk_key] = $row;
    }
    if (count($indexed) < count($mapped_fb)) {
        $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: " . (count($mapped_fb) - count($indexed)) . " duplicados FB eliminados"];
    }
    $mapped_fb = array_values($indexed);

    $col_names = array_keys($mapped_fb[0]);
    $non_pk = array_values(array_diff($col_names, $pk_cols));

    // Leer existentes de MySQL
    $all_cols_select = implode(', ', array_map(fn($c) => "`{$c}`", $col_names));
    $existing = [];
    $stmt = $mysql->query("SELECT {$all_cols_select} FROM `{$tabla}`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pk_key = '';
        foreach ($pk_cols as $pk) $pk_key .= '|' . ($row[$pk] ?? '');
        $existing[$pk_key] = $row;
    }

    // Clasificar
    $to_insert = [];
    $to_update = [];

    foreach ($mapped_fb as $row) {
        $pk_key = '';
        foreach ($pk_cols as $pk) $pk_key .= '|' . ($row[$pk] ?? '');

        if (!isset($existing[$pk_key])) {
            $to_insert[] = $row;
        } else {
            $changed = false;
            foreach ($non_pk as $col) {
                if (safe_val($row[$col]) !== safe_val($existing[$pk_key][$col])) {
                    $changed = true;
                    break;
                }
            }
            if ($changed) {
                $to_update[] = $row;
            }
            unset($existing[$pk_key]);
        }
    }

    $orphan_count = $delete_orphans ? count($existing) : 0;

    // Ejecutar
    $mysql->beginTransaction();
    try {
        // DELETE huérfanos
        $deleted = 0;
        if ($delete_orphans && $orphan_count > 0) {
            foreach ($existing as $old_row) {
                $wp = [];
                $vp = [];
                foreach ($pk_cols as $pk) {
                    $wp[] = "`{$pk}` = ?";
                    $vp[] = $old_row[$pk];
                }
                $mysql->prepare("DELETE FROM `{$tabla}` WHERE " . implode(' AND ', $wp))->execute($vp);
                $deleted++;
            }
        }

        // INSERT nuevos (en lotes de 500)
        $inserted = 0;
        if (!empty($to_insert)) {
            $ph = implode(',', array_fill(0, count($col_names), '?'));
            $cs = implode(',', array_map(fn($c) => "`{$c}`", $col_names));
            foreach (array_chunk($to_insert, 500) as $batch) {
                $values = []; $params = [];
                foreach ($batch as $row) {
                    $values[] = "({$ph})";
                    foreach ($col_names as $cn) $params[] = $row[$cn] ?? null;
                }
                $mysql->prepare("INSERT INTO `{$tabla}` ({$cs}) VALUES " . implode(',', $values))->execute($params);
                $inserted += count($batch);
            }
        }

        // UPDATE existentes (en lotes de 200)
        $updated = 0;
        if (!empty($to_update) && !empty($non_pk)) {
            $set_parts = implode(', ', array_map(fn($c) => "`{$c}` = ?", $non_pk));
            $where_parts = implode(' AND ', array_map(fn($c) => "`{$c}` = ?", $pk_cols));
            foreach (array_chunk($to_update, 200) as $batch) {
                foreach ($batch as $row) {
                    $params = [];
                    foreach ($non_pk as $cn) $params[] = $row[$cn] ?? null;
                    foreach ($pk_cols as $pk) $params[] = $row[$pk] ?? null;
                    $mysql->prepare("UPDATE `{$tabla}` SET {$set_parts} WHERE {$where_parts}")->execute($params);
                    $updated++;
                }
            }
        }

        $mysql->commit();

        $msg = "{$tabla}: INSERT {$inserted}";
        if ($updated > 0) $msg .= ", UPDATE {$updated}";
        if ($deleted > 0) $msg .= ", DELETE {$deleted}";
        $msg .= " (FB: " . count($mapped_fb) . ", MySQL: " . ($orphan_count > 0 ? ($deleted . " huérf") : count($mapped_fb) - $inserted - $updated . " intactos") . ")";
        $log[] = ['tipo' => 'ok', 'msg' => $msg];

    } catch (Throwable $e) {
        if ($mysql->inTransaction()) $mysql->rollBack();
        $log[] = ['tipo' => 'error', 'msg' => "{$tabla}: ERROR - " . $e->getMessage()];
    }
}

/**
 * Sync con DELETE por condición (para tablas de ciclo: DELETE old cycle + INSERT fresh)
 */
function sync_cycle_table(
    PDO $fb,
    PDO $mysql,
    string $tabla,
    array $fb_cols,
    array $my_cols,
    array $datos_fb,
    array &$log,
    ?string $where_delete = null,
    array $params_delete = []
): void {
    if (empty($datos_fb)) {
        // Solo limpiar MySQL de ese ciclo
        if ($where_delete) {
            $mysql->beginTransaction();
            try {
                $del = $mysql->prepare("DELETE FROM `{$tabla}` WHERE {$where_delete}");
                $del->execute($params_delete);
                $mysql->commit();
                $log[] = ['tipo' => 'ok', 'msg' => "{$tabla}: limpiados {$del->rowCount()} (sin datos FB)"];
            } catch (Throwable $e) {
                if ($mysql->inTransaction()) $mysql->rollBack();
                $log[] = ['tipo' => 'error', 'msg' => "{$tabla}: ERROR limpiando - " . $e->getMessage()];
            }
        } else {
            $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros"];
        }
        return;
    }

    // Mapear
    $fb_map = [];
    foreach ($fb_cols as $fc) {
        $lc = strtolower($fc);
        if (in_array($lc, $my_cols)) $fb_map[$lc] = $fc;
    }
    if (empty($fb_map)) { $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: sin columnas comunes"]; return; }

    $mapped_fb = [];
    foreach ($datos_fb as $row) {
        $m = [];
        foreach ($fb_map as $mk => $fk) $m[$mk] = $row[$fk] ?? null;
        $mapped_fb[] = $m;
    }

    // Deduplicar
    $pk_cols = get_pk_columns($mysql, $tabla);
    $indexed = [];
    foreach ($mapped_fb as $row) {
        $pk_key = '';
        foreach ($pk_cols as $pk) $pk_key .= '|' . ($row[$pk] ?? '');
        $indexed[$pk_key] = $row;
    }
    $mapped_fb = array_values($indexed);

    $col_names = array_keys($mapped_fb[0]);
    $ph = implode(',', array_fill(0, count($col_names), '?'));
    $cs = implode(',', array_map(fn($c) => "`{$c}`", $col_names));

    $mysql->beginTransaction();
    try {
        $deleted = 0;
        if ($where_delete) {
            $del = $mysql->prepare("DELETE FROM `{$tabla}` WHERE {$where_delete}");
            $del->execute($params_delete);
            $deleted = $del->rowCount();
        }

        $inserted = 0;
        foreach (array_chunk($mapped_fb, 500) as $batch) {
            $values = []; $params = [];
            foreach ($batch as $row) {
                $values[] = "({$ph})";
                foreach ($col_names as $cn) $params[] = $row[$cn] ?? null;
            }
            $mysql->prepare("INSERT INTO `{$tabla}` ({$cs}) VALUES " . implode(',', $values))->execute($params);
            $inserted += count($batch);
        }

        $mysql->commit();
        $log[] = ['tipo' => 'ok', 'msg' => "{$tabla}: DELETE {$deleted}, INSERT {$inserted} (FB: " . count($mapped_fb) . ")"];
    } catch (Throwable $e) {
        if ($mysql->inTransaction()) $mysql->rollBack();
        $log[] = ['tipo' => 'error', 'msg' => "{$tabla}: ERROR - " . $e->getMessage()];
    }
}

// ============================================================
// SINCRONIZACION
// ============================================================
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$ciclo_sel = $_POST['ciclo'] ?? $_GET['ciclo'] ?? '';
$delete_orphans = isset($_POST['delete_orphans']);
$log = [];

if ($accion === 'sync_ciclo') {
    $parts = explode('-', $ciclo_sel);
    if (count($parts) !== 3) {
        $log[] = ['tipo' => 'error', 'msg' => "Ciclo invalido: {$ciclo_sel}"];
    } else {
        $I = (int)$parts[0]; $F = (int)$parts[1]; $P = (int)$parts[2];
        $log[] = ['tipo' => 'info', 'msg' => "=== CICLO {$I}-{$F}-{$P} ==="];

        // ── FASE 1: Tablas directas por ciclo (DELETE + INSERT) ──
        $log[] = ['tipo' => 'info', 'msg' => "--- FASE 1: Tablas directas ---"];
        foreach ($TABLAS_CICLO_DIRECTO as $tabla) {
            $fb_cols = get_fb_columns($fb, $tabla);
            $my_cols = array_map(fn($r) => strtolower($r['Field']), get_mysql_columns($mysql, $tabla));
            $total = count_rows($fb, $tabla, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P]);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total} en FB"];
            $datos = ($total > 0)
                ? fetch_fb($fb, $tabla, $fb_cols, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P])
                : [];
            sync_cycle_table($fb, $mysql, $tabla, $fb_cols, $my_cols, $datos, $log,
                "inicial = ? AND final = ? AND periodo = ?", [$I, $F, $P]);
        }

        // ── FASE 2: Alumnos (via ALUMNOS_NIVELES → IDs → fetch completo) ──
        $log[] = ['tipo' => 'info', 'msg' => "--- FASE 2: Alumnos por ciclo ---"];

        // 2a. ALUMNOS_NIVELES por ciclo → obtener NUMEROALUMNO
        $tabla = 'ALUMNOS_NIVELES';
        $fb_cols = get_fb_columns($fb, $tabla);
        $my_cols = array_map(fn($r) => strtolower($r['Field']), get_mysql_columns($mysql, $tabla));
        $total_fn = count_rows($fb, $tabla, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P]);
        $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total_fn} en FB (filtro ciclo)"];

        $alumno_ids = [];
        if ($total_fn > 0) {
            $datos_niveles = fetch_fb($fb, $tabla, $fb_cols, "INICIAL = ? AND FINAL = ? AND PERIODO = ?", [$I, $F, $P]);
            // Extraer NUMEROALUMNO
            foreach ($datos_niveles as $row) {
                $val = $row['NUMEROALUMNO'] ?? null;
                if ($val !== null && $val !== '') $alumno_ids[] = (int)$val;
            }
            $alumno_ids = array_unique($alumno_ids);
        }
        $log[] = ['tipo' => 'info', 'msg' => "Alumnos encontrados: " . count($alumno_ids)];

        if (empty($alumno_ids)) {
            $log[] = ['tipo' => 'skip', 'msg' => "No hay alumnos, saltando ALUMNOS/GRUPOS/KARDEX"];
        } else {
            // 2b. ALUMNOS (SIN filtro ciclo → datos completos del alumno)
            $tabla = 'ALUMNOS';
            $fb_cols = get_fb_columns($fb, $tabla);
            $my_cols = array_map(fn($r) => strtolower($r['Field']), get_mysql_columns($mysql, $tabla));
            $total_a = count_rows_in($fb, $tabla, 'NUMEROALUMNO', $alumno_ids);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total_a} en FB (SIN filtro ciclo)"];
            if ($total_a > 0) {
                $datos_a = fetch_fb_in($fb, $tabla, $fb_cols, 'NUMEROALUMNO', $alumno_ids);
                smart_sync($mysql, $tabla, $fb_cols, $my_cols, $datos_a, $log, $delete_orphans);
            } else {
                $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros"];
            }

            // 2c. ALUMNOS_GRUPOS (SIN filtro ciclo → todos los grupos de esos alumnos)
            $tabla = 'ALUMNOS_GRUPOS';
            $fb_cols = get_fb_columns($fb, $tabla);
            $my_cols = array_map(fn($r) => strtolower($r['Field']), get_mysql_columns($mysql, $tabla));
            $total_ag = count_rows_in($fb, $tabla, 'NUMEROALUMNO', $alumno_ids);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total_ag} en FB (SIN filtro ciclo)"];
            if ($total_ag > 0) {
                $datos_ag = fetch_fb_in($fb, $tabla, $fb_cols, 'NUMEROALUMNO', $alumno_ids);
                smart_sync($mysql, $tabla, $fb_cols, $my_cols, $datos_ag, $log, $delete_orphans);
            } else {
                $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros"];
            }

            // 2d. ALUMNOS_KARDEX (SIN filtro ciclo → todo el historial de calificaciones)
            $tabla = 'ALUMNOS_KARDEX';
            $fb_cols = get_fb_columns($fb, $tabla);
            $my_cols = array_map(fn($r) => strtolower($r['Field']), get_mysql_columns($mysql, $tabla));
            $total_ak = count_rows_in($fb, $tabla, 'NUMEROALUMNO', $alumno_ids);
            $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total_ak} en FB (SIN filtro ciclo)"];
            if ($total_ak > 0) {
                $datos_ak = fetch_fb_in($fb, $tabla, $fb_cols, 'NUMEROALUMNO', $alumno_ids);
                smart_sync($mysql, $tabla, $fb_cols, $my_cols, $datos_ak, $log, $delete_orphans);
            } else {
                $log[] = ['tipo' => 'skip', 'msg' => "{$tabla}: 0 registros"];
            }
        }

        $log[] = ['tipo' => 'info', 'msg' => "=== FIN CICLO {$I}-{$F}-{$P} ==="];
    }

} elseif ($accion === 'sync_catalogos') {
    $log[] = ['tipo' => 'info', 'msg' => "=== CATALOGOS (smart sync) ==="];
    foreach ($TABLAS_SIN_CICLO as $tabla) {
        $fb_cols = get_fb_columns($fb, $tabla);
        $my_cols = array_map(fn($r) => strtolower($r['Field']), get_mysql_columns($mysql, $tabla));
        $total_fb = count_rows($fb, $tabla);
        $log[] = ['tipo' => 'info', 'msg' => "{$tabla}: {$total_fb} en FB"];
        $datos = ($total_fb > 0) ? fetch_fb($fb, $tabla, $fb_cols) : [];
        smart_sync($mysql, $tabla, $fb_cols, $my_cols, $datos, $log, $delete_orphans);
    }
}

// ============================================================
// ESTADO
// ============================================================
$all_tables = array_merge($TABLAS_CICLO_DIRECTO, $TABLAS_ALUMNOS_CICLO, $TABLAS_SIN_CICLO);
$estado_mysql = [];
foreach ($all_tables as $t) {
    try { $estado_mysql[$t] = count_rows($mysql, $t); }
    catch (Throwable $e) { $estado_mysql[$t] = -1; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GES - Smart Sync Firebird &rarr; MySQL</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }
        h1 { color: #60a5fa; margin-bottom: 4px; font-size: 1.4rem; }
        .subtitle { color: #64748b; font-size: 0.82rem; margin-bottom: 20px; }
        h2 { color: #94a3b8; font-size: 1rem; margin: 20px 0 10px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .panel { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 800px) { .grid2 { grid-template-columns: 1fr; } }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { background: #334155; padding: 8px 12px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; }
        td { padding: 8px 12px; border-bottom: 1px solid #1e293b; }
        tr:hover td { background: rgba(96,165,250,0.05); }
        select, button { padding: 10px 16px; border-radius: 6px; font-size: 0.9rem; }
        select { background: #0f172a; border: 1px solid #475569; color: #e2e8f0; }
        button { border: none; cursor: pointer; font-weight: 600; transition: all 0.15s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #60a5fa; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #4ade80; }
        .form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; }
        .log-entry { padding: 8px 12px; font-size: 0.82rem; border-left: 4px solid; margin-bottom: 3px; border-radius: 2px; }
        .log-ok { border-color: #22c55e; background: rgba(34,197,94,0.08); color: #a7f3d0; }
        .log-err { border-color: #ef4444; background: rgba(239,68,68,0.08); color: #fca5a5; }
        .log-info { border-color: #3b82f6; background: rgba(59,130,246,0.08); color: #bfdbfe; }
        .log-skip { border-color: #475569; color: #94a3b8; }
        .tag-ciclo { background: rgba(168,85,247,0.15); color: #a855f7; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
        .tag-alumno { background: rgba(234,179,8,0.15); color: #eab308; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
        .tag-normal { background: rgba(59,130,246,0.15); color: #60a5fa; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
        .check { display: flex; align-items: center; gap: 6px; margin-top: 12px; }
        .check input { width: 16px; height: 16px; }
        .check label { font-size: 0.82rem; color: #94a3b8; cursor: pointer; }
        .how { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 0.82rem; line-height: 1.7; }
        .how strong { color: #4ade80; }
        .badge { display: inline-block; background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem; margin-left: 8px; vertical-align: middle; }
    </style>
</head>
<body>
<div class="container">
    <h1>GES - Smart Sync <span class="badge">v2</span></h1>
    <p class="subtitle">Firebird &rarr; MySQL | Compara, inserta, actualiza</p>

    <div class="how">
        <strong>Como funciona:</strong><br>
        &bull; <strong>FASE 1</strong> — Tablas de ciclo directo (GRUPOS, CURSOS, HORARIOS, CICLOS): DELETE + INSERT por ciclo<br>
        &bull; <strong>FASE 2</strong> — Alumnos: busca en ALUMNOS_NIVELES por ciclo &rarr; obtiene IDs &rarr; trae ALUMNOS, GRUPOS y KARDEX <strong>SIN filtro de ciclo</strong> (datos completos)<br>
        &bull; <strong>Catálogos</strong> — Smart sync: INSERT nuevos, UPDATE cambios, DELETE huérfanos (opcional)<br>
        &bull; Compara por PK: si no hay cambio, no toca nada
    </div>

    <div class="panel">
        <h2>Estado en MySQL (GES)</h2>
        <table>
            <thead><tr><th>Tabla</th><th>Tipo</th><th>Registros</th></tr></thead>
            <tbody>
            <?php foreach ($all_tables as $t): ?>
                <tr>
                    <td><strong><?= strtolower($t) ?></strong></td>
                    <td>
                        <?php if (in_array($t, $TABLAS_CICLO_DIRECTO)): ?>
                            <span class="tag-ciclo">CICLO</span>
                        <?php elseif (in_array($t, $TABLAS_ALUMNOS_CICLO)): ?>
                            <span class="tag-alumno">ALUMNO</span>
                        <?php else: ?>
                            <span class="tag-normal">CATALOGO</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($estado_mysql[$t] >= 0): ?>
                            <strong><?= number_format($estado_mysql[$t]) ?></strong>
                        <?php else: ?>
                            <span style="color:#ef4444">ERROR</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="grid2">
        <div class="panel">
            <h2>Sincronizar por Ciclo</h2>
            <p style="font-size:0.82rem;color:#94a3b8;margin-bottom:12px;">
                <span class="tag-ciclo">CICLO</span> <?= implode(', ', $TABLAS_CICLO_DIRECTO) ?><br>
                <span class="tag-alumno">ALUMNO</span> <?= implode(', ', $TABLAS_ALUMNOS_CICLO) ?> <em>(sin filtro ciclo)</em>
            </p>
            <form method="post" onsubmit="return confirm('¿Sincronizar este ciclo?')">
                <input type="hidden" name="accion" value="sync_ciclo">
                <div class="form-row">
                    <div class="form-group">
                        <label>Ciclo</label>
                        <select name="ciclo" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($ciclos_fb as $c): ?>
                                <option value="<?= "{$c['INICIAL']}-{$c['FINAL']}-{$c['PERIODO']}" ?>">
                                    <?= "{$c['INICIAL']}-{$c['FINAL']}-{$c['PERIODO']}" ?> — <?= $c['DESCRIPCION'] ?? '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Sincronizar Ciclo</button>
                </div>
                <div class="check">
                    <input type="checkbox" name="delete_orphans" id="del1" value="1">
                    <label for="del1">Eliminar huérfanos en tablas de alumnos</label>
                </div>
            </form>
        </div>

        <div class="panel">
            <h2>Sincronizar Cat&aacute;logos</h2>
            <p style="font-size:0.82rem;color:#94a3b8;margin-bottom:12px;">
                <?= count($TABLAS_SIN_CICLO) ?> tablas (config, personas, etc)
            </p>
            <form method="post" onsubmit="return confirm('¿Sincronizar catálogos?')">
                <input type="hidden" name="accion" value="sync_catalogos">
                <button type="submit" class="btn-success" style="margin-top:20px;">Smart Sync Cat&aacute;logos</button>
                <div class="check">
                    <input type="checkbox" name="delete_orphans" id="del2" value="1">
                    <label for="del2">Eliminar huérfanos</label>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($log)): ?>
    <div class="panel">
        <h2>Log de Sincronizaci&oacute;n</h2>
        <div id="log" style="max-height:500px; overflow-y:auto;">
        <?php foreach ($log as $entry): ?>
            <div class="log-entry log-<?= $entry['tipo'] ?>">
                <?= htmlspecialchars($entry['msg']) ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Ciclos Disponibles (<?= count($ciclos_fb) ?>)</h2>
        <table>
            <thead><tr><th>Ciclo</th><th>Descripcion</th></tr></thead>
            <tbody>
            <?php foreach ($ciclos_fb as $c): ?>
                <tr>
                    <td><strong><?= "{$c['INICIAL']}-{$c['FINAL']}-{$c['PERIODO']}" ?></strong></td>
                    <td><?= $c['DESCRIPCION'] ?? '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
