<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';

requireRole('ADMIN', '../login.php');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reservation = $id === false || $id === null ? null : getReservationById($id);

if ($reservation === null) {
    http_response_code(404);
    exit('Solicitarea nu a fost găsită.');
}

$users = getReservationUsers();
$venues = getVenues();
$input = [
    'venue_id' => (string) $reservation['venue_id'], 'user_id' => (string) $reservation['user_id'],
    'event_date' => $reservation['event_date'], 'event_name' => $reservation['event_name'],
    'attendees_count' => (string) $reservation['attendees_count'], 'details' => $reservation['details'],
    'status' => $reservation['status'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = reservationInputFromPost(true);

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateReservationInput($input, true, $id);

        if ($errors === []) {
            updateReservation($id, (int) $input['user_id'], $input);
            setFlash('success', 'Solicitarea a fost actualizată.');
            redirect('reservations.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Editează solicitarea'; $pageDescription = 'Editează solicitarea de rezervare EventHub.'; $currentPage = 'admin'; $basePath = '../'; $submitLabel = 'Salvează modificările';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Administrare rezervări</p><h1>Editează solicitarea</h1><p>Poți schimba datele, clientul asociat și statusul solicitării.</p></div></section>
<section class="section admin-section"><div class="shell narrow"><div class="form-card">
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="reservation-edit.php?id=<?= (int) $id ?>" novalidate><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><?php require __DIR__ . '/reservation-form-fields.php'; ?></form>
</div></div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
