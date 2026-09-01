<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/mailer.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';
require_once dirname(__DIR__) . '/includes/user-management.php';

requireRole('ADMIN', '../login.php');
$users = getReservationUsers();
$venues = getVenues();
$input = ['venue_id' => '', 'user_id' => '', 'event_date' => '', 'event_name' => '', 'attendees_count' => '', 'details' => '', 'status' => 'PENDING'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = reservationInputFromPost(true);

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateReservationInput($input, true);

        if ($errors === []) {
            createReservation((int) $input['user_id'], $input);
            $client = getUserById((int) $input['user_id']);
            $venue = getVenueById((int) $input['venue_id']);
            if ($client !== null && $venue !== null) {
                sendReservationNotification($input, $client['name'], $client['email'], $venue['name']);
            }
            setFlash('success', 'Solicitarea a fost adăugată în numele clientului selectat.');
            redirect('reservations.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Adaugă solicitare'; $pageDescription = 'Adaugă o solicitare pentru un client EventHub.'; $currentPage = 'admin'; $basePath = '../'; $submitLabel = 'Adaugă solicitarea';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Administrare rezervări</p><h1>Adaugă solicitare</h1><p>Solicitarea trebuie asociată unui client existent cu rolul USER.</p></div></section>
<section class="section admin-section"><div class="shell narrow"><div class="form-card">
<?php if ($users === []): ?><div class="notice notice-error">Nu există clienți USER. Creează mai întâi un cont prin înregistrarea publică.</div><?php else: ?>
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="reservation-create.php" novalidate><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><?php require __DIR__ . '/reservation-form-fields.php'; ?></form>
<?php endif; ?>
</div></div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
