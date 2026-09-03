<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/mailer.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';

requireRole('ADMIN', '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}
if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Token CSRF invalid.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$decision = strtoupper(trim((string) ($_POST['decision'] ?? '')));
if ($id === false) {
    setFlash('error', 'Identificatorul solicitării nu este valid.');
    redirect('reservations.php');
}

$result = decidePendingReservation((int) $id, $decision);
if (!$result['success']) {
    setFlash('error', $result['message']);
    redirect('reservations.php');
}

$mailSent = sendReservationStatusEmail($result['reservation'], $decision);
if ($mailSent) {
    setFlash('success', $decision === 'APPROVED'
        ? 'Solicitarea a fost aprobată, iar utilizatorul a fost notificat prin email.'
        : 'Solicitarea a fost respinsă, iar utilizatorul a fost notificat prin email.');
} else {
    setFlash('info', $decision === 'APPROVED'
        ? 'Solicitarea a fost aprobată. Emailul nu a putut fi trimis.'
        : 'Solicitarea a fost respinsă. Emailul nu a putut fi trimis.');
}

redirect('reservations.php');
