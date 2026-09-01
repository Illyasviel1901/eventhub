<?php

declare(strict_types=1);

/** @return array{name: string, description: string, address: string, capacity: string} */
function venueInputFromPost(): array
{
    return [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
        'capacity' => trim((string) ($_POST['capacity'] ?? '')),
    ];
}

/** @param array{name: string, description: string, address: string, capacity: string} $input
 *  @return array<int, string>
 */
function validateVenueInput(array $input): array
{
    $errors = [];

    if ($input['name'] === '' || strlen($input['name']) > 100) {
        $errors[] = 'Numele este obligatoriu și poate avea maximum 100 de caractere.';
    }
    if ($input['description'] === '' || strlen($input['description']) > 5000) {
        $errors[] = 'Descrierea este obligatorie și poate avea maximum 5000 de caractere.';
    }
    if ($input['address'] === '' || strlen($input['address']) > 100) {
        $errors[] = 'Adresa este obligatorie și poate avea maximum 100 de caractere.';
    }
    if (filter_var($input['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors[] = 'Capacitatea trebuie să fie un număr întreg pozitiv.';
    }

    return $errors;
}

function venueNameExists(string $name, ?int $exceptId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM venues WHERE name = :name';
    $parameters = ['name' => $name];

    if ($exceptId !== null) {
        $sql .= ' AND id <> :id';
        $parameters['id'] = $exceptId;
    }

    $statement = db()->prepare($sql);
    $statement->execute($parameters);

    return (int) $statement->fetchColumn() > 0;
}

/** @param array{name: string, description: string, address: string, capacity: string} $input */
function createVenue(array $input): int
{
    $statement = db()->prepare(
        'INSERT INTO venues (name, description, address, capacity)
         VALUES (:name, :description, :address, :capacity)'
    );
    $statement->execute([
        'name' => $input['name'],
        'description' => $input['description'],
        'address' => $input['address'],
        'capacity' => (int) $input['capacity'],
    ]);

    return (int) db()->lastInsertId();
}

/** @param array{name: string, description: string, address: string, capacity: string} $input */
function updateVenue(int $id, array $input): void
{
    $statement = db()->prepare(
        'UPDATE venues
         SET name = :name, description = :description, address = :address, capacity = :capacity
         WHERE id = :id'
    );
    $statement->execute([
        'id' => $id,
        'name' => $input['name'],
        'description' => $input['description'],
        'address' => $input['address'],
        'capacity' => (int) $input['capacity'],
    ]);
}

function deleteVenue(int $id): void
{
    $statement = db()->prepare('DELETE FROM venues WHERE id = :id');
    $statement->execute(['id' => $id]);
}
