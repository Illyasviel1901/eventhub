<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation-management.php';
require_once __DIR__ . '/includes/weather.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodă HTTP nepermisă.']);
    exit;
}

if (!hasRole('USER')) {
    http_response_code(isAuthenticated() ? 403 : 401);
    echo json_encode(['error' => 'Autentificarea ca utilizator este necesară.']);
    exit;
}

$venueId = filter_input(INPUT_GET, 'venue_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$date = trim((string) ($_GET['date'] ?? ''));
$selectedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

if ($venueId === false || $venueId === null || $selectedDate === false || $selectedDate->format('Y-m-d') !== $date) {
    http_response_code(422);
    echo json_encode(['error' => 'Locația sau data nu este validă.']);
    exit;
}

if ($selectedDate < new DateTimeImmutable('tomorrow')) {
    http_response_code(422);
    echo json_encode(['error' => 'Data evenimentului trebuie să fie cel puțin ziua următoare.']);
    exit;
}

$venue = getVenueById($venueId);
if ($venue === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Locația nu a fost găsită.']);
    exit;
}

$available = !reservationDateIsBlocked($venueId, $date);
$result = [
    'date' => $date,
    'available' => $available,
    'weather_eligible' => dateIsInWeatherWindow($date),
    'weather' => null,
];

if ($result['weather_eligible']) {
    // Cache-ul de 30 minute rezolvă cererile repetate. Pauza minimă limitează
    // doar apelurile externe succesive, fără a afecta răspunsurile deja memorate.
    $lastRequest = (float) ($_SESSION['weather_last_external_request'] ?? 0.0);
    $cacheKey = 'weather_forecast_' . hash('sha256', strtolower(venueCityFromAddress($venue['address'])) . '|' . $date);
    $hasFreshCache = isset($_SESSION[$cacheKey]['expires_at']) && (int) $_SESSION[$cacheKey]['expires_at'] > time();
    if (!$hasFreshCache && microtime(true) - $lastRequest < 0.25) {
        usleep((int) ((0.25 - (microtime(true) - $lastRequest)) * 1000000));
    }
    $result['weather'] = getVenueWeatherForDate((string) $venue['address'], $date);
    if (!$hasFreshCache) {
        $_SESSION['weather_last_external_request'] = microtime(true);
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
