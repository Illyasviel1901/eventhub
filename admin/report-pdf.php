<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';
require_once dirname(__DIR__) . '/includes/pdf.php';

requireRole('ADMIN', '../login.php');

$pdo = db();
$statistics = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'PENDING') AS pending,
            SUM(status = 'APPROVED') AS approved,
            SUM(status = 'REJECTED') AS rejected
     FROM reservations"
)->fetch();
$venueCount = (int) $pdo->query('SELECT COUNT(*) FROM venues')->fetchColumn();
$userCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'USER'")->fetchColumn();
$reservations = $pdo->query(
    'SELECT r.event_name, r.event_date, r.attendees_count, r.status,
            v.name AS venue_name, u.name AS user_name
     FROM reservations r
     INNER JOIN venues v ON v.id = r.venue_id
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY r.event_date, r.id'
)->fetchAll();
$popularVenues = $pdo->query(
    'SELECT v.name, COUNT(r.id) AS requests
     FROM venues v LEFT JOIN reservations r ON r.venue_id = v.id
     GROUP BY v.id, v.name ORDER BY requests DESC, v.name LIMIT 5'
)->fetchAll();

$reportTimeZone = visitorTimeZone();
$generatedAt = (new DateTimeImmutable('now'))->setTimezone($reportTimeZone);

$lines = [
    'EVENTHUB - RAPORT GENERAL',
    'Generat la: ' . $generatedAt->format('d.m.Y H:i:s') . ' (' . $reportTimeZone->getName() . ')',
    str_repeat('-', 82),
    'SINTEZA',
    'Locatii: ' . $venueCount,
    'Clienti: ' . $userCount,
    'Cereri totale: ' . (int) ($statistics['total'] ?? 0),
    'In asteptare: ' . (int) ($statistics['pending'] ?? 0) . ' | Aprobate: ' . (int) ($statistics['approved'] ?? 0) . ' | Respinse: ' . (int) ($statistics['rejected'] ?? 0),
    '',
    'CELE MAI SOLICITATE LOCATII',
];
foreach ($popularVenues as $index => $venue) {
    $lines[] = ($index + 1) . '. ' . $venue['name'] . ' - ' . (int) $venue['requests'] . ' cereri';
}
$lines[] = '';
$lines[] = 'CERERI';
if ($reservations === []) {
    $lines[] = 'Nu exista cereri inregistrate.';
} else {
    foreach ($reservations as $reservation) {
        $lines[] = sprintf(
            '%s | %s | %s | %s | %d participanti | %s',
            date('d.m.Y', strtotime($reservation['event_date'])),
            $reservation['event_name'],
            $reservation['venue_name'],
            $reservation['user_name'],
            (int) $reservation['attendees_count'],
            reservationStatusLabel($reservation['status'])
        );
    }
}

sendPdfDownload(createSimplePdf($lines), 'eventhub-raport-' . $generatedAt->format('Y-m-d') . '.pdf');
