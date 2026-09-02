<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireAuthentication();
$user = currentUser();
$subject = '';
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $website = $_POST['website'] ?? null;

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif (isAutomatedPublicSubmission($website)) {
        $errors[] = 'Cererea nu a putut fi procesată.';
    }
    if (strlen($subject) < 2 || strlen($subject) > 100) {
        $errors[] = 'Subiectul trebuie să conțină între 2 și 100 de caractere.';
    }
    if ($message === '') {
        $errors[] = 'Mesajul este obligatoriu.';
    } elseif (strlen($message) > 5000) {
        $errors[] = 'Mesajul poate avea maximum 5000 de caractere.';
    }

    if ($errors === []) {
        $statement = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)');
        $statement->execute(['name' => $user['name'], 'email' => $user['email'], 'subject' => $subject, 'message' => $message]);
        $sent = sendCompanyEmail(
            'Contact EventHub: ' . $subject,
            "Nume: {$user['name']}\nEmail: {$user['email']}\n\n{$message}",
            $user['email'],
            $user['name']
        );
        setFlash($sent ? 'success' : 'info', $sent
            ? 'Mesajul a fost trimis echipei EventHub.'
            : 'Mesajul a fost înregistrat. Emailul nu a putut fi trimis.');
        redirect('contact.php');
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Contact';
$pageDescription = 'Contactează echipa EventHub.';
$currentPage = 'contact';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Suntem aici pentru tine</p><h1>Contactează EventHub</h1></div></section>
<section class="section"><div class="shell narrow"><div class="form-card">
<div class="contact-identity"><span>Trimiți ca</span><strong><?= e($user['name']) ?></strong><p><?= e($user['email']) ?></p></div>
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="contact.php" novalidate>
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="honeypot" aria-hidden="true"><label for="contact-website">Website</label><input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
<div class="form-group"><label for="subject">Subiect</label><input id="subject" name="subject" type="text" minlength="2" maxlength="100" value="<?= e($subject) ?>" required></div>
<div class="form-group"><label for="message">Mesaj</label><textarea id="message" name="message" maxlength="5000" rows="8" required><?= e($message) ?></textarea></div>
<button class="button button-primary" type="submit">Trimite mesajul</button>
</form>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
