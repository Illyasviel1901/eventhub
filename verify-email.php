<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email-verification.php';

if (isAuthenticated()) {
    redirect('account.php');
}

$verification = pendingEmailVerification('registration');
if ($verification === null) {
    setFlash('error', 'Nu există o verificare activă sau codul a expirat.');
    redirect('register.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim((string) ($_POST['code'] ?? ''));
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat.';
    } elseif (isAutomatedPublicSubmission($_POST['website'] ?? null)) {
        $errors[] = 'Solicitarea nu a putut fi procesată.';
    } elseif (!verifyPendingEmailCode('registration', $code)) {
        $errors[] = 'Codul este incorect sau a expirat.';
    } else {
        $verification = pendingEmailVerification('registration');
        $payload = $verification['payload'];
        $statement = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $statement->execute(['email' => $payload['email']]);

        if ((int) $statement->fetchColumn() > 0) {
            clearEmailVerification();
            $errors[] = 'Există deja un cont asociat acestei adrese de email.';
        } else {
            $statement = db()->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'USER')");
            $statement->execute([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password_hash'],
            ]);
            $id = (int) db()->lastInsertId();
            clearEmailVerification();
            loginUser(['id' => $id, 'name' => $payload['name'], 'email' => $payload['email'], 'role' => 'USER']);
            setFlash('success', 'Emailul a fost verificat, iar contul a fost creat.');
            redirect(destinationAfterAuthentication('USER'));
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Verifică emailul';
$pageDescription = 'Confirmă adresa de email pentru contul EventHub.';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="shell auth-layout-single"><div class="form-card">
<h1 class="form-title">Verifică emailul</h1>
<p class="form-lead">Am trimis un cod de 6 cifre la <strong><?= e($verification['email']) ?></strong>. Codul este valabil 10 minute.</p>
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="verify-email.php" novalidate>
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="honeypot" aria-hidden="true"><label for="verify-website">Website</label><input id="verify-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
<div class="form-group"><label for="code">Cod de verificare</label><input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required></div>
<button class="button button-primary button-full" type="submit">Confirmă și creează contul</button>
</form>
<p class="verification-note"><a href="register.php">Reia înregistrarea</a> dacă adresa este greșită.</p>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
