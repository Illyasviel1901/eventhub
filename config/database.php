<?php

declare(strict_types=1);

require_once __DIR__ . '/environment.php';

loadEnvironment(dirname(__DIR__) . '/.env');

/**
 * Creează o singură conexiune PDO reutilizată pe durata cererii PHP.
 */
function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $configuration = databaseConfiguration();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $configuration['host'],
        $configuration['port'],
        $configuration['name']
    );

    $connection = new PDO(
        $dsn,
        $configuration['user'],
        $configuration['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // MySQL păstrează TIMESTAMP în UTC și îl convertește la fusul sesiunii.
    // Offsetul este recalculat la fiecare proces PHP, inclusiv la schimbarea orei de vară.
    $bucharestOffset = (new DateTimeImmutable('now', new DateTimeZone('Europe/Bucharest')))->format('P');
    $connection->exec("SET time_zone = " . $connection->quote($bucharestOffset));

    return $connection;
}

/**
 * Acceptă variabilele DB_* local și MYSQL_URL pe Railway.
 * Variabilele DB_* au prioritate dacă sunt definite.
 *
 * @return array{host: string, port: int, name: string, user: string, password: string}
 */
function databaseConfiguration(): array
{
    $urlConfiguration = [];
    $mysqlUrl = environment('MYSQL_URL');

    if ($mysqlUrl !== null && $mysqlUrl !== '') {
        $parts = parse_url($mysqlUrl);

        if ($parts === false) {
            throw new RuntimeException('Variabila MYSQL_URL nu are un format valid.');
        }

        $urlConfiguration = [
            'host' => $parts['host'] ?? null,
            'port' => isset($parts['port']) ? (int) $parts['port'] : null,
            'name' => isset($parts['path']) ? ltrim($parts['path'], '/') : null,
            'user' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        ];
    }

    $configuration = [
        'host' => environment('DB_HOST', $urlConfiguration['host'] ?? '127.0.0.1'),
        'port' => (int) environment('DB_PORT', (string) ($urlConfiguration['port'] ?? 3306)),
        'name' => environment('DB_NAME', $urlConfiguration['name'] ?? ''),
        'user' => environment('DB_USER', $urlConfiguration['user'] ?? ''),
        'password' => environment('DB_PASSWORD', $urlConfiguration['password'] ?? ''),
    ];

    if ($configuration['name'] === '' || $configuration['user'] === '') {
        throw new RuntimeException('Configurația bazei de date este incompletă.');
    }

    return $configuration;
}
