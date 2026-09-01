<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/xlsx.php';
require_once dirname(__DIR__) . '/includes/reservation-management.php';

requireRole('ADMIN', '../login.php');

$rows = [['ID', 'Eveniment', 'Locatie', 'Data', 'Client', 'Email', 'Participanti', 'Status', 'Detalii']];
$statement = db()->query(
    'SELECT r.id, r.event_name, v.name AS venue_name, r.event_date, u.name AS user_name,
            u.email AS user_email, r.attendees_count, r.status, r.details
     FROM reservations r
     INNER JOIN venues v ON v.id = r.venue_id
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY r.event_date, r.id'
);
foreach ($statement->fetchAll() as $reservation) {
    $rows[] = [
        (int) $reservation['id'], $reservation['event_name'], $reservation['venue_name'],
        $reservation['event_date'], $reservation['user_name'], $reservation['user_email'],
        (int) $reservation['attendees_count'], reservationStatusLabel($reservation['status']), $reservation['details'],
    ];
}

sendXlsxDownload(createXlsxFile($rows, 'Cereri'), 'eventhub-cereri-' . date('Y-m-d') . '.xlsx');
