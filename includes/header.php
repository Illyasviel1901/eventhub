<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/analytics.php';

$pageTitle = $pageTitle ?? 'EventHub';
$pageDescription = $pageDescription ?? 'EventHub — locații pentru evenimente memorabile.';
$currentPage = $currentPage ?? '';
$basePath = $basePath ?? '';
$authenticatedUser = currentUser();
try {
    recordPageVisit();
} catch (Throwable $exception) {
    error_log('Analytics: ' . $exception->getMessage());
}
$pendingRequests = 0;
if ($authenticatedUser !== null && $authenticatedUser['role'] === 'ADMIN') {
    require_once __DIR__ . '/reservation-management.php';
    try {
        $pendingRequests = pendingReservationCount();
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
    }
}
$flash = pullFlash();
$siteBaseUrl = rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/');
$scriptPath = ltrim(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php')), '/');
$publicIndexableScripts = ['index.php', 'venues.php', 'venue.php'];
$seoIndexable = $seoIndexable ?? in_array($scriptPath, $publicIndexableScripts, true);
$canonicalPath = $canonicalPath ?? $scriptPath;
if ($scriptPath === 'venue.php' && isset($venue['id'])) {
    $canonicalPath = 'venue.php?id=' . (int) $venue['id'];
}
$canonicalUrl = $siteBaseUrl . '/' . ltrim($canonicalPath, '/');
$openGraphImage = $siteBaseUrl . '/assets/images/venues/palatul-bragadiru/sala-evenimente.png';
$structuredData = $structuredData ?? [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'EventHub',
    'url' => $siteBaseUrl . '/',
    'inLanguage' => 'ro-RO',
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="<?= $seoIndexable ? 'index, follow' : 'noindex, nofollow' ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <link rel="sitemap" type="application/xml" href="<?= e($siteBaseUrl) ?>/sitemap.php" title="Sitemap EventHub">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="EventHub">
    <meta property="og:title" content="<?= e($pageTitle) ?> | EventHub">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($openGraphImage) ?>">
    <meta property="og:image:alt" content="EventHub — locații pentru evenimente în București">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?> | EventHub">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($openGraphImage) ?>">
    <title><?= e($pageTitle) ?> | EventHub</title>
    <link rel="icon" href="<?= e($basePath) ?>assets/images/favicon.svg" type="image/svg+xml">
    <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main-content">Sari la conținut</a>
<header class="site-header">
    <div class="shell nav-wrap">
        <a class="brand" href="<?= e($basePath) ?>index.php" aria-label="EventHub — pagina principală">
            <span class="brand-mark" aria-hidden="true">E</span>
            <span>EventHub</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-navigation">
            <span class="sr-only">Deschide meniul</span>
            <span aria-hidden="true">☰</span>
        </button>
        <nav id="main-navigation" class="main-nav" aria-label="Navigație principală">
            <a href="<?= e($basePath) ?>index.php"<?= $currentPage === 'home' ? ' aria-current="page"' : '' ?>>Acasă</a>
            <a href="<?= e($basePath) ?>venues.php"<?= $currentPage === 'venues' ? ' aria-current="page"' : '' ?>>Locații</a>
            <?php if ($authenticatedUser === null || $authenticatedUser['role'] === 'USER'): ?>
                <a href="<?= e($basePath) ?>contact.php"<?= $currentPage === 'contact' ? ' aria-current="page"' : '' ?>>Contact</a>
            <?php endif; ?>
            <?php if ($authenticatedUser === null): ?>
                <a href="<?= e($basePath) ?>login.php"<?= $currentPage === 'login' ? ' aria-current="page"' : '' ?>>Autentificare</a>
            <?php else: ?>
                <a href="<?= e($basePath) ?>account.php"<?= $currentPage === 'account' ? ' aria-current="page"' : '' ?>>Contul meu</a>
                <?php if ($authenticatedUser['role'] === 'USER'): ?>
                    <a href="<?= e($basePath) ?>my-reservations.php"<?= $currentPage === 'my-reservations' ? ' aria-current="page"' : '' ?>>Rezervările mele</a>
                <?php endif; ?>
                <?php if ($authenticatedUser['role'] === 'ADMIN'): ?>
                    <a class="nav-with-badge" href="<?= e($basePath) ?>admin/reservations.php"<?= $currentPage === 'requests' ? ' aria-current="page"' : '' ?>>Cereri<?php if ($pendingRequests > 0): ?><span class="nav-badge" aria-label="<?= $pendingRequests ?> cereri în așteptare"><?= $pendingRequests ?></span><?php endif; ?></a>
                    <a href="<?= e($basePath) ?>admin/analytics.php"<?= $currentPage === 'analytics' ? ' aria-current="page"' : '' ?>>Statistici</a>
                    <a href="<?= e($basePath) ?>admin/reports.php"<?= $currentPage === 'reports' ? ' aria-current="page"' : '' ?>>Rapoarte</a>
                    <a href="<?= e($basePath) ?>admin/index.php"<?= $currentPage === 'admin' ? ' aria-current="page"' : '' ?>>Administrare</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if ($flash !== null): ?>
    <div class="flash-wrap shell"><div class="notice notice-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div></div>
<?php endif; ?>
<main id="main-content">
