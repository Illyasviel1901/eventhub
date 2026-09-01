<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Locații de evenimente în București';
$pageDescription = 'Compară cinci locații reale din București, cu descriere, adresă, capacitate și formular de solicitare.';
$currentPage = 'venues';
$venues = [];
$loadError = false;

try {
    $venues = getVenues();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $loadError = true;
}

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $pageDescription,
    'mainEntity' => [
        '@type' => 'ItemList',
        'numberOfItems' => count($venues),
        'itemListElement' => array_map(
            static fn (array $venue, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/') . '/venue.php?id=' . (int) $venue['id'],
                'name' => $venue['name'],
            ],
            $venues,
            array_keys($venues)
        ),
    ],
];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="shell narrow">
        <h1>Locații pentru fiecare fel de eveniment</h1>
        <p>Alege un spațiu potrivit numărului de invitați și atmosferei pe care ți-o dorești.</p>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="results-heading">
            <h2>Toate locațiile</h2>
            <?php if (!$loadError): ?><p><?= count($venues) ?> <?= count($venues) === 1 ? 'locație disponibilă' : 'locații disponibile' ?></p><?php endif; ?>
        </div>

        <?php if ($loadError): ?>
            <div class="notice notice-error" role="alert">Nu am putut încărca locațiile. Verifică serviciul bazei de date și încearcă din nou.</div>
        <?php elseif ($venues === []): ?>
            <div class="empty-state"><h2>Nu există locații publicate</h2><p>Locațiile adăugate vor apărea automat aici.</p></div>
        <?php else: ?>
            <div class="venue-grid">
                <?php foreach ($venues as $index => $venue): ?>
                    <article class="venue-card">
                        <?php if (!empty($venue['main_image'])): ?>
                            <img class="venue-card-image" src="<?= e(venueImageUrl((string) $venue['main_image'])) ?>" alt="<?= e((string) ($venue['main_image_alt'] ?? 'Imagine ilustrativă a locației')) ?>" width="1600" height="1000" loading="lazy">
                        <?php else: ?>
                            <div class="venue-image venue-image-<?= ($index % 3) + 1 ?>" aria-hidden="true"><span><?= e(substr($venue['name'], 0, 1)) ?></span></div>
                        <?php endif; ?>
                        <div class="venue-card-body">
                            <p class="venue-meta"><span><?= (int) $venue['capacity'] ?> persoane</span></p>
                            <h2><?= e($venue['name']) ?></h2>
                            <p><?= e($venue['description']) ?></p>
                            <p class="address">⌖ <?= e($venue['address']) ?></p>
                            <a class="card-link" href="venue.php?id=<?= (int) $venue['id'] ?>">Detalii și disponibilitate <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
