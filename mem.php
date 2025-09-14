<?php
// Memory layer for business context and conversation history.

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
