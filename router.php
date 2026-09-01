<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

if ($path === '/robots.txt') {
    require __DIR__ . '/robots-dynamic.php';
    return true;
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

$requestedFile = realpath(__DIR__ . $path);
$projectRoot = realpath(__DIR__);

if ($requestedFile !== false
    && $projectRoot !== false
    && str_starts_with($requestedFile, $projectRoot . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pagina nu a fost găsită | EventHub</title></head><body><main><h1>Pagina nu a fost găsită</h1><p><a href="/">Înapoi la EventHub</a></p></main></body></html>';
return true;
