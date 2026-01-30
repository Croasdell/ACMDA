<?php
require_once __DIR__ . '/acmda.php';

function waHandleWebhook(PDO $db, string $method, array $query, string $payload, string $verifyToken): array {
    if ($method === 'GET') {
        $token = $query['hub_verify_token'] ?? '';
        $challenge = $query['hub_challenge'] ?? '';
        if ($token === $verifyToken) {
            return [200, $challenge];
        }
        return [403, 'Invalid verify token'];
    }

    if ($method === 'POST') {
        $data = json_decode($payload, true);
        $msg = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        if ($msg) {
            $from = $msg['from'] ?? '';
            $text = $msg['text']['body'] ?? '';
            receiveMessage($db, $from, $text);
        }
        return [200, 'OK'];
    }

    return [405, 'Method not allowed'];
}

if (php_sapi_name() !== 'cli') {
    $db = initDb(__DIR__ . '/acmda.sqlite');
    $verify = getenv('WA_VERIFY_TOKEN') ?: 'test-token';
    [$code, $body] = waHandleWebhook($db, $_SERVER['REQUEST_METHOD'], $_GET, file_get_contents('php://input'), $verify);
    http_response_code($code);
    echo $body;
}
