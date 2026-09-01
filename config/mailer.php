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

function configuredCompanyEmail(): string
{
    return trim((string) getenv('COMPANY_EMAIL'));
}

function configuredMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) getenv('SMTP_HOST');
    $mail->Port = (int) getenv('SMTP_PORT');
    $mail->SMTPAuth = true;
    $mail->Username = (string) getenv('SMTP_USER');
    $mail->Password = (string) getenv('SMTP_PASSWORD');
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
    if (!smtpIsConfigured() || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
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
    if (!smtpIsConfigured() || !filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
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
