<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email-verification.php';

if (isAuthenticated()) {
    redirect('account.php');
}

$name = '';
$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
    $website = $_POST['website'] ?? null;

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif (isAutomatedPublicSubmission($website)) {
        $errors[] = 'Cererea nu a putut fi procesată.';
    }

    if ($name === '' || strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Numele trebuie să conțină între 2 și 100 de caractere.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Introdu o adresă de email validă, de maximum 100 de caractere.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Parola trebuie să conțină între 8 și 72 de caractere.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'Confirmarea parolei nu corespunde.';
    }

    if ($errors === []) {
        $statement = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        if ($statement->fetchColumn() !== false) {
            $errors[] = 'Există deja un cont asociat acestei adrese de email.';
        } elseif (!smtpIsConfigured()) {
            $errors[] = 'Verificarea emailului nu este disponibilă până la configurarea SMTP.';
        } elseif (startEmailVerification('registration', $email, [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ])) {
            redirect('verify-email.php');
        } else {
            $errors[] = 'Codul de verificare nu a putut fi trimis. Verifică adresa și încearcă din nou.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Înregistrare';
$pageDescription = 'Creează un cont de client EventHub.';
$currentPage = 'register';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="shell auth-layout-single">
        <div class="form-card">
            <h1 class="form-title">Înregistrare</h1>
            <p class="form-lead">Ai deja un cont? <a href="login.php">Autentifică-te</a>.</p>
            <?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="register.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="honeypot" aria-hidden="true"><label for="register-website">Website</label><input id="register-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                <div class="form-group"><label for="name">Nume complet</label><input id="name" name="name" type="text" minlength="2" maxlength="100" autocomplete="name" value="<?= e($name) ?>" required></div>
                <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" autocomplete="email" value="<?= e($email) ?>" required></div>
                <div class="form-group"><label for="password">Parolă</label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required><small id="password-help">Între 8 și 72 de caractere.</small></div>
                <div class="form-group"><label for="password-confirmation">Confirmă parola</label><input id="password-confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
                <button class="button button-primary button-full" type="submit">Trimite codul de verificare</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
