<?php

declare(strict_types=1);

/** Escape text before rendering it in HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return array<int, array{id: int, name: string, description: string, address: string, capacity: int, main_image?: string|null, main_image_alt?: string|null}> */
function getVenues(): array
{
    $statement = db()->query(
        'SELECT v.id, v.name, v.description, v.address, v.capacity,
                (SELECT vi.image_path FROM venue_images vi WHERE vi.venue_id = v.id ORDER BY vi.sort_order, vi.id LIMIT 1) AS main_image,
                (SELECT vi.alt_text FROM venue_images vi WHERE vi.venue_id = v.id ORDER BY vi.sort_order, vi.id LIMIT 1) AS main_image_alt
         FROM venues v
         ORDER BY v.name'
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

/** @return array<int, array{id: int, image_path: string, alt_text: string, sort_order: int}> */
function getVenueImages(int $venueId): array
{
    $statement = db()->prepare(
        'SELECT id, image_path, alt_text, sort_order
         FROM venue_images
         WHERE venue_id = :venue_id
         ORDER BY sort_order, id'
    );
    $statement->execute(['venue_id' => $venueId]);

    return $statement->fetchAll();
}

function venueImageUrl(string $path): string
{
    if (!str_starts_with($path, 'assets/')) {
        return $path;
    }

    $absolutePath = dirname(__DIR__) . '/' . $path;
    if (!is_file($absolutePath)) {
        return $path;
    }

    return $path . '?v=' . (string) filemtime($absolutePath);
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
