<?php
declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/core/Database.php';

// Get Firebird connection using Database singleton
$pdo = Database::firebird();

echo "=== RDB\$REF_CONSTRAINTS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$REF_CONSTRAINTS")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";

echo "\n=== RDB\$RELATION_CONSTRAINTS ===\n";
$rows = $pdo->query("SELECT FIRST 1 * FROM RDB\$RELATION_CONSTRAINTS")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) echo "  " . implode(', ', array_keys($rows[0])) . "\n";
