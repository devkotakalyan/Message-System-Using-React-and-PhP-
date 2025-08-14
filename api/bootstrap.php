<?php

// Shared bootstrap: loads config, sets up DB, common helpers

declare(strict_types=1);

// Display errors for development; turn off in production
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$config = require __DIR__ . '/config.php';

// Simple CORS handler
if (php_sapi_name() !== 'cli') {
    $cors = $config['cors'];
    header('Access-Control-Allow-Origin: ' . ($cors['allowed_origins'][0] ?? '*'));
    header('Access-Control-Allow-Methods: ' . implode(', ', $cors['allowed_methods']));
    header('Access-Control-Allow-Headers: ' . implode(', ', $cors['allowed_headers']));
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function respond_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function get_db(PDO $existing = null): PDO {
    static $pdo = null;
    if ($existing) {
        $pdo = $existing;
        return $pdo;
    }
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = (require __DIR__ . '/config.php')['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']
    );
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

// Minimal JWT utilities (HS256)
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload)),
    ];
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, $secret, true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

function jwt_decode(string $jwt, string $secret): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    [$header64, $payload64, $sig64] = $parts;
    $header = json_decode(base64url_decode($header64), true);
    if (($header['alg'] ?? '') !== 'HS256') {
        return null;
    }
    $payload = json_decode(base64url_decode($payload64), true);
    $signature = base64url_decode($sig64);
    $expected = hash_hmac('sha256', "$header64.$payload64", $secret, true);
    if (!hash_equals($expected, $signature)) {
        return null;
    }
    $now = time();
    if (isset($payload['exp']) && $payload['exp'] < $now) {
        return null;
    }
    return $payload;
}

function require_auth(): array {
    // Try several locations for the Authorization header (Apache/CGI quirks)
    $auth = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';
    if ($auth === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $auth = $headers['authorization'];
        }
    }
    if (!preg_match('/^Bearer\s+(.*)$/i', $auth, $m)) {
        respond_json(['error' => 'Missing Authorization header'], 401);
    }
    $token = $m[1];
    $cfg = require __DIR__ . '/config.php';
    $payload = jwt_decode($token, $cfg['jwt_secret']);
    if (!$payload) {
        respond_json(['error' => 'Invalid or expired token'], 401);
    }
    return $payload;
}


