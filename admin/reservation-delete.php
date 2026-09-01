<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';

requireRole('ADMIN', '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodă HTTP nepermisă.');
}
if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Token CSRF invalid.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reservation = $id === false ? null : getReservationById((int) $id);
if ($reservation === null) {
    http_response_code(404);
    exit('Solicitarea nu a fost găsită.');
}

deleteReservation((int) $id);
setFlash('success', 'Solicitarea „' . $reservation['event_name'] . '” a fost ștearsă.');
redirect('reservations.php');
