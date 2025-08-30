<?php
require_once __DIR__ . '/includes/CoreFunction.php';


if (!file_exists(__DIR__ . '/.env')) {
    die('.env file not found');
}

loadEnv(__DIR__ . '/.env');
if($_ENV['WEB_MODE']=='development'){

    show_error_log("Loading config.php");
    show_error_log("JWT_SECRET value: " . ($_ENV['JWT_SECRET'] ?? 'not set'));
}
$config = [
    
    'web' => [
        'mode'=> $_ENV['WEB_MODE'] ?? null,
        'url' => $_ENV['WEB_URL'] ?? null,
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? null,
        'name' => $_ENV['WEB_NAME'] ?? null,
        'socket_port' => $_ENV['SOCKET_PORT'] ?? null,
    'temp_extensions' => [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ],
        'upload_extensions' => [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ],
        'protect_folder' => ['protect/uploads', 'temp','protect'],
    ],
    'db' => [
        // driver: mysql | sqlite | none
        'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
        // MySQL settings (kept for backward compatibility)
        'host' => $_ENV['MYSQL_HOST'] ?? null,
        'database' => $_ENV['MYSQL_DATABASE'] ?? null,
        'username' => $_ENV['MYSQL_USER'] ?? null,
        'password' => $_ENV['MYSQL_PASSWORD'] ?? null,
        'port' => $_ENV['MYSQL_PORT'] ?? null,
        'charset' => $_ENV['MYSQL_CHARSET'] ?? null,
        // SQLite settings
        'sqlite_path' => $_ENV['SQLITE_PATH'] ?? (__DIR__ . '/storage/database.sqlite'),
    ],
    'layout' => [
        'default_head' => '
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">'
    ]
];

return $config;
