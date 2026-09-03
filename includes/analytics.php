<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Returnează un identificator stabil și lipsit de date personale pentru pagina curentă.
 */
function analyticsPageName(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'unknown'));
    $directory = basename(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $page = $directory === 'admin' ? 'admin/' . $script : $script;

    return substr(preg_replace('/[^a-zA-Z0-9_\/.\-]/', '', $page) ?: 'unknown', 0, 100);
}

/** Înregistrează o singură accesare pentru răspunsul HTML curent. */
function recordPageVisit(?string $page = null): void
{
    static $recorded = false;

    if ($recorded) {
        return;
    }

    $page = $page ?? analyticsPageName();
    if ($page === '') {
        return;
    }

    $statement = db()->prepare('INSERT INTO page_visits (page) VALUES (:page)');
    $statement->execute(['page' => substr($page, 0, 100)]);
    $recorded = true;
}

/** @return array{total: int, today: int, last_seven_days: int, tracked_pages: int} */
function analyticsSummary(): array
{
    $row = db()->query(
        "SELECT COUNT(*) AS total,
                SUM(DATE(visited_at) = CURRENT_DATE) AS today,
                SUM(visited_at >= CURRENT_DATE - INTERVAL 6 DAY) AS last_seven_days,
                COUNT(DISTINCT page) AS tracked_pages
         FROM page_visits"
    )->fetch();

    return [
        'total' => (int) ($row['total'] ?? 0),
        'today' => (int) ($row['today'] ?? 0),
        'last_seven_days' => (int) ($row['last_seven_days'] ?? 0),
        'tracked_pages' => (int) ($row['tracked_pages'] ?? 0),
    ];
}

/** @return array<int, array{page: string, visits: int}> */
function mostVisitedPages(int $limit = 10): array
{
    $limit = max(1, min($limit, 50));
    $statement = db()->query(
        "SELECT page, COUNT(*) AS visits
         FROM page_visits
         GROUP BY page
         ORDER BY visits DESC, page ASC
         LIMIT {$limit}"
    );

    return array_map(
        static fn (array $row): array => ['page' => (string) $row['page'], 'visits' => (int) $row['visits']],
        $statement->fetchAll()
    );
}

/** @return array<int, array{date: string, visits: int}> */
function visitsByDay(int $days = 7): array
{
    $days = max(1, min($days, 31));
    $start = (new DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
    $statement = db()->prepare(
        'SELECT DATE(visited_at) AS visit_date, COUNT(*) AS visits
         FROM page_visits
         WHERE visited_at >= :start
         GROUP BY DATE(visited_at)
         ORDER BY visit_date'
    );
    $statement->execute(['start' => $start->format('Y-m-d 00:00:00')]);

    $counts = [];
    foreach ($statement->fetchAll() as $row) {
        $counts[(string) $row['visit_date']] = (int) $row['visits'];
    }

    $result = [];
    for ($index = 0; $index < $days; $index++) {
        $date = $start->modify("+{$index} days")->format('Y-m-d');
        $result[] = ['date' => $date, 'visits' => $counts[$date] ?? 0];
    }

    return $result;
}

/** @return array<int, array{page: string, visited_at: string, visited_at_unix: int}> */
function recentPageVisits(int $limit = 20): array
{
    $limit = max(1, min($limit, 100));
    $statement = db()->query(
        "SELECT page, visited_at, UNIX_TIMESTAMP(visited_at) AS visited_at_unix
         FROM page_visits
         ORDER BY visited_at DESC, id DESC
         LIMIT {$limit}"
    );

    return array_map(
        static fn (array $row): array => [
            'page' => (string) $row['page'],
            'visited_at' => (string) $row['visited_at'],
            'visited_at_unix' => (int) $row['visited_at_unix'],
        ],
        $statement->fetchAll()
    );
}

function analyticsPageLabel(string $page): string
{
    $labels = [
        'index.php' => 'Pagina principală',
        'venues.php' => 'Lista locațiilor',
        'venue.php' => 'Detalii locație',
        'login.php' => 'Autentificare',
        'register.php' => 'Înregistrare',
        'verify-email.php' => 'Verificare email',
        'account.php' => 'Contul meu',
        'account-edit.php' => 'Editare cont',
        'verify-email-change.php' => 'Verificare email nou',
        'my-reservations.php' => 'Rezervările mele',
        'reservation-create.php' => 'Solicitare rezervare',
        'contact.php' => 'Contact',
        'admin/index.php' => 'Panou administrativ',
        'admin/reservations.php' => 'Cereri',
        'admin/reservation-create.php' => 'Adăugare cerere',
        'admin/reservation-edit.php' => 'Detalii cerere',
        'admin/venues.php' => 'Administrare locații',
        'admin/venue-create.php' => 'Adăugare locație',
        'admin/venue-edit.php' => 'Editare locație',
        'admin/users.php' => 'Administrare utilizatori',
        'admin/user-create.php' => 'Adăugare utilizator',
        'admin/analytics.php' => 'Statistici',
        'admin/reports.php' => 'Rapoarte și transfer de date',
    ];

    return $labels[$page] ?? $page;
}
