<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($email === '' || $password === '') {
    respond_json(['error' => 'Missing fields'], 400);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond_json(['error' => 'Invalid credentials'], 401);
}

$cfg = require __DIR__ . '/../config.php';
$now = time();
$token = jwt_encode([
    'sub' => (int)$user['id'],
    'email' => $user['email'],
    'iat' => $now,
    'exp' => $now + 60 * 60 * 24 * 7,
], $cfg['jwt_secret']);

respond_json(['token' => $token, 'user' => ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email']]]);


