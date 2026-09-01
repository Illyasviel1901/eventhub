<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/user-management.php';
require_once __DIR__ . '/includes/email-verification.php';

requireRole('USER');
$user = currentUser();
$name = $user['name'];
$email = $user['email'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateUserIdentity($name, $email);
        if ($errors === [] && userEmailExists($email, (int) $user['id'])) {
            $errors[] = 'Există deja un cont asociat acestei adrese de email.';
        }

        if ($errors === [] && $email !== strtolower((string) $user['email'])) {
            if (!mailTransportIsConfigured()) {
                $errors[] = 'Schimbarea emailului necesită configurarea serviciului de email pentru trimiterea codului.';
            } elseif (startEmailVerification('email_change', $email, [
                'user_id' => (int) $user['id'],
                'name' => $name,
                'email' => $email,
            ])) {
                redirect('verify-email-change.php');
            } else {
                $errors[] = 'Codul de verificare nu a putut fi trimis la noua adresă.';
            }
        } elseif ($errors === []) {
            updateUserIdentity((int) $user['id'], $name, $email);
            loginUser(['id' => $user['id'], 'name' => $name, 'email' => $email, 'role' => 'USER']);
            setFlash('success', 'Datele contului au fost actualizate.');
            redirect('account.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Editează contul';
$pageDescription = 'Modifică numele și emailul contului EventHub.';
$currentPage = 'account';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Cont client</p><h1>Editează datele contului</h1><p>O adresă nouă de email va fi salvată numai după confirmarea codului primit.</p></div></section>
<section class="section"><div class="shell narrow"><div class="form-card">
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="account-edit.php" novalidate>
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group"><label for="name">Nume complet</label><input id="name" name="name" type="text" minlength="2" maxlength="100" autocomplete="name" value="<?= e($name) ?>" required></div>
<div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" autocomplete="email" value="<?= e($email) ?>" required></div>
<div class="form-actions"><button class="button button-primary" type="submit">Salvează modificările</button><a class="button button-secondary" href="account.php">Anulează</a></div>
</form>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
