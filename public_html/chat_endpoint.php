<?php
require_once __DIR__ . '/chat_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Chat not configured']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$message = trim($payload['message'] ?? '');

if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Message required']);
    exit;
}

$links = [
    'booking' => 'https://calendly.com/iancroasdell/handyman-plus-van',
    'booking_full' => 'https://calendly.com/iancroasdell/handyman-plus-van-6hrs',
    'zoom' => 'https://calendly.com/iancroasdell/20-minute-video-call-service-19',
    'whatsapp' => 'https://wa.me/447729689420'
];

$system = <<<SYS
You are the Handyman Plus Van booking assistant for Ian in Swindon.
Goal: help people book Ian's time. Do not provide quotes for big jobs.
If asked for a quote or large job, say you do not quote for bigger jobs and suggest booking time or a 20-minute Zoom call.
Always be friendly, short, and easy to read for older people. Use simple sentences.
Share these links when helpful:
- Book a handyman visit: {$links['booking']}
- Book a full-day visit: {$links['booking_full']}
- Book a 20-minute Zoom call (£19): {$links['zoom']}
- WhatsApp for questions: {$links['whatsapp']}
Never ask for email. Encourage WhatsApp or booking links.
SYS;

$request = [
    'model' => OPENAI_MODEL,
    'input' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message]
    ],
    'max_output_tokens' => 220,
    'temperature' => 0.3
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($request)
]);

$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false || $status < 200 || $status >= 300) {
    http_response_code(500);
    echo json_encode(['error' => 'Chat service error']);
    exit;
}

$data = json_decode($result, true);
$reply = $data['output'][0]['content'][0]['text'] ?? '';

if ($reply === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Empty response']);
    exit;
}

echo json_encode(['reply' => $reply]);
