<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$fromUserId = (int)$auth['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$toUserId = (int)($input['toUserId'] ?? 0);
$content = trim((string)($input['content'] ?? ''));

if ($toUserId <= 0 || $content === '') {
    respond_json(['error' => 'Invalid input'], 400);
}

$pdo = get_db();

// Only allow messaging between friends
$stmt = $pdo->prepare('SELECT 1 FROM friends WHERE user_id = ? AND friend_id = ? LIMIT 1');
$stmt->execute([$fromUserId, $toUserId]);
if (!$stmt->fetch()) {
    respond_json(['error' => 'Not friends'], 403);
}

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content, created_at, updated_at, is_deleted) VALUES (?, ?, ?, NOW(), NOW(), 0)');
$stmt->execute([$fromUserId, $toUserId, $content]);
$messageId = (int)get_db()->lastInsertId();

respond_json(['id' => $messageId, 'ok' => true]);


