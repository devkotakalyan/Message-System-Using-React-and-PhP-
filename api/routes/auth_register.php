<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');
$name = trim((string)($input['name'] ?? ''));

if ($email === '' || $password === '' || $name === '') {
    respond_json(['error' => 'Missing fields'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_json(['error' => 'Invalid email'], 400);
}

$pdo = get_db();

// Ensure unique email
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    respond_json(['error' => 'Email already registered'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())');
$stmt->execute([$name, $email, $hash]);
$userId = (int)$pdo->lastInsertId();

$cfg = require __DIR__ . '/../config.php';
$now = time();
$token = jwt_encode([
    'sub' => $userId,
    'email' => $email,
    'iat' => $now,
    'exp' => $now + 60 * 60 * 24 * 7, // 7 days
], $cfg['jwt_secret']);

respond_json(['token' => $token, 'user' => ['id' => $userId, 'name' => $name, 'email' => $email]]);


