<?php

// Basic configuration for the API

return [
    // Update these for your local MySQL setup (XAMPP defaults shown)
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'message_system',
        'user' => 'root',
        'password' => '', // default XAMPP root has no password
        'charset' => 'utf8mb4',
    ],

    // Change this secret in production to a long random string
    'jwt_secret' => 'change_me_to_a_long_random_secret_string',

    // CORS settings (adjust to your frontend origin during development)
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
    ],
];


