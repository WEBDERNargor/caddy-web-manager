<?php
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Bypass app routing for the Caddy Admin API proxy when using PHP's built-in server
// .htaccess is not honored by the built-in server, so we manually route here.

// 1) Friendly endpoint: /api/config/ -> api/caddy/index.php with PATH_INFO=/config/
if (preg_match('#^/api/config(?:/|$)#', $uri)) {
    // emulate PATH_INFO mapping
    $_SERVER['SCRIPT_NAME'] = '/api/caddy/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/api/caddy/index.php';
    $_SERVER['PATH_INFO'] = '/config/';
    include __DIR__ . '/api/caddy/index.php';
    return true;
}

// 2) Direct proxy path preserved: /api/caddy/*
if (preg_match('#^/api/caddy(?:/|$)#', $uri)) {
    // Always dispatch to the proxy script
    $_SERVER['SCRIPT_NAME'] = '/api/caddy/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/api/caddy/index.php';
    include __DIR__ . '/api/caddy/index.php';
    return true;
}
if (!isset($blockedDirs)) {
    $myconfig = require(__DIR__ . '/config.php');
    $blockedDirs = $myconfig['web']['protect_folder'];


    foreach ($blockedDirs as $dir) {
        // ป้องกันเข้าทุก path ที่เริ่มด้วยโฟลเดอร์ที่กำหนด
        if (preg_match("#^/{$dir}(/|$)#", $uri)) {
            http_response_code(404);
            return false;
           
        }
    }
}
$file = __DIR__ . $uri;

if (is_file($file)) {
    return false;
}
include __DIR__ . "/index.php";
