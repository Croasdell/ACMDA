<?php
// Cron-friendly sender for approved WhatsApp messages.
require __DIR__ . '/acmda.php';

$db = initDb($dbFile);
sendApprovedMessages($db);
