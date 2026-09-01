<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation-management.php';
require_once __DIR__ . '/includes/weather.php';

requireRole('USER');
$user = currentUser();
$reservations = getReservationsForUser((int) $user['id']);
$weatherByReservation = [];
foreach ($reservations as $reservation) {
    if ($reservation['status'] !== 'REJECTED'
        && dateIsInWeatherWindow((string) $reservation['event_date'])) {
        $weatherByReservation[(int) $reservation['id']] = getVenueWeatherForDate(
            (string) $reservation['venue_address'],
            (string) $reservation['event_date']
        );
    }
}

$pageTitle = 'Rezervările mele';
$pageDescription = 'Solicitările tale de rezervare EventHub.';
$currentPage = 'my-reservations';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="shell narrow"><p class="eyebrow">Cont client</p><h1>Rezervările mele</h1><p>Aici urmărești solicitările trimise și decizia echipei EventHub.</p></div></section>
<section class="section"><div class="shell">
    <?php if ($reservations === []): ?>
        <div class="empty-state"><h2>Nu ai nici o solicitare activă</h2><p>Alege o locație și completează detaliile evenimentului tău.</p><a class="button button-primary" href="venues.php">Explorează locațiile</a></div>
    <?php else: ?>
        <div class="reservation-grid">
        <?php foreach ($reservations as $reservation): ?>
            <article class="reservation-card">
                <div class="reservation-card-heading"><div><p class="eyebrow"><?= e(formatDateRo($reservation['event_date'])) ?></p><h2><?= e($reservation['event_name']) ?></h2></div><span class="status-badge status-<?= strtolower(e($reservation['status'])) ?>"><?= e(reservationStatusLabel($reservation['status'])) ?></span></div>
                <dl class="reservation-facts"><div><dt>Locație</dt><dd><a href="venue.php?id=<?= (int) $reservation['venue_id'] ?>"><?= e($reservation['venue_name']) ?></a></dd></div><div><dt>Participanți</dt><dd><?= (int) $reservation['attendees_count'] ?></dd></div><div><dt>Adresă</dt><dd><?= e($reservation['venue_address']) ?></dd></div></dl>
                <div class="reservation-details"><strong>Detalii</strong><p><?= nl2br(e($reservation['details'])) ?></p></div>
                <?php $forecast = $weatherByReservation[(int) $reservation['id']] ?? null; ?>
                <?php if ($reservation['status'] !== 'REJECTED' && $forecast !== null): ?>
                    <div class="reservation-weather reservation-weather-inline"><div><p class="eyebrow">Prognoză Open-Meteo</p><h3><?= e($forecast['description']) ?> în <?= e($forecast['city']) ?></h3><p><?= e(number_format($forecast['temperature_min'], 1, ',', '')) ?>°C – <?= e(number_format($forecast['temperature_max'], 1, ',', '')) ?>°C · ploaie <?= (int) $forecast['precipitation_probability'] ?>% · vânt <?= e(number_format($forecast['wind_speed'], 1, ',', '')) ?> km/h</p></div></div>
                <?php endif; ?>
                <?php if ($reservation['status'] === 'PENDING'): ?>
                    <form class="reservation-cancel-form" method="post" action="reservation-cancel.php" data-confirm="Sigur dorești să anulezi această solicitare? Acțiunea nu poate fi anulată.">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>">
                        <button class="button button-small button-danger-outline" type="submit">Anulează solicitarea</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
