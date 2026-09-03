<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Bucharest');

/**
 * Încarcă variabilele dintr-un fișier .env simplu.
 * Variabilele deja furnizate de sistem/Railway au prioritate.
 */
function loadEnvironment(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if (strlen($value) >= 2) {
            $firstCharacter = $value[0];
            $lastCharacter = $value[strlen($value) - 1];

            if (($firstCharacter === '"' && $lastCharacter === '"')
                || ($firstCharacter === "'" && $lastCharacter === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function environment(string $name, ?string $default = null): ?string
{
    $value = getenv($name);

    return $value === false ? $default : $value;
}

/**
 * Returns the visitor's browser time zone saved by JavaScript.
 * Bucharest remains the safe fallback for the first request and invalid cookies.
 */
function visitorTimeZone(): DateTimeZone
{
    $name = (string) ($_COOKIE['eventhub_timezone'] ?? 'Europe/Bucharest');

    if ($name === '' || strlen($name) > 100 || preg_match('/^[A-Za-z0-9_+\-\/]+$/', $name) !== 1) {
        return new DateTimeZone('Europe/Bucharest');
    }

    try {
        return new DateTimeZone($name);
    } catch (Throwable) {
        return new DateTimeZone('Europe/Bucharest');
    }
}
