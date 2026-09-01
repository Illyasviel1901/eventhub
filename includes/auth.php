<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/session.php';

/** @return array{id: int, name: string, email: string, role: string}|null */
function currentUser(): ?array
{
    $user = $_SESSION['user'] ?? null;

    if (!is_array($user)
        || !isset($user['id'], $user['name'], $user['email'], $user['role'])) {
        return null;
    }

    return $user;
}

function isAuthenticated(): bool
{
    return currentUser() !== null;
}

/** @param array{id: int|string, name: string, email: string, role: string} $user */
function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parameters['path'],
            'domain' => $parameters['domain'],
            'secure' => $parameters['secure'],
            'httponly' => $parameters['httponly'],
            'samesite' => $parameters['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Protecție anti-automatizare simplă pentru formularele fără privilegii.
 * Câmpul honeypot este invizibil pentru utilizatori, dar este completat frecvent de boți.
 */
function isAutomatedPublicSubmission(mixed $honeypot): bool
{
    return !is_string($honeypot) || trim($honeypot) !== '';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** @return array{type: string, message: string}|null */
function pullFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) && isset($flash['type'], $flash['message']) ? $flash : null;
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function requireAuthentication(string $loginPath = 'login.php'): void
{
    if (!isAuthenticated()) {
        setFlash('error', 'Autentifică-te pentru a accesa această pagină.');
        redirect($loginPath);
    }
}

function hasRole(string $role): bool
{
    $user = currentUser();

    return $user !== null && $user['role'] === $role;
}

function requireRole(string $role, string $loginPath = 'login.php'): void
{
    requireAuthentication($loginPath);

    if (!hasRole($role)) {
        http_response_code(403);
        exit('Acces interzis. Nu ai permisiunea necesară pentru această pagină.');
    }
}

function rememberReservationVenue(int $venueId): void
{
    $_SESSION['pending_reservation_venue_id'] = $venueId;
}

function destinationAfterAuthentication(string $role): string
{
    $venueId = filter_var(
        $_SESSION['pending_reservation_venue_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    unset($_SESSION['pending_reservation_venue_id']);

    if ($role === 'USER' && $venueId !== false && $venueId !== null) {
        return 'reservation-create.php?venue_id=' . $venueId;
    }

    return $role === 'ADMIN' ? 'admin/index.php' : 'account.php';
}
