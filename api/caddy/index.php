<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$config = require __DIR__ . "/../../config.php";
// Detect embedded include mode (internal call from a page)
$__EMBED = defined('CADDY_PROXY_EMBED') && CADDY_PROXY_EMBED;
// Load config


// Debug header to confirm routing reaches this proxy (skip when embedded)
if (!$__EMBED) {
    header('X-Proxy-By: caddy-proxy');
}

// ===========================
// API Key verification (non-embedded requests only)
// ===========================
if (!$__EMBED) {
    // Lazy-load DB utilities

    
    
    try {
        $apiKey = null;
        // Header first
        foreach (['HTTP_X_API_KEY', 'HTTP_AUTHORIZATION'] as $hdr) {
            if (!empty($_SERVER[$hdr])) {
                $val = trim((string)$_SERVER[$hdr]);
                if ($hdr === 'HTTP_AUTHORIZATION' && stripos($val, 'Bearer ') === 0) {
                    $val = trim(substr($val, 7));
                }
                if ($val !== '') { $apiKey = $val; break; }
            }
        }
        // Fallback to query ?api_key=
        if (!$apiKey && isset($_GET['api_key'])) {
            $apiKey = trim((string)$_GET['api_key']);
        }

        if (!$apiKey) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing API key']);
            exit;
        }

        // Verify in DB
        $pdo = \App\includes\Database::getInstance();
        if (!$pdo) { throw new \RuntimeException('Database not configured'); }
        $stmt = $pdo->prepare('SELECT id, active FROM api_keys WHERE token = :t LIMIT 1');
        $stmt->execute([':t' => $apiKey]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['active'] !== 1) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid API key']);
            exit;
        }
        // update last_used_at (best-effort)
        try {
            $up = $pdo->prepare('UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = :id');
            $up->execute([':id' => $row['id']]);
        } catch (\Throwable $e) { /* ignore */ }
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API key verification failed', 'detail' => $e->getMessage()]);
        exit;
    }
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
