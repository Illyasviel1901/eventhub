<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireAuthentication();
$user = currentUser();
$pageTitle = 'Contul meu';
$pageDescription = 'Contul tău EventHub.';
$currentPage = 'account';
require __DIR__ . '/includes/header.php';
?>
<section class="account-hero">
    <div class="shell narrow">
        <p class="eyebrow">Cont EventHub</p>
        <h1>Bun venit, <?= e($user['name']) ?></h1>
    </div>
</section>
<section class="section">
    <div class="shell narrow">
        <div class="profile-card">
            <div class="profile-avatar" aria-hidden="true"><?= e(substr($user['name'], 0, 1)) ?></div>
            <div><p class="profile-label">Nume</p><h2><?= e($user['name']) ?></h2><p class="profile-label">Email</p><p><?= e($user['email']) ?></p></div>
        </div>
        <?php if ($user['role'] === 'ADMIN'): ?>
            <div class="account-note"><h2>Administrarea EventHub</h2><p>Contul tău are acces la secțiunea administrativă protejată.</p><a class="button button-primary" href="admin/index.php">Deschide panoul administrativ</a></div>
        <?php else: ?>
            <div class="account-note"><h2>Planifică evenimentul</h2><p>Poți solicita o locație și urmări statusul cererilor trimise.</p><div class="button-row"><a class="button button-primary" href="venues.php">Explorează locațiile</a><a class="button button-secondary" href="my-reservations.php">Rezervările mele</a><a class="button button-secondary" href="account-edit.php">Editează datele contului</a></div></div>
        <?php endif; ?>
        <div class="logout-panel">
            <div><h2>Încheierea sesiunii</h2><p>Te poți deconecta în siguranță de pe acest dispozitiv.</p></div>
            <form method="post" action="logout.php" data-confirm="Sigur dorești să te deconectezi?">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button class="button button-danger" type="submit">Deconectare</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
