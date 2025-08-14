<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// Very small router based on REQUEST_URI and method

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Remove leading project path if accessing via subdir (e.g., /Message_system/api)
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($scriptDir && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}

// Normalize trailing slashes
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Dispatch
switch ([$method, $path]) {
    // Auth
    case ['POST', '/auth/register']:
        require __DIR__ . '/routes/auth_register.php';
        break;
    case ['POST', '/auth/login']:
        require __DIR__ . '/routes/auth_login.php';
        break;

    // Friend requests
    case ['POST', '/friends/request']:
        require __DIR__ . '/routes/friends_request.php';
        break;
    case ['POST', '/friends/respond']:
        require __DIR__ . '/routes/friends_respond.php';
        break;
    case ['GET', '/friends/list']:
        require __DIR__ . '/routes/friends_list.php';
        break;

    // Messages
    case ['POST', '/messages/send']:
        require __DIR__ . '/routes/messages_send.php';
        break;
    case ['GET', '/messages/list']:
        require __DIR__ . '/routes/messages_list.php';
        break;
    case ['PATCH', '/messages/edit']:
        require __DIR__ . '/routes/messages_edit.php';
        break;
    case ['DELETE', '/messages/delete']:
        require __DIR__ . '/routes/messages_delete.php';
        break;

    default:
        respond_json(['error' => 'Not Found', 'method' => $method, 'path' => $path], 404);
}


