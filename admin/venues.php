<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('ADMIN', '../login.php');
$venues = getVenues();

$pageTitle = 'Administrare locații';
$pageDescription = 'Administrarea locațiilor EventHub.';
$currentPage = 'admin';
$basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading">
    <div class="shell heading-row">
        <div><p class="eyebrow">CRUD locații</p><h1>Administrare locații</h1><p>Adaugă, consultă, modifică sau șterge locațiile afișate pe site.</p></div>
        <a class="button button-primary" href="venue-create.php">Adaugă locație</a>
    </div>
</section>
<section class="section admin-section">
    <div class="shell">
        <?php if ($venues === []): ?>
            <div class="empty-state"><h2>Nu există locații</h2><p>Adaugă prima locație pentru a o publica pe site.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Locație</th><th>Adresă</th><th>Capacitate</th><th>Acțiuni</th></tr></thead>
                    <tbody>
                    <?php foreach ($venues as $venue): ?>
                        <tr>
                            <td><strong><?= e($venue['name']) ?></strong><a class="table-sub-link" href="../venue.php?id=<?= (int) $venue['id'] ?>">Vezi pagina publică</a></td>
                            <td><?= e($venue['address']) ?></td>
                            <td><?= (int) $venue['capacity'] ?> persoane</td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-small button-secondary" href="venue-edit.php?id=<?= (int) $venue['id'] ?>">Editează</a>
                                    <form method="post" action="venue-delete.php" data-confirm="Sigur dorești să ștergi locația «<?= e($venue['name']) ?>»? Această acțiune nu poate fi anulată.">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $venue['id'] ?>">
                                        <button class="button button-small button-danger-outline" type="submit">Șterge</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
