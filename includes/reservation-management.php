<?php

declare(strict_types=1);

const RESERVATION_STATUSES = ['PENDING', 'APPROVED', 'REJECTED'];

/** @return array<int, array{id: int, name: string, email: string}> */
function getReservationUsers(): array
{
    return db()->query("SELECT id, name, email FROM users WHERE role = 'USER' ORDER BY name, email")->fetchAll();
}

/** @return array<int, array<string, mixed>> */
function getReservationsForUser(int $userId): array
{
    $statement = db()->prepare(
        'SELECT r.id, r.event_date, r.event_name, r.attendees_count, r.details, r.status,
                v.id AS venue_id, v.name AS venue_name, v.address AS venue_address
         FROM reservations r
         INNER JOIN venues v ON v.id = r.venue_id
         WHERE r.user_id = :user_id
         ORDER BY r.event_date DESC, r.id DESC'
    );
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

/** @return array<int, array<string, mixed>> */
function getAllReservations(): array
{
    return db()->query(
        "SELECT r.id, r.event_date, r.event_name, r.attendees_count, r.status,
                u.name AS user_name, u.email AS user_email,
                v.name AS venue_name
         FROM reservations r
         INNER JOIN users u ON u.id = r.user_id
         INNER JOIN venues v ON v.id = r.venue_id
         ORDER BY FIELD(r.status, 'PENDING', 'APPROVED', 'REJECTED'), r.event_date, r.id DESC"
    )->fetchAll();
}

function pendingReservationCount(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM reservations WHERE status = 'PENDING'")->fetchColumn();
}

/** @return array<string, mixed>|null */
function getReservationById(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT r.id, r.user_id, r.venue_id, r.event_date, r.event_name,
                r.attendees_count, r.details, r.status,
                u.name AS user_name, u.email AS user_email, u.role AS user_role,
                v.name AS venue_name, v.capacity AS venue_capacity
         FROM reservations r
         INNER JOIN users u ON u.id = r.user_id
         INNER JOIN venues v ON v.id = r.venue_id
         WHERE r.id = :id'
    );
    $statement->execute(['id' => $id]);
    $reservation = $statement->fetch();

    return $reservation === false ? null : $reservation;
}

function reservationDateIsBlocked(int $venueId, string $date, ?int $exceptReservationId = null): bool
{
    $sql = "SELECT COUNT(*) FROM reservations
            WHERE venue_id = :venue_id AND event_date = :event_date AND status = 'APPROVED'";
    $parameters = ['venue_id' => $venueId, 'event_date' => $date];

    if ($exceptReservationId !== null) {
        $sql .= ' AND id <> :id';
        $parameters['id'] = $exceptReservationId;
    }

    $statement = db()->prepare($sql);
    $statement->execute($parameters);

    return (int) $statement->fetchColumn() > 0;
}

/** @return array{venue_id: string, user_id: string, event_date: string, event_name: string, attendees_count: string, details: string, status: string} */
function reservationInputFromPost(bool $admin): array
{
    return [
        'venue_id' => trim((string) ($_POST['venue_id'] ?? '')),
        'user_id' => $admin ? trim((string) ($_POST['user_id'] ?? '')) : '',
        'event_date' => trim((string) ($_POST['event_date'] ?? '')),
        'event_name' => trim((string) ($_POST['event_name'] ?? '')),
        'attendees_count' => trim((string) ($_POST['attendees_count'] ?? '')),
        'details' => trim((string) ($_POST['details'] ?? '')),
        'status' => $admin ? trim((string) ($_POST['status'] ?? '')) : 'PENDING',
    ];
}

/** @param array<string, string> $input
 *  @return array<int, string>
 */
function validateReservationInput(array $input, bool $admin, ?int $exceptReservationId = null): array
{
    $errors = [];
    $venueId = filter_var($input['venue_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $venue = $venueId === false ? null : getVenueById((int) $venueId);

    if ($venue === null) {
        $errors[] = 'Selectează o locație validă.';
    }

    if ($admin) {
        $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false) {
            $errors[] = 'Selectează un client valid.';
        } else {
            $statement = db()->prepare("SELECT COUNT(*) FROM users WHERE id = :id AND role = 'USER'");
            $statement->execute(['id' => $userId]);
            if ((int) $statement->fetchColumn() !== 1) {
                $errors[] = 'Solicitarea poate fi asociată numai unui cont USER existent.';
            }
        }
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input['event_date']);
    if ($date === false || $date->format('Y-m-d') !== $input['event_date']) {
        $errors[] = 'Selectează o dată validă.';
    } elseif ($date < new DateTimeImmutable('today')) {
        $errors[] = 'Data evenimentului nu poate fi în trecut.';
    }

    if (strlen($input['event_name']) < 2 || strlen($input['event_name']) > 100) {
        $errors[] = 'Numele evenimentului trebuie să conțină între 2 și 100 de caractere.';
    }

    $attendees = filter_var($input['attendees_count'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($attendees === false) {
        $errors[] = 'Numărul de participanți trebuie să fie un număr întreg pozitiv.';
    } elseif ($venue !== null && (int) $attendees > (int) $venue['capacity']) {
        $errors[] = 'Numărul de participanți depășește capacitatea locației (' . $venue['capacity'] . ').';
    }

    if ($input['details'] === '') {
        $errors[] = 'Detaliile sunt obligatorii';
    } elseif (strlen($input['details']) > 5000) {
        $errors[] = 'Detaliile pot avea maximum 5000 de caractere.';
    }

    if (!in_array($input['status'], RESERVATION_STATUSES, true)) {
        $errors[] = 'Statusul solicitării nu este valid.';
    }

    if ($venue !== null && $date !== false
        && ($input['status'] === 'APPROVED' || !$admin)
        && reservationDateIsBlocked((int) $venueId, $input['event_date'], $exceptReservationId)) {
        $errors[] = 'Locația este deja rezervată în data selectată.';
    }

    return $errors;
}

/** @param array<string, string> $input */
function createReservation(int $userId, array $input): int
{
    $statement = db()->prepare(
        'INSERT INTO reservations
            (user_id, venue_id, event_date, event_name, attendees_count, details, status)
         VALUES
            (:user_id, :venue_id, :event_date, :event_name, :attendees_count, :details, :status)'
    );
    $statement->execute([
        'user_id' => $userId,
        'venue_id' => (int) $input['venue_id'],
        'event_date' => $input['event_date'],
        'event_name' => $input['event_name'],
        'attendees_count' => (int) $input['attendees_count'],
        'details' => $input['details'],
        'status' => $input['status'],
    ]);

    return (int) db()->lastInsertId();
}

/** @param array<string, string> $input */
function updateReservation(int $id, int $userId, array $input): void
{
    $statement = db()->prepare(
        'UPDATE reservations SET user_id = :user_id, venue_id = :venue_id,
            event_date = :event_date, event_name = :event_name,
            attendees_count = :attendees_count, details = :details, status = :status
         WHERE id = :id'
    );
    $statement->execute([
        'id' => $id,
        'user_id' => $userId,
        'venue_id' => (int) $input['venue_id'],
        'event_date' => $input['event_date'],
        'event_name' => $input['event_name'],
        'attendees_count' => (int) $input['attendees_count'],
        'details' => $input['details'],
        'status' => $input['status'],
    ]);
}

function deleteReservation(int $id): void
{
    $statement = db()->prepare('DELETE FROM reservations WHERE id = :id');
    $statement->execute(['id' => $id]);
}

/** @return array{success: bool, message: string, reservation?: array<string, mixed>} */
function decidePendingReservation(int $id, string $decision): array
{
    if (!in_array($decision, ['APPROVED', 'REJECTED'], true)) {
        return ['success' => false, 'message' => 'Decizia nu este validă.'];
    }

    $pdo = db();
    $reservation = getReservationById($id);
    if ($reservation === null) {
        return ['success' => false, 'message' => 'Solicitarea nu a fost găsită.'];
    }
    if ($reservation['status'] !== 'PENDING') {
        return ['success' => false, 'message' => 'Numai cererile în așteptare pot fi aprobate sau respinse.'];
    }

    $lockName = 'eventhub_reservation_' . (int) $reservation['venue_id'] . '_' . $reservation['event_date'];
    $lockStatement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 5)');
    $lockStatement->execute(['lock_name' => $lockName]);
    if ((int) $lockStatement->fetchColumn() !== 1) {
        return ['success' => false, 'message' => 'Cererea nu poate fi procesată momentan. Încearcă din nou.'];
    }

    try {
        $pdo->beginTransaction();
        $currentStatement = $pdo->prepare('SELECT status FROM reservations WHERE id = :id FOR UPDATE');
        $currentStatement->execute(['id' => $id]);
        if ($currentStatement->fetchColumn() !== 'PENDING') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Cererea a fost deja procesată.'];
        }

        if ($decision === 'APPROVED' && reservationDateIsBlocked(
            (int) $reservation['venue_id'],
            (string) $reservation['event_date'],
            $id
        )) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Locația este deja rezervată în data selectată.'];
        }

        $update = $pdo->prepare('UPDATE reservations SET status = :status WHERE id = :id AND status = \'PENDING\'');
        $update->execute(['status' => $decision, 'id' => $id]);
        $pdo->commit();
        $reservation['status'] = $decision;

        return ['success' => true, 'message' => 'Statusul cererii a fost actualizat.', 'reservation' => $reservation];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $release->execute(['lock_name' => $lockName]);
    }
}

function reservationStatusLabel(string $status): string
{
    return ['PENDING' => 'În așteptare', 'APPROVED' => 'Aprobată', 'REJECTED' => 'Respinsă'][$status] ?? $status;
}
