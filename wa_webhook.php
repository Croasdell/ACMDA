<?php
// WhatsApp webhook endpoint for inbound messages
// Place in project root and configure WA_VERIFY_TOKEN environment variable.

require __DIR__ . '/acmda.php';

$verifyToken = getenv('WA_VERIFY_TOKEN') ?: 'changeme';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    if ($mode === 'subscribe' && $token === $verifyToken) {
        echo $challenge;
    } else {
        http_response_code(403);
    }
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!empty($data['entry'][0]['changes'][0]['value']['messages'][0])) {
    $msg = $data['entry'][0]['changes'][0]['value']['messages'][0];
    $from = $msg['from'] ?? 'unknown';
    $text = $msg['text']['body'] ?? '';
    $db = initDb($dbFile);
    $id = receiveMessage($db, $from, $text, $services);
    echo json_encode(['status' => 'stored', 'id' => $id]);
} else {
    echo json_encode(['status' => 'ignored']);
}
