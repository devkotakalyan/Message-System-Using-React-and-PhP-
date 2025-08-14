<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$userId = (int)$auth['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$messageId = (int)($input['messageId'] ?? 0);
$newContent = trim((string)($input['content'] ?? ''));

if ($messageId <= 0 || $newContent === '') {
    respond_json(['error' => 'Invalid input'], 400);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, sender_id, is_deleted FROM messages WHERE id = ?');
$stmt->execute([$messageId]);
$msg = $stmt->fetch();
if (!$msg) {
    respond_json(['error' => 'Not found'], 404);
}
if ((int)$msg['sender_id'] !== $userId) {
    respond_json(['error' => 'Forbidden'], 403);
}
if ((int)$msg['is_deleted'] === 1) {
    respond_json(['error' => 'Cannot edit deleted message'], 400);
}

$stmt = $pdo->prepare('UPDATE messages SET content = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([$newContent, $messageId]);

respond_json(['ok' => true]);


