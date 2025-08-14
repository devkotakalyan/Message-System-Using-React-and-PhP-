<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$userId = (int)$auth['sub'];

$pdo = get_db();

// Friends
$stmt = $pdo->prepare('SELECT u.id, u.name, u.email FROM friends f JOIN users u ON u.id = f.friend_id WHERE f.user_id = ? ORDER BY u.name');
$stmt->execute([$userId]);
$friends = $stmt->fetchAll();

// Pending received requests
$stmt = $pdo->prepare('SELECT fr.id as requestId, u.id as fromUserId, u.name, u.email, fr.created_at FROM friend_requests fr JOIN users u ON u.id = fr.from_user_id WHERE fr.to_user_id = ? AND fr.status = "pending" ORDER BY fr.created_at DESC');
$stmt->execute([$userId]);
$pending = $stmt->fetchAll();

respond_json(['friends' => $friends, 'pending' => $pending]);


