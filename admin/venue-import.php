<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/venue-management.php';
require_once dirname(__DIR__) . '/includes/xlsx.php';

requireRole('ADMIN', '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}
if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Token CSRF invalid.');
}

$file = $_FILES['venues_file'] ?? null;
if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    setFlash('error', 'Selectează un fișier XLSX valid.');
    redirect('reports.php');
}
if ((int) $file['size'] > 2 * 1024 * 1024 || strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
    setFlash('error', 'Fișierul trebuie să fie XLSX și să aibă maximum 2 MB.');
    redirect('reports.php');
}
if (!is_uploaded_file((string) $file['tmp_name'])) {
    setFlash('error', 'Fișierul încărcat nu a putut fi validat.');
    redirect('reports.php');
}

try {
    $rows = readXlsxFile((string) $file['tmp_name']);
    if ($rows === []) {
        throw new RuntimeException('Fișierul nu conține date.');
    }

    $normalize = static fn (string $value): string => strtolower(strtr(trim($value), ['ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't']));
    $headers = array_map($normalize, $rows[0]);
    $required = ['nume', 'descriere', 'adresa', 'capacitate'];
    $indexes = [];
    foreach ($required as $header) {
        $index = array_search($header, $headers, true);
        if ($index === false) {
            throw new RuntimeException('Antetele obligatorii sunt: Nume, Descriere, Adresa, Capacitate.');
        }
        $indexes[$header] = $index;
    }

    $validated = [];
    $names = [];
    foreach (array_slice($rows, 1) as $rowNumber => $row) {
        $input = [
            'name' => trim((string) ($row[$indexes['nume']] ?? '')),
            'description' => trim((string) ($row[$indexes['descriere']] ?? '')),
            'address' => trim((string) ($row[$indexes['adresa']] ?? '')),
            'capacity' => trim((string) ($row[$indexes['capacitate']] ?? '')),
        ];
        if (implode('', $input) === '') {
            continue;
        }
        $errors = validateVenueInput($input);
        if ($errors !== []) {
            throw new RuntimeException('Rândul ' . ($rowNumber + 2) . ': ' . implode(' ', $errors));
        }
        $key = strtolower($input['name']);
        if (isset($names[$key])) {
            throw new RuntimeException('Locația „' . $input['name'] . '” apare de mai multe ori în fișier.');
        }
        $names[$key] = true;
        $validated[] = $input;
    }
    if ($validated === []) {
        throw new RuntimeException('Fișierul nu conține locații de importat.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'INSERT INTO venues (name, description, address, capacity) VALUES (:name, :description, :address, :capacity)
         ON DUPLICATE KEY UPDATE description = VALUES(description), address = VALUES(address), capacity = VALUES(capacity)'
    );
    foreach ($validated as $input) {
        $statement->execute([
            'name' => $input['name'], 'description' => $input['description'],
            'address' => $input['address'], 'capacity' => (int) $input['capacity'],
        ]);
    }
    $pdo->commit();
    setFlash('success', count($validated) . ' locații au fost importate sau actualizate.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('error', $exception->getMessage());
}

redirect('reports.php');
