<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$fromUserId = (int)$auth['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$toUserId = (int)($input['toUserId'] ?? 0);

if ($toUserId <= 0 || $toUserId === $fromUserId) {
    respond_json(['error' => 'Invalid target user'], 400);
}

$pdo = get_db();

// Check if users exist and not already friends or pending
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$toUserId]);
if (!$stmt->fetch()) {
    respond_json(['error' => 'User not found'], 404);
}

// Existing friendship
$stmt = $pdo->prepare('SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
$stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
if ($stmt->fetch()) {
    respond_json(['error' => 'Already friends'], 409);
}

// Existing pending request
$stmt = $pdo->prepare('SELECT id FROM friend_requests WHERE ((from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)) AND status = \'pending\'');
$stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
if ($stmt->fetch()) {
    respond_json(['error' => 'Request already pending'], 409);
}

$stmt = $pdo->prepare('INSERT INTO friend_requests (from_user_id, to_user_id, status, created_at) VALUES (?, ?, \'pending\', NOW())');
$stmt->execute([$fromUserId, $toUserId]);

respond_json(['ok' => true]);


