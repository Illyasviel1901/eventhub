<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/venue-management.php';

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
$venue = $id === false ? null : getVenueById((int) $id);

if ($venue === null) {
    http_response_code(404);
    exit('Locația nu a fost găsită.');
}

try {
    deleteVenue((int) $id);
    setFlash('success', 'Locația „' . $venue['name'] . '” a fost ștearsă.');
} catch (PDOException $exception) {
    if ($exception->getCode() !== '23000') {
        throw $exception;
    }

    setFlash('error', 'Locația nu poate fi ștearsă deoarece are rezervări asociate.');
}

redirect('venues.php');
