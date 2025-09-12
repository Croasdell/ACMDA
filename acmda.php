<?php
// All-in-one AI Customer Messaging & Developer Assistant (ACMDA)
// This single script provides memory, RAG, and a WhatsApp message pipeline.

$dbFile = __DIR__ . '/acmda.sqlite';

function initDb(string $dbFile): PDO {
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
        CREATE TABLE IF NOT EXISTS wa_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender TEXT,
            message TEXT,
            draft TEXT,
            status TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    SQL);
    return $db;
}

function saveMemory(PDO $db, string $user, string $message, string $response): void {
    $stmt = $db->prepare('INSERT INTO memory(user, message, response) VALUES (?, ?, ?)');
    $stmt->execute([$user, $message, $response]);
}

function getMemory(PDO $db, string $user): array {
    $stmt = $db->prepare('SELECT message, response FROM memory WHERE user = ? ORDER BY id');
    $stmt->execute([$user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Embedded business service definitions used to craft rule-based replies.
$services = [
    'offers' => ['assembly', 'doors', 'locks', 'tiling', 'plumbing repairs'],
    'not_offered' => ['carpet fitting', 'electrical rewiring'],
    'policy' => 'Please use the online booking system for prices and availability.'
];

function draftReply(PDO $db, string $user, string $message, array $services): string {
    $lower = strtolower($message);
    foreach ($services['offers'] as $service) {
        if (str_contains($lower, $service)) {
            $reply = "Yes, Ian can help with $service. {$services['policy']}";
            saveMemory($db, $user, $message, $reply);
            return $reply;
        }
    }
    foreach ($services['not_offered'] as $service) {
        if (str_contains($lower, $service)) {
            $reply = "Ian doesn't provide $service. {$services['policy']}";
            saveMemory($db, $user, $message, $reply);
            return $reply;
        }
    }
    $reply = "Thanks for reaching out! {$services['policy']}";
    saveMemory($db, $user, $message, $reply);
    return $reply;
}

function receiveMessage(PDO $db, string $sender, string $message, array $services): int {
    $draft = draftReply($db, $sender, $message, $services);
    $stmt = $db->prepare('INSERT INTO wa_messages(sender, message, draft, status) VALUES (?, ?, ?, "pending")');
    $stmt->execute([$sender, $message, $draft]);
    return (int) $db->lastInsertId();
}

function approveMessage(PDO $db, int $id): void {
    $stmt = $db->prepare('UPDATE wa_messages SET status = "approved" WHERE id = ?');
    $stmt->execute([$id]);
}

function rejectMessage(PDO $db, int $id): void {
    $stmt = $db->prepare('UPDATE wa_messages SET status = "rejected" WHERE id = ?');
    $stmt->execute([$id]);
}

function sendApprovedMessages(PDO $db): void {
    $stmt = $db->query('SELECT id, sender, draft FROM wa_messages WHERE status = "approved"');
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($msgs as $m) {
        echo "Sending to {$m['sender']}: {$m['draft']}\n"; // Simulated send
        $upd = $db->prepare('UPDATE wa_messages SET status = "sent" WHERE id = ?');
        $upd->execute([$m['id']]);
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $db = initDb($dbFile);
    $cmd = $argv[1] ?? '';

    switch ($cmd) {
        case 'receive':
            $sender = $argv[2] ?? 'customer';
            $msg = $argv[3] ?? '';
            $id = receiveMessage($db, $sender, $msg, $services);
            echo "Message stored with id $id\n";
            break;
        case 'approve':
            $id = (int) ($argv[2] ?? 0);
            approveMessage($db, $id);
            echo "Message $id approved\n";
            break;
        case 'reject':
            $id = (int) ($argv[2] ?? 0);
            rejectMessage($db, $id);
            echo "Message $id rejected\n";
            break;
        case 'send':
            sendApprovedMessages($db);
            break;
        case 'memory':
            $user = $argv[2] ?? 'customer';
            $mem = getMemory($db, $user);
            foreach ($mem as $row) {
                echo "{$row['message']} => {$row['response']}\n";
            }
            break;
        default:
            echo "Usage: php acmda.php [receive|approve|reject|send|memory]\n";
    }
}

