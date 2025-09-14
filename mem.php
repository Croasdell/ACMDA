<?php
// Memory layer for business context and conversation history.

// -- Business context -----------------------------------------------------
function initBusiness(PDO $db): void {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS business (key TEXT PRIMARY KEY, value TEXT)'
    );
}

function saveBusinessData(PDO $db, array $data): void {
    $stmt = $db->prepare('REPLACE INTO business(key, value) VALUES(:key, :value)');
    foreach ($data as $key => $value) {
        $stmt->execute([
            ':key' => $key,
            ':value' => json_encode($value)
        ]);
    }
}

function loadBusinessData(PDO $db): array {
    $stmt = $db->query('SELECT key, value FROM business');
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[$row['key']] = json_decode($row['value'], true);
    }
    return $out;
}

// -- Conversation memory --------------------------------------------------

function initMemory(PDO $db): void {
    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS chat_memory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            who TEXT,
            role TEXT,
            content TEXT,
            created_at INTEGER DEFAULT (strftime('%s','now'))
        );
    SQL);
    $db->exec('CREATE INDEX IF NOT EXISTS idx_chat_memory_who ON chat_memory(who)');
}

function mem_save(PDO $db, string $who, string $role, string $content): void {
    $stmt = $db->prepare('INSERT INTO chat_memory(who, role, content) VALUES(?,?,?)');
    $stmt->execute([$who, $role, $content]);
}

function mem_history(PDO $db, string $who, int $limit = 50): array {
    $stmt = $db->prepare('SELECT role, content FROM chat_memory WHERE who = ? ORDER BY id DESC LIMIT ?');
    $stmt->bindValue(1, $who, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function mem_clear(PDO $db, string $who): void {
    $stmt = $db->prepare('DELETE FROM chat_memory WHERE who = ?');
    $stmt->execute([$who]);
}
