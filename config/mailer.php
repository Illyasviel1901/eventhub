<?php

declare(strict_types=1);

require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

loadEnvironment(dirname(__DIR__) . '/.env');

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function smtpIsConfigured(): bool
{
    foreach (['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASSWORD', 'MAIL_FROM', 'COMPANY_EMAIL'] as $key) {
        if (trim((string) getenv($key)) === '') {
            return false;
        }
    }

    return true;
}

function brevoIsConfigured(): bool
{
    return trim((string) getenv('BREVO_API_KEY')) !== ''
        && filter_var(trim((string) getenv('MAIL_FROM')), FILTER_VALIDATE_EMAIL) !== false;
}

function mailTransportIsConfigured(): bool
{
    return brevoIsConfigured() || smtpIsConfigured();
}

function configuredCompanyEmail(): string
{
    return trim((string) getenv('COMPANY_EMAIL'));
}

/**
 * Trimite un email prin API-ul HTTPS Brevo.
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
    if (!brevoIsConfigured() || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $senderEmail = trim((string) getenv('MAIL_FROM'));
    $senderName = trim((string) getenv('MAIL_FROM_NAME')) ?: 'EventHub';
    $payload = [
        'sender' => ['email' => $senderEmail, 'name' => $senderName],
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

function configuredMailer(): PHPMailer
{
    $smtpHost = trim((string) getenv('SMTP_HOST'));
    $smtpIpv4Addresses = gethostbynamel($smtpHost);
    $connectionHost = is_array($smtpIpv4Addresses) && isset($smtpIpv4Addresses[0])
        ? $smtpIpv4Addresses[0]
        : $smtpHost;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $connectionHost;
    $mail->Port = (int) getenv('SMTP_PORT');
    $mail->SMTPAuth = true;
    $mail->Username = (string) getenv('SMTP_USER');
    $mail->Password = (string) getenv('SMTP_PASSWORD');
    $mail->Timeout = 8;
    $mail->getSMTPInstance()->Timelimit = 8;
    $mail->SMTPOptions = [
        'ssl' => [
            'peer_name' => $smtpHost,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ];
    $encryption = strtolower(trim((string) getenv('SMTP_ENCRYPTION')));

    if (in_array($encryption, ['tls', 'ssl'], true)) {
        $mail->SMTPSecure = $encryption;
    }

    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->setFrom((string) getenv('MAIL_FROM'), trim((string) getenv('MAIL_FROM_NAME')) ?: 'EventHub');
    $mail->isHTML(false);

    return $mail;
}

function sendRecipientEmail(string $recipientEmail, string $recipientName, string $subject, string $body): bool
{
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (brevoIsConfigured()) {
        return sendBrevoEmail($recipientEmail, $recipientName, $subject, $body);
    }

    if (!smtpIsConfigured()) {
        return false;
    }

    try {
        $mail = configuredMailer();
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $exception) {
        error_log('Eroare SMTP EventHub: ' . ($mail->ErrorInfo ?? $exception->getMessage()));
        return false;
    }
}

function sendCompanyEmail(string $subject, string $body, ?string $replyEmail = null, ?string $replyName = null): bool
{
    $companyEmail = configuredCompanyEmail();
    if (!filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (brevoIsConfigured()) {
        $replyTo = $replyEmail !== null && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)
            ? ['email' => $replyEmail, 'name' => $replyName ?? '']
            : null;

        return sendBrevoEmail($companyEmail, 'EventHub', $subject, $body, $replyTo);
    }

    if (!smtpIsConfigured()) {
        return false;
    }

    try {
        $mail = configuredMailer();
        $mail->addAddress($companyEmail, 'EventHub');
        if ($replyEmail !== null && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyEmail, $replyName ?? '');
        }
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $exception) {
        error_log('Eroare SMTP EventHub: ' . ($mail->ErrorInfo ?? $exception->getMessage()));
        return false;
    }
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

function sendReservationStatusEmail(array $reservation, string $status): bool
{
    $statusText = $status === 'APPROVED' ? 'aprobată' : 'respinsă';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $reservation['event_date']);
    $formattedDate = $date === false ? (string) $reservation['event_date'] : $date->format('d.m.Y');
    $body = "Bună ziua, {$reservation['user_name']},\n\n"
        . "Cererea dvs. pentru {$reservation['venue_name']} din {$formattedDate} a fost {$statusText}.\n\n"
        . "Eveniment: {$reservation['event_name']}\n"
        . "Status: " . ($status === 'APPROVED' ? 'APROBATĂ' : 'RESPINSĂ') . "\n\n"
        . "Echipa EventHub";

    return sendRecipientEmail(
        (string) $reservation['user_email'],
        (string) $reservation['user_name'],
        'Cerere EventHub ' . $statusText,
        $body
    );
}
