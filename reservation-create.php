<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation-management.php';
require_once __DIR__ . '/includes/weather.php';

$venueId = filter_input(INPUT_GET, 'venue_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$venue = $venueId === false || $venueId === null ? null : getVenueById($venueId);

if ($venue === null) {
    http_response_code(404);
    exit('Locația nu a fost găsită.');
}

if (!isAuthenticated()) {
    rememberReservationVenue($venueId);
    setFlash('error', 'Autentifică-te pentru a solicita această locație.');
    redirect('login.php');
}

requireRole('USER');
$user = currentUser();
$input = [
    'venue_id' => (string) $venueId,
    'user_id' => '',
    'event_date' => '',
    'event_name' => '',
    'attendees_count' => '',
    'details' => '',
    'status' => 'PENDING',
];
$errors = [];
$unavailableDates = getUnavailableDates((int) $venueId);
$initialWeather = $input['event_date'] !== '' ? getVenueWeatherForDate((string) $venue['address'], $input['event_date']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = reservationInputFromPost(false);
    $input['venue_id'] = (string) $venueId;
    $input['status'] = 'PENDING';

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateReservationInput($input, false);

        if ($errors === []) {
            createReservation((int) $user['id'], $input);
            $mailSent = sendReservationNotification($input, $user['name'], $user['email'], $venue['name']);
            setFlash(
                $mailSent || !mailTransportIsConfigured() ? 'success' : 'info',
                $mailSent
                    ? 'Solicitarea a fost trimisă, iar echipa EventHub a fost notificată prin email.'
                    : 'Solicitarea a fost salvată și este în așteptarea verificării.'
            );
            redirect('my-reservations.php');
        }
    }

    if ($errors !== [] && $input['event_date'] !== '') {
        $initialWeather = getVenueWeatherForDate((string) $venue['address'], $input['event_date']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Solicită ' . $venue['name'];
$pageDescription = 'Trimite o solicitare de rezervare pentru ' . $venue['name'] . '.';
$currentPage = 'venues';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Solicitare de rezervare</p><h1><?= e($venue['name']) ?></h1><p><?= e($venue['address']) ?> · maximum <?= (int) $venue['capacity'] ?> persoane</p></div></section>
<section class="section"><div class="shell narrow"><div class="form-card">
    <h2>Detaliile evenimentului</h2><p class="form-lead">Solicitarea va avea inițial statusul „În așteptare”. Confirmarea este făcută ulterior de echipa EventHub.</p>
    <?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="reservation-create.php?venue_id=<?= (int) $venueId ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="form-group"><label for="event-date">Data evenimentului</label><input id="event-date" name="event_date" type="date" min="<?= e(reservationEarliestDate()) ?>" value="<?= e($input['event_date']) ?>" data-venue-id="<?= (int) $venueId ?>" data-forecast-endpoint="weather-forecast.php" data-unavailable-dates='<?= e(json_encode(array_values($unavailableDates), JSON_UNESCAPED_SLASHES)) ?>' required><small id="date-availability" class="date-feedback" aria-live="polite">Selectează o dată pentru verificarea disponibilității.</small></div>
        <div id="reservation-weather" class="reservation-weather<?= $initialWeather === null ? ' is-hidden' : '' ?>" aria-live="polite">
            <?php if ($initialWeather !== null): ?>
                <div><p class="eyebrow">Prognoză Open-Meteo · <?= e(formatDateRo($initialWeather['date'])) ?></p><h3><?= e($initialWeather['description']) ?> în <?= e($initialWeather['city']) ?></h3><p><?= e(number_format($initialWeather['temperature_min'], 1, ',', '')) ?>°C – <?= e(number_format($initialWeather['temperature_max'], 1, ',', '')) ?>°C · ploaie <?= (int) $initialWeather['precipitation_probability'] ?>% · vânt <?= e(number_format($initialWeather['wind_speed'], 1, ',', '')) ?> km/h</p></div>
            <?php endif; ?>
        </div>
        <div class="form-group"><label for="event-name">Numele sau tipul evenimentului</label><input id="event-name" name="event_name" type="text" minlength="2" maxlength="100" value="<?= e($input['event_name']) ?>" required></div>
        <div class="form-group"><label for="attendees">Număr estimat de participanți</label><input id="attendees" name="attendees_count" type="number" min="1" max="<?= (int) $venue['capacity'] ?>" step="1" value="<?= e($input['attendees_count']) ?>" required></div>
        <div class="form-group"><label for="details">Detalii</label><textarea id="details" name="details" maxlength="5000" rows="7" required><?= e($input['details']) ?></textarea></div>
        <div class="form-actions"><button class="button button-primary" type="submit">Trimite solicitarea</button><a class="button button-secondary" href="venue.php?id=<?= (int) $venueId ?>">Anulează</a></div>
    </form>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
