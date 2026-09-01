<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/mailer.php';

const EMAIL_VERIFICATION_TTL = 600;
const EMAIL_VERIFICATION_MAX_ATTEMPTS = 5;

function createEmailVerificationCode(): string
{
    return (string) random_int(100000, 999999);
}

function startEmailVerification(string $purpose, string $email, array $payload): bool
{
    $code = createEmailVerificationCode();
    $sent = sendRecipientEmail(
        $email,
        (string) ($payload['name'] ?? 'Client EventHub'),
        'Cod de verificare EventHub',
        "Codul tău de verificare EventHub este: {$code}\n\nCodul este valabil 10 minute. Dacă nu ai solicitat această operațiune, ignoră mesajul."
    );

    if (!$sent) {
        return false;
    }

    $_SESSION['email_verification'] = [
        'purpose' => $purpose,
        'email' => $email,
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => time() + EMAIL_VERIFICATION_TTL,
        'attempts' => 0,
        'payload' => $payload,
    ];

    return true;
}

function pendingEmailVerification(?string $purpose = null): ?array
{
    $verification = $_SESSION['email_verification'] ?? null;
    if (!is_array($verification)
        || !isset($verification['purpose'], $verification['email'], $verification['code_hash'], $verification['expires_at'], $verification['attempts'], $verification['payload'])) {
        return null;
    }

    if ((int) $verification['expires_at'] < time()) {
        unset($_SESSION['email_verification']);
        return null;
    }

    if ($purpose !== null && $verification['purpose'] !== $purpose) {
        return null;
    }

    return $verification;
}

function verifyPendingEmailCode(string $purpose, string $code): bool
{
    $verification = pendingEmailVerification($purpose);
    if ($verification === null || !preg_match('/^\d{6}$/', $code)) {
        return false;
    }

    $verification['attempts'] = (int) $verification['attempts'] + 1;
    $_SESSION['email_verification'] = $verification;

    if ($verification['attempts'] > EMAIL_VERIFICATION_MAX_ATTEMPTS) {
        unset($_SESSION['email_verification']);
        return false;
    }

    return password_verify($code, (string) $verification['code_hash']);
}

function clearEmailVerification(): void
{
    unset($_SESSION['email_verification']);
}
