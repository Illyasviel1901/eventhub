<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$venue = null;
$venueImages = [];
$loadError = false;

if ($id !== false && $id !== null) {
    try {
        $venue = getVenueById($id);
        if ($venue !== null) {
            $venueImages = getVenueImages((int) $venue['id']);
        }
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $loadError = true;
    }
}

if (!$loadError && $venue === null) {
    http_response_code(404);
}

$pageTitle = $venue === null ? 'Locație indisponibilă' : $venue['name'] . ' — București';
$pageDescription = $venue === null ? 'Locația solicitată nu a fost găsită.' : $venue['description'];
$currentPage = 'venues';
$seoIndexable = $venue !== null;
if ($venue !== null) {
    $venueImageUrls = array_map(
        static fn (array $image): string => rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/') . '/' . ltrim($image['image_path'], '/'),
        $venueImages
    );
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'EventVenue',
        'name' => $venue['name'],
        'description' => $venue['description'],
        'url' => rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/') . '/venue.php?id=' . (int) $venue['id'],
        'maximumAttendeeCapacity' => (int) $venue['capacity'],
        'image' => $venueImageUrls,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => trim(str_replace(', București', '', (string) $venue['address'])),
            'addressLocality' => 'București',
            'addressCountry' => 'RO',
        ],
    ];
}

require __DIR__ . '/includes/header.php';
?>
<?php if ($loadError): ?>
    <section class="section"><div class="shell narrow"><div class="notice notice-error" role="alert"><h1>Eroare temporară</h1><p>Datele locației nu pot fi încărcate momentan.</p><a class="text-link" href="venues.php">Înapoi la locații</a></div></div></section>
<?php elseif ($venue === null): ?>
    <section class="section"><div class="shell narrow"><div class="empty-state"><p class="error-code">404</p><h1>Locația nu a fost găsită</h1><p>Adresa poate fi greșită sau locația nu mai este disponibilă.</p><a class="button button-primary" href="venues.php">Vezi toate locațiile</a></div></div></section>
<?php else: ?>
    <section class="detail-hero">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Fir de navigare"><a href="index.php">Acasă</a><span>/</span><a href="venues.php">Locații</a><span>/</span><span aria-current="page"><?= e($venue['name']) ?></span></nav>
            <div class="detail-grid">
                <div class="detail-copy">
                    <p class="eyebrow">Locație EventHub</p>
                    <h1><?= e($venue['name']) ?></h1>
                    <p class="detail-description"><?= e($venue['description']) ?></p>
                    <div class="detail-facts">
                        <div><span>Capacitate</span><strong><?= (int) $venue['capacity'] ?> persoane</strong></div>
                        <div><span>Adresă</span><strong><?= e($venue['address']) ?></strong></div>
                    </div>
                    <?php $viewer = currentUser(); ?>
                    <?php if ($viewer !== null && $viewer['role'] === 'ADMIN'): ?>
                        <a class="button button-primary" href="admin/reservation-create.php">Adaugă solicitare pentru un client</a>
                    <?php else: ?>
                        <a class="button button-primary" href="reservation-create.php?venue_id=<?= (int) $venue['id'] ?>">Solicită această locație</a>
                    <?php endif; ?>
                </div>
                <?php if ($venueImages !== []): ?>
                    <?php $mainImage = $venueImages[0]; ?>
                    <div class="venue-gallery" data-gallery>
                        <div class="gallery-stage">
                            <button class="gallery-arrow gallery-prev" type="button" data-gallery-prev aria-label="Imaginea anterioară">←</button>
                            <img class="gallery-main-image" data-gallery-main src="<?= e($mainImage['image_path']) ?>" alt="<?= e($mainImage['alt_text']) ?>" width="1600" height="1000">
                            <button class="gallery-arrow gallery-next" type="button" data-gallery-next aria-label="Imaginea următoare">→</button>
                        </div>
                        <div class="gallery-thumbnails" role="list" aria-label="Imaginile locației">
                            <?php foreach ($venueImages as $imageIndex => $image): ?>
                                <button class="gallery-thumbnail<?= $imageIndex === 0 ? ' is-active' : '' ?>" type="button" data-gallery-thumbnail data-src="<?= e($image['image_path']) ?>" data-alt="<?= e($image['alt_text']) ?>" aria-label="Afișează: <?= e($image['alt_text']) ?>" aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>">
                                    <img src="<?= e($image['image_path']) ?>" alt="" width="240" height="150" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="gallery-disclaimer">Galerie foto a locației.</p>
                    </div>
                <?php else: ?>
                    <div class="detail-visual" aria-hidden="true"><span><?= e(substr($venue['name'], 0, 1)) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section section-muted">
        <div class="shell narrow request-cta">
            <p class="eyebrow">Planifică evenimentul</p>
            <h2>Verifică data în formularul de solicitare</h2>
            <p>Disponibilitatea este verificată imediat după selectarea datei. Datele ocupate nu sunt publicate în pagina locației.</p>
            <?php if ($viewer !== null && $viewer['role'] === 'ADMIN'): ?>
                <p>Conturile ADMIN gestionează solicitările în numele clienților.</p>
                <a class="button button-primary" href="admin/reservation-create.php">Adaugă solicitare pentru un client</a>
            <?php else: ?>
                <a class="button button-primary" href="reservation-create.php?venue_id=<?= (int) $venue['id'] ?>">Solicită această locație</a>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
