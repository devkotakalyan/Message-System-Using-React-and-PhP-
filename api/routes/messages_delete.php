<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$userId = (int)$auth['sub'];

// For DELETE, allow JSON body or query param
$raw = file_get_contents('php://input');
$input = $raw ? (json_decode($raw, true) ?? []) : [];
$messageId = isset($input['messageId']) ? (int)$input['messageId'] : (isset($_GET['messageId']) ? (int)$_GET['messageId'] : 0);

if ($messageId <= 0) {
    respond_json(['error' => 'messageId required'], 400);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, sender_id FROM messages WHERE id = ?');
$stmt->execute([$messageId]);
$msg = $stmt->fetch();
if (!$msg) {
    respond_json(['error' => 'Not found'], 404);
}
if ((int)$msg['sender_id'] !== $userId) {
    respond_json(['error' => 'Forbidden'], 403);
}

// Soft-delete
$stmt = $pdo->prepare('UPDATE messages SET is_deleted = 1, content = \'\', updated_at = NOW() WHERE id = ?');
$stmt->execute([$messageId]);

respond_json(['ok' => true]);


