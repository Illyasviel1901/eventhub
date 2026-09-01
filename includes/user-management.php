<?php

declare(strict_types=1);

/** @return array<int, array{id: int, name: string, email: string}> */
function getClientUsers(): array
{
    return db()->query("SELECT id, name, email FROM users WHERE role = 'USER' ORDER BY name, email")->fetchAll();
}

/** @return array{id: int, name: string, email: string, role: string}|null */
function getUserById(int $id): ?array
{
    $statement = db()->prepare('SELECT id, name, email, role FROM users WHERE id = :id');
    $statement->execute(['id' => $id]);
    $user = $statement->fetch();

    return $user === false ? null : $user;
}

function userEmailExists(string $email, ?int $exceptId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
    $parameters = ['email' => $email];

    if ($exceptId !== null) {
        $sql .= ' AND id <> :id';
        $parameters['id'] = $exceptId;
    }

    $statement = db()->prepare($sql);
    $statement->execute($parameters);

    return (int) $statement->fetchColumn() > 0;
}

/** @return array<int, string> */
function validateUserIdentity(string $name, string $email): array
{
    $errors = [];

    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Numele trebuie să conțină între 2 și 100 de caractere.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Introdu o adresă de email validă, de maximum 100 de caractere.';
    }

    return $errors;
}

function updateUserIdentity(int $id, string $name, string $email): void
{
    $statement = db()->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id AND role = \'USER\'');
    $statement->execute(['id' => $id, 'name' => $name, 'email' => $email]);
}

function createClientUser(string $name, string $email, string $password): int
{
    $statement = db()->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'USER')"
    );
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) db()->lastInsertId();
}

function deleteClientUser(int $id): bool
{
    $statement = db()->prepare("DELETE FROM users WHERE id = :id AND role = 'USER'");
    $statement->execute(['id' => $id]);

    return $statement->rowCount() === 1;
}
