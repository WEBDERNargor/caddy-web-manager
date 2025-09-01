<?php
// Serve only whitelisted files from protect/other
$directory = __DIR__ . '/../../protect/other/';
$filename = isset($_GET['file']) ? basename((string)$_GET['file']) : '';

if ($filename === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Missing file parameter']);
    exit;
}

// Allow only specific extensions
$allowed = [
    'json' => 'application/json; charset=utf-8',
    'html' => 'text/html; charset=utf-8',
    'yaml' => 'application/yaml; charset=utf-8',
    'yml'  => 'application/yaml; charset=utf-8'
];

// If extension omitted, default to .html first, else as-is
$ext = pathinfo($filename, PATHINFO_EXTENSION);
if ($ext === '') {
    $try = [$filename . '.html', $filename . '.json', $filename . '.yaml', $filename . '.yml'];
} else {
    $try = [$filename];
}

$found = null; $ctype = null;
foreach ($try as $fn) {
    $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) continue;
    $path = $directory . $fn;
    if (is_file($path)) { $found = $path; $ctype = $allowed[$ext]; break; }
}

if (!$found) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'File not found']);
    exit;
}

header('Content-Type: ' . $ctype);
readfile($found);
