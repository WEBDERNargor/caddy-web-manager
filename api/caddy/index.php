<?php
// Detect embedded include mode (internal call from a page)
$__EMBED = defined('CADDY_PROXY_EMBED') && CADDY_PROXY_EMBED;
// Load config
$config = require __DIR__ . "/../../config.php";

// Debug header to confirm routing reaches this proxy (skip when embedded)
if (!$__EMBED) {
    header('X-Proxy-By: caddy-proxy');
}

// ===========================
// CONFIG: Caddy Admin API URL
// ===========================
$target = $config['web']['caddy_url'] ?? 'http://127.0.0.1:2019';
$target = rtrim($target, '/');

// ===========================
// Resolve path after this index.php (works with or without PATH_INFO)
// ===========================
$path = '/';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/'); // e.g. /api/caddy
    // Try to strip /index.php prefix, or base dir (built-in server include case)
    $indexPrefix = $baseDir . '/index.php';
    if ($scriptName && strpos($uriPath, $scriptName) === 0) {
        $path = substr($uriPath, strlen($scriptName));
    } elseif ($indexPrefix !== '' && strpos($uriPath, $indexPrefix) === 0) {
        $path = substr($uriPath, strlen($indexPrefix));
    } elseif ($baseDir !== '' && strpos($uriPath, $baseDir . '/') === 0) {
        // e.g. URI: /api/caddy/config, baseDir: /api/caddy -> path: /config
        $path = substr($uriPath, strlen($baseDir));
    } else {
        $path = '/';
    }
    if ($path === '' || $path === false) $path = '/';
}

// Forward query string
$qs = $_SERVER['QUERY_STRING'] ?? '';
$url = $target . $path . ($qs ? ('?' . $qs) : '');

// Debug mode: return mapping info without contacting upstream
if (isset($_GET['debug'])) {
    if (!$__EMBED) header('Content-Type: application/json');
    echo json_encode([
        'target' => $target,
        'path' => $path,
        'incoming_uri' => ($_SERVER['REQUEST_URI'] ?? null),
        'script_name' => ($_SERVER['SCRIPT_NAME'] ?? null),
        'resolved_url' => $url,
        'method' => ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ===========================
// Prepare cURL
// ===========================
$ch = curl_init($url);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Body passthrough
$input = file_get_contents('php://input');
if ($input !== false && $input !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Headers passthrough (filter problematic ones)
$headers = [];
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $key => $value) {
        $lk = strtolower($key);
        if (in_array($lk, ['host', 'content-length', 'accept-encoding', 'cookie'])) continue;
        $headers[] = $key . ': ' . $value;
    }
}
// Add forwarding hints
if (!empty($_SERVER['REMOTE_ADDR'])) {
    $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
}
if (!empty($_SERVER['HTTP_HOST'])) {
    $headers[] = 'X-Forwarded-Host: ' . $_SERVER['HTTP_HOST'];
}
if (!empty($_SERVER['REQUEST_SCHEME'])) {
    $headers[] = 'X-Forwarded-Proto: ' . $_SERVER['REQUEST_SCHEME'];
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
// Fail fast: 1s connect, 2s total
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);

// ===========================
// Execute
// ===========================
$response = curl_exec($ch);
if ($response === false) {
    if (!$__EMBED) {
        http_response_code(502);
        header('Content-Type: application/json');
    }
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Split header/body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 200;
curl_close($ch);

// Set status code (skip when embedded)
if (!$__EMBED) http_response_code($status);

// Forward headers (skip hop-by-hop) — skip entirely when embedded
if (!$__EMBED) {
    $headerLines = preg_split('/\r\n/', $header, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($headerLines as $line) {
        if (stripos($line, 'HTTP/') === 0) continue; // status line
        if (stripos($line, 'Transfer-Encoding:') === 0) continue;
        if (stripos($line, 'Content-Length:') === 0) continue; // PHP will set automatically
        if (stripos($line, 'Connection:') === 0) continue;
        header($line, false);
    }
}

// Output body
echo $body;
