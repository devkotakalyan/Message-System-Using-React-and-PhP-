<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$auth = require_auth();
$userId = (int)$auth['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$requestId = (int)($input['requestId'] ?? 0);
$action = (string)($input['action'] ?? ''); // 'accept' or 'decline'

if ($requestId <= 0 || !in_array($action, ['accept', 'decline'], true)) {
    respond_json(['error' => 'Invalid input'], 400);
}

$pdo = get_db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT * FROM friend_requests WHERE id = ? FOR UPDATE');
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();
    if (!$req || (int)$req['to_user_id'] !== $userId || $req['status'] !== 'pending') {
        $pdo->rollBack();
        respond_json(['error' => 'Invalid request'], 400);
    }

    if ($action === 'decline') {
        $stmt = $pdo->prepare('UPDATE friend_requests SET status = \'declined\', responded_at = NOW() WHERE id = ?');
        $stmt->execute([$requestId]);
        $pdo->commit();
        respond_json(['ok' => true]);
    }

    // Accept: create friendship (two entries for symmetric lookup)
    $stmt = $pdo->prepare('INSERT INTO friends (user_id, friend_id, created_at) VALUES (?, ?, NOW()), (?, ?, NOW())');
    $stmt->execute([(int)$req['from_user_id'], $userId, $userId, (int)$req['from_user_id']]);

    $stmt = $pdo->prepare('UPDATE friend_requests SET status = \'accepted\', responded_at = NOW() WHERE id = ?');
    $stmt->execute([$requestId]);

    $pdo->commit();
    respond_json(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond_json(['error' => 'Server error'], 500);
}


