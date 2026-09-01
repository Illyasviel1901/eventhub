<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';

requireRole('ADMIN', '../login.php');
$reservations = getAllReservations();

$pageTitle = 'Cereri';
$pageDescription = 'Gestionarea cererilor de rezervare EventHub.';
$currentPage = 'requests';
$basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell heading-row"><div><p class="eyebrow">Administrare</p><h1>Cereri</h1><p>Verifică detaliile și procesează cererile de rezervare ale clienților.</p></div><a class="button button-primary" href="reservation-create.php">Adaugă solicitare</a></div></section>
<section class="section admin-section"><div class="shell">
<?php if ($reservations === []): ?><div class="empty-state"><h2>Nu există cereri</h2><p>Poți adăuga una în numele unui client existent.</p></div><?php else: ?>
<div class="table-wrap"><table class="admin-table requests-table"><thead><tr><th>Eveniment</th><th>Locație</th><th>Data</th><th>Persoană</th><th>Participanți</th><th>Status</th><th>Acțiuni</th></tr></thead><tbody>
<?php foreach ($reservations as $reservation): ?><tr>
<td><strong><?= e($reservation['event_name']) ?></strong></td>
<td><?= e($reservation['venue_name']) ?></td>
<td><time datetime="<?= e($reservation['event_date']) ?>"><?= e(formatDateRo($reservation['event_date'])) ?></time></td>
<td><?= e($reservation['user_name']) ?><span class="table-sub-link"><?= e($reservation['user_email']) ?></span></td>
<td><?= (int) $reservation['attendees_count'] ?></td>
<td><span class="status-badge status-<?= strtolower(e($reservation['status'])) ?>"><?= e(reservationStatusLabel($reservation['status'])) ?></span></td>
<td><div class="table-actions request-actions">
<a class="button button-small button-secondary" href="reservation-edit.php?id=<?= (int) $reservation['id'] ?>">Vezi detalii</a>
<?php if ($reservation['status'] === 'PENDING'): ?>
<form method="post" action="reservation-status.php" data-confirm="Aprobi această cerere?"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><input type="hidden" name="decision" value="APPROVED"><button class="button button-small button-approve" type="submit">Aprobă</button></form>
<form method="post" action="reservation-status.php" data-confirm="Respingi această cerere?"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><input type="hidden" name="decision" value="REJECTED"><button class="button button-small button-danger-outline" type="submit">Respinge</button></form>
<?php endif; ?>
<form method="post" action="reservation-delete.php" data-confirm="Sigur dorești să ștergi definitiv cererea «<?= e($reservation['event_name']) ?>»? Această acțiune nu poate fi anulată."><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><button class="button button-small button-danger-outline" type="submit">Șterge</button></form>
</div></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
