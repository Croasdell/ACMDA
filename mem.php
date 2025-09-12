<?php
// Memory management for Dolphin AI system
// Initializes memory SQLite DB and seeds business context facts

$dbFile = __DIR__ . '/memory.sqlite';
$services = require __DIR__ . '/services.php';

function initDb(string $dbFile, array $services): PDO {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS memory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user TEXT,
            message TEXT,
            response TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    SQL);
    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS facts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT,
            detail TEXT
        );
    SQL);
    // seed facts if empty
    $count = (int) $db->query('SELECT COUNT(*) FROM facts')->fetchColumn();
    if ($count === 0) {
        $ins = $db->prepare('INSERT INTO facts(category, detail) VALUES (?, ?)');
        foreach ($services['offers'] as $svc) {
            $ins->execute(['offers', $svc]);
        }
        foreach ($services['not_offered'] as $svc) {
            $ins->execute(['not_offered', $svc]);
        }
        $ins->execute(['policy', $services['policy']]);
    }
    return $db;
}

function remember(PDO $db, string $user, string $message, string $response): void {
    $stmt = $db->prepare('INSERT INTO memory(user, message, response) VALUES (?, ?, ?)');
    $stmt->execute([$user, $message, $response]);
}

function recall(PDO $db, string $user): array {
    $stmt = $db->prepare('SELECT message, response FROM memory WHERE user = ? ORDER BY id');
    $stmt->execute([$user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listFacts(PDO $db): array {
    $stmt = $db->query('SELECT category, detail FROM facts ORDER BY id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$db = initDb($dbFile, $services);
$cmd = $argv[1] ?? '';

switch ($cmd) {
    case 'remember':
        $user = $argv[2] ?? 'customer';
        $msg = $argv[3] ?? '';
        $resp = $argv[4] ?? '';
        remember($db, $user, $msg, $resp);
        echo "Memory stored\n";
        break;
    case 'recall':
        $user = $argv[2] ?? 'customer';
        $mem = recall($db, $user);
        foreach ($mem as $row) {
            echo "{$row['message']} => {$row['response']}\n";
        }
        break;
    case 'facts':
        $facts = listFacts($db);
        foreach ($facts as $f) {
            echo "{$f['category']}: {$f['detail']}\n";
        }
        break;
    default:
        echo "Usage: php mem.php [remember|recall|facts]\n";
}

?>
