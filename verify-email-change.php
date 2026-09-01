<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/user-management.php';
require_once __DIR__ . '/includes/email-verification.php';

requireRole('USER');
$user = currentUser();
$verification = pendingEmailVerification('email_change');
if ($verification === null || (int) ($verification['payload']['user_id'] ?? 0) !== (int) $user['id']) {
    clearEmailVerification();
    setFlash('error', 'Nu există o schimbare de email activă sau codul a expirat.');
    redirect('account-edit.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim((string) ($_POST['code'] ?? ''));
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat.';
    } elseif (!verifyPendingEmailCode('email_change', $code)) {
        $errors[] = 'Codul este incorect sau a expirat.';
    } else {
        $verification = pendingEmailVerification('email_change');
        $payload = $verification['payload'];
        if (userEmailExists((string) $payload['email'], (int) $user['id'])) {
            clearEmailVerification();
            $errors[] = 'Adresa de email este deja folosită de alt cont.';
        } else {
            updateUserIdentity((int) $user['id'], (string) $payload['name'], (string) $payload['email']);
            clearEmailVerification();
            loginUser(['id' => $user['id'], 'name' => $payload['name'], 'email' => $payload['email'], 'role' => 'USER']);
            setFlash('success', 'Noua adresă de email a fost verificată și salvată.');
            redirect('account.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Confirmă noul email';
$pageDescription = 'Confirmă noua adresă de email EventHub.';
$currentPage = 'account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="shell auth-layout-single"><div class="form-card">
<h1 class="form-title">Confirmă noul email</h1>
<p class="form-lead">Introdu codul trimis la <strong><?= e($verification['email']) ?></strong>. Adresa actuală rămâne activă până la confirmare.</p>
<?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="verify-email-change.php" novalidate>
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group"><label for="code">Cod de verificare</label><input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required></div>
<button class="button button-primary button-full" type="submit">Confirmă noul email</button>
</form>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
