<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isAuthenticated()) {
    $authenticatedUser = currentUser();
    redirect(destinationAfterAuthentication($authenticatedUser['role']));
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $website = $_POST['website'] ?? null;

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif (isAutomatedPublicSubmission($website)) {
        $errors[] = 'Solicitarea nu a putut fi procesată.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Introdu o adresă de email și o parolă valide.';
    } else {
        $statement = db()->prepare(
            'SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user !== false && password_verify($password, $user['password'])) {
            loginUser($user);
            setFlash('success', 'Autentificarea a reușit.');
            redirect(destinationAfterAuthentication($user['role']));
        }

        $errors[] = 'Emailul sau parola sunt incorecte.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Autentificare';
$pageDescription = 'Autentifică-te în contul tău EventHub.';
$currentPage = 'login';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="shell auth-layout auth-layout-single">
        <div class="form-card">
            <h2>Autentificare</h2>
            <p class="form-lead">Nu ai încă un cont? <a href="register.php">Înregistrează-te</a>.</p>

            <?php if ($errors !== []): ?>
                <div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="honeypot" aria-hidden="true"><label for="login-website">Website</label><input id="login-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" autocomplete="email" value="<?= e($email) ?>" required></div>
                <div class="form-group"><label for="password">Parolă</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
                <button class="button button-primary button-full" type="submit">Autentifică-te</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
