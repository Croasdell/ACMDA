<?php
require_once __DIR__ . '/acmda.php';

$db = initDb(__DIR__ . '/acmda.sqlite');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (isset($_POST['approve'])) {
        approveMessage($db, $id);
    } elseif (isset($_POST['reject'])) {
        rejectMessage($db, $id);
    }
    header('Location: wa_approve.php');
    exit;
}

$msgs = $db->query('SELECT id, sender, message, draft FROM wa_messages WHERE status = "pending"')
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pending WhatsApp Messages</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .msg { border: 1px solid #ccc; padding: 1em; margin-bottom: 1em; }
        .msg p { margin: .3em 0; }
    </style>
</head>
<body>
<h1>Pending WhatsApp Messages</h1>
<?php foreach ($msgs as $m): ?>
    <div class="msg">
        <p><strong>From:</strong> <?= htmlspecialchars($m['sender']) ?></p>
        <p><strong>Message:</strong> <?= htmlspecialchars($m['message']) ?></p>
        <p><strong>Draft:</strong> <?= htmlspecialchars($m['draft']) ?></p>
        <form method="post">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <button name="approve">Approve</button>
            <button name="reject">Reject</button>
        </form>
    </div>
<?php endforeach; ?>
</body>
</html>
