<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/analytics.php';

requireRole('ADMIN', '../login.php');

$statistics = [
    'venues' => (int) db()->query('SELECT COUNT(*) FROM venues')->fetchColumn(),
    'users' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'USER'")->fetchColumn(),
    'pending' => (int) db()->query("SELECT COUNT(*) FROM reservations WHERE status = 'PENDING'")->fetchColumn(),
    'approved' => (int) db()->query("SELECT COUNT(*) FROM reservations WHERE status = 'APPROVED'")->fetchColumn(),
    'visits' => (int) db()->query('SELECT COUNT(*) FROM page_visits')->fetchColumn(),
];

$pageTitle = 'Administrare';
$pageDescription = 'Panoul administrativ EventHub.';
$currentPage = 'admin';
$basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-hero">
    <div class="shell">
        <p class="eyebrow">Zonă protejată</p>
        <h1>Panou administrativ</h1>
    </div>
</section>
<section class="section">
    <div class="shell">
        <div class="admin-stats" aria-label="Statistici EventHub">
            <article class="stat-card"><span>Locații</span><strong><?= $statistics['venues'] ?></strong><p>Locații publicate în catalog</p></article>
            <article class="stat-card"><span>Clienți</span><strong><?= $statistics['users'] ?></strong><p>Conturi cu rol USER</p></article>
            <article class="stat-card"><span>Solicitări în așteptare</span><strong><?= $statistics['pending'] ?></strong><p>Rezervări cu status PENDING</p></article>
            <article class="stat-card"><span>Rezervări aprobate</span><strong><?= $statistics['approved'] ?></strong><p>Rezervări cu status APPROVED</p></article>
            <article class="stat-card"><span>Accesări</span><strong><?= $statistics['visits'] ?></strong><p>Pagini vizualizate în aplicație</p></article>
        </div>
        <div class="admin-actions">
            <div><p class="eyebrow">Administrare</p><h2>Gestionează locațiile</h2><p>Adaugă locații noi și actualizează sau șterge informațiile existente. Modificările sunt reflectate imediat în catalogul public.</p></div>
            <div class="button-row admin-button-row"><a class="button button-primary button-with-badge" href="reservations.php">Solicitări<?php if ($statistics['pending'] > 0): ?><span class="button-badge"><?= $statistics['pending'] ?></span><?php endif; ?></a><a class="button button-primary" href="venues.php">Administrare locații</a><a class="button button-primary" href="users.php">Administrare utilizatori</a><a class="button button-primary" href="analytics.php">Vezi statistici</a><a class="button button-primary" href="reports.php">Rapoarte și export</a><a class="button button-secondary" href="../venues.php">Vezi site-ul public</a></div>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
