<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/xlsx.php';

requireRole('ADMIN', '../login.php');

$rows = [['ID', 'Nume', 'Descriere', 'Adresa', 'Capacitate']];
foreach (db()->query('SELECT id, name, description, address, capacity FROM venues ORDER BY name')->fetchAll() as $venue) {
    $rows[] = [(int) $venue['id'], $venue['name'], $venue['description'], $venue['address'], (int) $venue['capacity']];
}

sendXlsxDownload(createXlsxFile($rows, 'Locatii'), 'eventhub-locatii-' . date('Y-m-d') . '.xlsx');
