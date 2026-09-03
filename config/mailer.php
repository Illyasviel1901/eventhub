<?php

declare(strict_types=1);

require_once __DIR__ . '/environment.php';

loadEnvironment(dirname(__DIR__) . '/.env');

function mailTransportIsConfigured(): bool
{
    return trim((string) getenv('BREVO_API_KEY')) !== ''
        && filter_var(trim((string) getenv('MAIL_FROM')), FILTER_VALIDATE_EMAIL) !== false
        && filter_var(trim((string) getenv('COMPANY_EMAIL')), FILTER_VALIDATE_EMAIL) !== false;
}

function configuredCompanyEmail(): string
{
    return trim((string) getenv('COMPANY_EMAIL'));
}

/**
 * Trimite un email text prin Brevo API folosind HTTPS.
 *
 * @param array{email: string, name?: string}|null $replyTo
 */
function sendBrevoEmail(
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $body,
    ?array $replyTo = null
): bool {
    if (!mailTransportIsConfigured() || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $payload = [
        'sender' => [
            'email' => trim((string) getenv('MAIL_FROM')),
            'name' => trim((string) getenv('MAIL_FROM_NAME')) ?: 'EventHub',
        ],
        'to' => [['email' => $recipientEmail, 'name' => $recipientName]],
        'subject' => $subject,
        'textContent' => $body,
    ];

    if ($replyTo !== null && filter_var($replyTo['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $payload['replyTo'] = [
            'email' => $replyTo['email'],
            'name' => $replyTo['name'] ?? '',
        ];
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        error_log('Eroare Brevo EventHub: conținutul emailului nu a putut fi serializat.');
        return false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . trim((string) getenv('BREVO_API_KEY')),
            ],
            'content' => $json,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $context);
    $responseHeaders = $http_response_header ?? [];
    $statusCode = 0;

    if (isset($responseHeaders[0]) && preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches) === 1) {
        $statusCode = (int) $matches[1];
    }

    if ($statusCode >= 200 && $statusCode < 300) {
        return true;
    }

    $safeResponse = is_string($response) ? substr($response, 0, 500) : 'fără răspuns HTTP';
    error_log("Eroare Brevo EventHub: HTTP {$statusCode}; {$safeResponse}");

    return false;
}

function sendRecipientEmail(string $recipientEmail, string $recipientName, string $subject, string $body): bool
{
    return sendBrevoEmail($recipientEmail, $recipientName, $subject, $body);
}

function sendCompanyEmail(string $subject, string $body, ?string $replyEmail = null, ?string $replyName = null): bool
{
    $companyEmail = configuredCompanyEmail();
    if (!filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $replyTo = $replyEmail !== null && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)
        ? ['email' => $replyEmail, 'name' => $replyName ?? '']
        : null;

    return sendBrevoEmail($companyEmail, 'EventHub', $subject, $body, $replyTo);
}

/** @param array<string, string> $input */
function sendReservationNotification(array $input, string $clientName, string $clientEmail, string $venueName): bool
{
    $body = "A fost înregistrată o solicitare de rezervare EventHub.\n\n"
        . "Client: {$clientName}\nEmail: {$clientEmail}\nLocație: {$venueName}\n"
        . "Data: {$input['event_date']}\nEveniment: {$input['event_name']}\n"
        . "Participanți: {$input['attendees_count']}\nStatus: {$input['status']}\n\n"
        . "Detalii:\n{$input['details']}";

    return sendCompanyEmail('Solicitare nouă: ' . $input['event_name'], $body, $clientEmail, $clientName);
}

/** @param array<string, mixed> $reservation */
function sendReservationStatusEmail(array $reservation, string $status): bool
{
    $statusText = $status === 'APPROVED' ? 'aprobată' : 'respinsă';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $reservation['event_date']);
    $formattedDate = $date === false ? (string) $reservation['event_date'] : $date->format('d.m.Y');
    $body = "Bună ziua, {$reservation['user_name']},\n\n"
        . "Solicitarea dvs. pentru {$reservation['venue_name']} din {$formattedDate} a fost {$statusText}.\n\n"
        . "Eveniment: {$reservation['event_name']}\n"
        . "Status: " . ($status === 'APPROVED' ? 'APROBATĂ' : 'RESPINSĂ') . "\n\n"
        . 'Echipa EventHub';

    return sendRecipientEmail(
        (string) $reservation['user_email'],
        (string) $reservation['user_name'],
        'Solicitare EventHub ' . $statusText,
        $body
    );
}
