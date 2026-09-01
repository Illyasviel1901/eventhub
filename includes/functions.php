<?php

declare(strict_types=1);

/** Escape text before rendering it in HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return array<int, array{id: int, name: string, description: string, address: string, capacity: int}> */
function getVenues(): array
{
    $statement = db()->query(
        'SELECT id, name, description, address, capacity
         FROM venues
         ORDER BY name'
    );

    return $statement->fetchAll();
}

/** @return array{id: int, name: string, description: string, address: string, capacity: int}|null */
function getVenueById(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT id, name, description, address, capacity
         FROM venues
         WHERE id = :id'
    );
    $statement->execute(['id' => $id]);
    $venue = $statement->fetch();

    return $venue === false ? null : $venue;
}

/** @return array<int, string> */
function getUnavailableDates(int $venueId): array
{
    $statement = db()->prepare(
        "SELECT DISTINCT event_date
         FROM reservations
         WHERE venue_id = :venue_id
           AND status = 'APPROVED'
           AND event_date >= CURRENT_DATE
         ORDER BY event_date"
    );
    $statement->execute(['venue_id' => $venueId]);

    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

function formatDateRo(string $date): string
{
    $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $value === false ? $date : $value->format('d.m.Y');
}
