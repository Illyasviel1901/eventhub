<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/user-management.php';

requireRole('ADMIN', '../login.php');
$name = ''; $email = ''; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateUserIdentity($name, $email);
        if (strlen($password) < 8 || strlen($password) > 72) $errors[] = 'Parola trebuie să conțină între 8 și 72 de caractere.';
        if ($password !== $passwordConfirmation) $errors[] = 'Confirmarea parolei nu corespunde.';
        if ($errors === [] && userEmailExists($email)) $errors[] = 'Există deja un cont asociat acestei adrese de email.';

        if ($errors === []) {
            createClientUser($name, $email, $password);
            setFlash('success', 'Utilizatorul USER a fost adăugat.');
            redirect('users.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Adaugă utilizator'; $pageDescription = 'Adaugă un client EventHub.'; $currentPage = 'admin'; $basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Administrare utilizatori</p><h1>Adaugă utilizator</h1><p>Contul creat va primi întotdeauna rolul USER.</p></div></section>
<section class="section admin-section"><div class="shell narrow"><div class="form-card">
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="user-create.php" novalidate><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group"><label for="name">Nume complet</label><input id="name" name="name" type="text" minlength="2" maxlength="100" value="<?= e($name) ?>" required></div>
<div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" value="<?= e($email) ?>" required></div>
<div class="form-group"><label for="password">Parolă inițială</label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
<div class="form-group"><label for="password-confirmation">Confirmă parola</label><input id="password-confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
<div class="form-actions"><button class="button button-primary" type="submit">Adaugă utilizatorul</button><a class="button button-secondary" href="users.php">Anulează</a></div>
</form></div></div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
