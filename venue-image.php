<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(404);
    exit;
}

$statement = db()->prepare(
    'SELECT image_data, mime_type
     FROM venue_images
     WHERE id = :id
       AND image_data IS NOT NULL
     LIMIT 1'
);
$statement->execute(['id' => $id]);
$image = $statement->fetch();

if ($image === false || !is_string($image['mime_type'])) {
    http_response_code(404);
    exit;
}

$data = $image['image_data'];
if (is_resource($data)) {
    $data = stream_get_contents($data);
}

$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!is_string($data) || $data === '' || !in_array($image['mime_type'], $allowedMimeTypes, true)) {
    http_response_code(404);
    exit;
}

$etag = '"' . hash('sha256', $data) . '"';
if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . strlen($data));
header('Cache-Control: public, max-age=86400, immutable');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');
echo $data;
