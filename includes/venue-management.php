<?php

declare(strict_types=1);

const VENUE_IMAGE_MAX_FILES = 5;
const VENUE_IMAGE_MAX_BYTES = 5 * 1024 * 1024;
const VENUE_IMAGE_ALLOWED_MIME = [
    'image/jpeg' => true,
    'image/png' => true,
    'image/webp' => true,
];

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

/**
 * Normalizează câmpul HTML `images[]` într-o listă ușor de validat.
 *
 * @return array<int, array{name: string, tmp_name: string, error: int, size: int, mime_type?: string}>
 */
function venueImageUploadsFromRequest(): array
{
    $upload = $_FILES['images'] ?? null;
    if (!is_array($upload) || !isset($upload['name'], $upload['tmp_name'], $upload['error'], $upload['size'])) {
        return [];
    }

    $names = is_array($upload['name']) ? $upload['name'] : [$upload['name']];
    $temporaryNames = is_array($upload['tmp_name']) ? $upload['tmp_name'] : [$upload['tmp_name']];
    $errors = is_array($upload['error']) ? $upload['error'] : [$upload['error']];
    $sizes = is_array($upload['size']) ? $upload['size'] : [$upload['size']];
    $files = [];

    foreach ($names as $index => $name) {
        $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $files[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($temporaryNames[$index] ?? ''),
            'error' => $error,
            'size' => (int) ($sizes[$index] ?? 0),
        ];
    }

    return $files;
}

/**
 * @param array<int, array{name: string, tmp_name: string, error: int, size: int, mime_type?: string}> $files
 * @return array{files: array<int, array{name: string, tmp_name: string, error: int, size: int, mime_type: string}>, errors: array<int, string>}
 */
function validateVenueImageUploads(array $files): array
{
    $validated = [];
    $errors = [];

    if (count($files) > VENUE_IMAGE_MAX_FILES) {
        return ['files' => [], 'errors' => ['Poți încărca maximum 5 imagini pentru o locație.']];
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($files as $index => $file) {
        $position = $index + 1;
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Imaginea {$position} nu a putut fi încărcată.";
            continue;
        }
        if ($file['size'] < 1 || $file['size'] > VENUE_IMAGE_MAX_BYTES) {
            $errors[] = "Imaginea {$position} trebuie să aibă maximum 5 MB.";
            continue;
        }
        if ($file['tmp_name'] === '' || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = "Imaginea {$position} nu este un upload HTTP valid.";
            continue;
        }

        $mimeType = $fileInfo->file($file['tmp_name']);
        $dimensions = @getimagesize($file['tmp_name']);
        if (!is_string($mimeType) || !isset(VENUE_IMAGE_ALLOWED_MIME[$mimeType]) || $dimensions === false) {
            $errors[] = "Imaginea {$position} trebuie să fie JPEG, PNG sau WebP.";
            continue;
        }
        if ((int) $dimensions[0] > 8000 || (int) $dimensions[1] > 8000) {
            $errors[] = "Imaginea {$position} are dimensiuni prea mari.";
            continue;
        }

        $file['mime_type'] = $mimeType;
        $validated[] = $file;
    }

    return ['files' => $validated, 'errors' => $errors];
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

/**
 * @param array{name: string, description: string, address: string, capacity: string} $input
 * @param array<int, array{name: string, tmp_name: string, error: int, size: int, mime_type: string}> $images
 */
function createVenue(array $input, array $images = []): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare(
            'INSERT INTO venues (name, description, address, capacity)
             VALUES (:name, :description, :address, :capacity)'
        );
        $statement->execute([
            'name' => $input['name'],
            'description' => $input['description'],
            'address' => $input['address'],
            'capacity' => (int) $input['capacity'],
        ]);
        $venueId = (int) $pdo->lastInsertId();

        $insertImage = null;
        $updatePath = null;
        if ($images !== []) {
            $insertImage = $pdo->prepare(
                'INSERT INTO venue_images
                    (venue_id, image_path, image_data, mime_type, alt_text, sort_order)
                 VALUES
                    (:venue_id, :image_path, :image_data, :mime_type, :alt_text, :sort_order)'
            );
            $updatePath = $pdo->prepare(
                'UPDATE venue_images SET image_path = :image_path WHERE id = :id'
            );
        }

        foreach ($images as $index => $image) {
            $data = file_get_contents($image['tmp_name']);
            if ($data === false) {
                throw new RuntimeException('Conținutul unei imagini nu a putut fi citit.');
            }

            $temporaryPath = 'upload-pending-' . bin2hex(random_bytes(16));
            $insertImage->bindValue(':venue_id', $venueId, PDO::PARAM_INT);
            $insertImage->bindValue(':image_path', $temporaryPath);
            $insertImage->bindValue(':image_data', $data, PDO::PARAM_LOB);
            $insertImage->bindValue(':mime_type', $image['mime_type']);
            $insertImage->bindValue(':alt_text', $input['name'] . ' — imaginea locației ' . ($index + 1));
            $insertImage->bindValue(':sort_order', $index + 1, PDO::PARAM_INT);
            $insertImage->execute();

            $imageId = (int) $pdo->lastInsertId();
            $updatePath->execute([
                'id' => $imageId,
                'image_path' => 'venue-image.php?id=' . $imageId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
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
