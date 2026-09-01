<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Locații pentru evenimente în București';
$pageDescription = 'Descoperă cinci locații reale din București și trimite online o cerere de rezervare prin EventHub.';
$currentPage = 'home';
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'EventHub',
    'url' => rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/') . '/',
    'email' => environment('COMPANY_EMAIL', 'gomesjohn929@gmail.com'),
    'areaServed' => ['@type' => 'City', 'name' => 'București'],
    'description' => $pageDescription,
];
$venues = [];
$loadError = false;

try {
    $venues = array_slice(getVenues(), 0, 3);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $loadError = true;
}

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="shell hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Spațiul potrivit. Evenimentul tău.</p>
            <h1>Momente speciale în locații atent alese</h1>
            <div class="button-row">
                <a class="button button-primary" href="venues.php">Descoperă locațiile</a>
                <a class="button button-secondary" href="#despre">Cum funcționează</a>
            </div>
        </div>
        <div class="hero-visual" aria-label="Ilustrație abstractă a unei săli de evenimente">
            <span class="visual-card visual-card-main">EventHub</span>
            <span class="visual-card visual-card-small">Locații în București</span>
        </div>
    </div>
</section>

<section id="despre" class="section section-muted">
    <div class="shell">
        <div class="section-heading">
            <p class="eyebrow">Simplu și transparent</p>
            <h2>De la inspirație la locația potrivită</h2>
        </div>
        <div class="steps-grid">
            <article class="step-card"><span>01</span><h3>Explorează</h3><p>Compară descrierea, adresa și capacitatea fiecărui spațiu.</p></article>
            <article class="step-card"><span>02</span><h3>Verifică</h3><p>Consultă datile deja ocupate pentru locația aleasă.</p></article>
            <article class="step-card"><span>03</span><h3>Solicită</h3><p>Trimite o cerere de rezervare catre echipa EventHub</p></article>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-heading heading-row">
            <div><p class="eyebrow">Spațiile noastre</p><h2>Locații recomandate</h2></div>
            <a class="text-link" href="venues.php">Vezi toate locațiile <span aria-hidden="true">→</span></a>
        </div>

        <?php if ($loadError): ?>
            <div class="notice notice-error" role="alert">Locațiile nu pot fi încărcate momentan. Încearcă din nou mai târziu.</div>
        <?php elseif ($venues === []): ?>
            <div class="empty-state"><h3>Nu există locații publicate</h3><p>Revino în curând pentru noutăți.</p></div>
        <?php else: ?>
            <div class="venue-grid">
                <?php foreach ($venues as $index => $venue): ?>
                    <article class="venue-card">
                        <?php if (!empty($venue['main_image'])): ?>
                            <img class="venue-card-image" src="<?= e($venue['main_image']) ?>" alt="<?= e((string) ($venue['main_image_alt'] ?? 'Imagine ilustrativă a locației')) ?>" width="1600" height="1000" loading="lazy">
                        <?php else: ?>
                            <div class="venue-image venue-image-<?= ($index % 3) + 1 ?>" aria-hidden="true"><span><?= e(substr($venue['name'], 0, 1)) ?></span></div>
                        <?php endif; ?>
                        <div class="venue-card-body">
                            <p class="venue-meta"><span><?= (int) $venue['capacity'] ?> persoane</span></p>
                            <h3><?= e($venue['name']) ?></h3>
                            <p><?= e($venue['description']) ?></p>
                            <p class="address">⌖ <?= e($venue['address']) ?></p>
                            <a class="card-link" href="venue.php?id=<?= (int) $venue['id'] ?>">Vezi detalii <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-section">
    <div class="shell cta-content">
        <div><p class="eyebrow eyebrow-light">Pregătit să începi?</p><h2>Găsește cadrul evenimentului tău</h2></div>
        <a class="button button-light" href="venues.php">Explorează locațiile</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
