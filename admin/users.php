<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/user-management.php';

requireRole('ADMIN', '../login.php');
$users = getClientUsers();

$pageTitle = 'Administrare utilizatori'; $pageDescription = 'Administrarea clienților EventHub.'; $currentPage = 'admin'; $basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell heading-row"><div><p class="eyebrow">Conturi USER</p><h1>Administrare utilizatori</h1><p>Adaugă sau șterge conturile clienților. Conturile administrative nu sunt gestionate aici.</p></div><a class="button button-primary" href="user-create.php">Adaugă utilizator</a></div></section>
<section class="section admin-section"><div class="shell">
<?php if ($users === []): ?><div class="empty-state"><h2>Nu există clienți</h2><p>Poți adăuga primul cont cu rolul USER.</p></div><?php else: ?>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Nume</th><th>Email</th><th>Rol</th><th>Acțiuni</th></tr></thead><tbody>
<?php foreach ($users as $client): ?><tr><td><strong><?= e($client['name']) ?></strong></td><td><?= e($client['email']) ?></td><td><span class="status-badge status-approved">USER</span></td><td><form method="post" action="user-delete.php" data-confirm="Sigur dorești să ștergi utilizatorul «<?= e($client['name']) ?>»? Solicitările sale vor fi șterse definitiv."><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $client['id'] ?>"><button class="button button-small button-danger-outline" type="submit">Șterge</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
