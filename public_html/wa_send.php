<?php
require_once __DIR__ . '/acmda.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(400);
    echo "CLI only";
    exit;
}

$db = initDb(__DIR__ . '/acmda.sqlite');
sendApprovedMessages($db);
