<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$userId = (int)$auth['sub'];

// Query params: peerId, cursor(optional), limit(optional)
$peerId = isset($_GET['peerId']) ? (int)$_GET['peerId'] : 0;
$cursor = isset($_GET['cursor']) ? (int)$_GET['cursor'] : 0; // message id to start before
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 30;

if ($peerId <= 0) {
    respond_json(['error' => 'peerId required'], 400);
}

$pdo = get_db();

// Ensure friendship
$stmt = $pdo->prepare('SELECT 1 FROM friends WHERE user_id = ? AND friend_id = ? LIMIT 1');
$stmt->execute([$userId, $peerId]);
if (!$stmt->fetch()) {
    respond_json(['error' => 'Not friends'], 403);
}

$params = [$userId, $peerId, $peerId, $userId];
$cursorSql = '';
if ($cursor > 0) {
    $cursorSql = ' AND m.id < ?';
    $params[] = $cursor;
}

$sql = 'SELECT m.id, m.sender_id, m.receiver_id, m.content, m.created_at, m.updated_at, m.is_deleted
        FROM messages m
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))' . $cursorSql . '
        ORDER BY m.id DESC
        LIMIT ' . (int)$limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Reverse to ascending chronological order for UI
$messages = array_reverse($rows);

// Next cursor for pagination
$nextCursor = count($rows) === $limit ? (int)min(array_column($rows, 'id')) : null;

respond_json(['messages' => $messages, 'nextCursor' => $nextCursor]);


