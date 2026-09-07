<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class FirebirdReader
{
    protected PDO $connection;

    public function __construct()
    {
        $this->connect();
    }

    protected function connect(): void
    {
        $dsn = config('database.connections.firebird.dsn');
        $username = config('database.connections.firebird.username');
        $password = config('database.connections.firebird.password');
        $options = config('database.connections.firebird.options', []);

        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (Throwable $e) {
            Log::error('Firebird connection failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function getColumns(string $table): array
    {
        $stmt = $this->connection->prepare(
            'SELECT TRIM(rf.RDB$FIELD_NAME) AS COL FROM RDB$RELATION_FIELDS rf WHERE rf.RDB$RELATION_NAME = ? ORDER BY rf.RDB$FIELD_POSITION'
        );
        $stmt->execute([strtoupper($table)]);
        return array_map(fn($r) => strtoupper(trim($r['COL'])), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getPrimaryKey(string $table, PDO $mysql): array
    {
        $stmt = $mysql->prepare("SHOW COLUMNS FROM `{$table}`");
        $stmt->execute();
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => strtolower($r['Field']), array_filter($cols, fn($r) => $r['Key'] === 'PRI'));
    }

    public function countRows(string $table, ?string $where = null, array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS N FROM {$table}";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['N'] ?? 0);
    }

    public function countRowsIn(string $table, string $col, array $ids): int
    {
        $total = 0;
        foreach (array_chunk($ids, 1400) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $total += $this->countRows($table, "{$col} IN ({$ph})", $chunk);
        }
        return $total;
    }

    public function fetchRows(string $table, array $fbCols, ?string $where = null, array $params = []): array
    {
        $colsSelect = implode(', ', array_map(fn($c) => "\"{$c}\"", $fbCols));
        $sql = "SELECT {$colsSelect} FROM {$table}";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->cleanRows($datos);
    }

    public function fetchRowsIn(string $table, array $fbCols, string $col, array $ids): array
    {
        $all = [];
        foreach (array_chunk($ids, 1400) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $rows = $this->fetchRows($table, $fbCols, "{$col} IN ({$ph})", $chunk);
            $all = array_merge($all, $rows);
        }
        return $all;
    }

    /**
     * Fetch rows in chunks using Firebird FIRST/SKIP syntax.
     * Yields batches to keep memory bounded.
     *
     * @return \Generator<int, array>
     */
    public function fetchRowsChunked(string $table, array $fbCols, int $chunkSize = 5000, ?string $where = null, array $params = []): \Generator
    {
        $colsSelect = implode(', ', array_map(fn($c) => "\"{$c}\"", $fbCols));
        $offset = 0;

        while (true) {
            $sql = "SELECT FIRST {$chunkSize} SKIP {$offset} {$colsSelect} FROM {$table}";
            if ($where) $sql .= " WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            $this->cleanRows($rows);
            yield $rows;

            if (count($rows) < $chunkSize) {
                break;
            }

            $offset += $chunkSize;
        }
    }

    public function cleanRows(array &$datos): array
    {
        foreach ($datos as &$row) {
            foreach ($row as $k => &$v) {
                if (is_string($v)) $v = trim($v);
                if ($v === '' || $v === null) $v = null;
            }
            unset($v);
        }
        unset($row);
        return $datos;
    }

    public function safeVal($v): ?string
    {
        if ($v === null) return null;
        if (is_string($v) && $v === '') return null;
        return (string)$v;
    }

    public function ping(): bool
    {
        try {
            $this->connection->query('SELECT 1 FROM RDB$DATABASE');
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}