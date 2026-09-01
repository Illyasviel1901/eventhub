<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$baseUrl = rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/');
$urls = [
    ['loc' => $baseUrl . '/index.php', 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => $baseUrl . '/venues.php', 'changefreq' => 'weekly', 'priority' => '0.9'],
];

try {
    $venueIds = db()->query('SELECT id FROM venues ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($venueIds as $venueId) {
        $urls[] = [
            'loc' => $baseUrl . '/venue.php?id=' . (int) $venueId,
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];
    }
} catch (Throwable $exception) {
    error_log('Sitemap: ' . $exception->getMessage());
}

$xml = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= $xml($url['loc']) ?></loc>
        <changefreq><?= $xml($url['changefreq']) ?></changefreq>
        <priority><?= $xml($url['priority']) ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
