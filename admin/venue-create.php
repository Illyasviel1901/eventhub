<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/venue-management.php';

requireRole('ADMIN', '../login.php');

$venueInput = ['name' => '', 'description' => '', 'address' => '', 'capacity' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venueInput = venueInputFromPost();

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateVenueInput($venueInput);

        if ($errors === [] && venueNameExists($venueInput['name'])) {
            $errors[] = 'Există deja o locație cu acest nume.';
        }

        if ($errors === []) {
            createVenue($venueInput);
            setFlash('success', 'Locația a fost adăugată.');
            redirect('venues.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Adaugă locație';
$pageDescription = 'Adaugă o locație nouă în EventHub.';
$currentPage = 'admin';
$basePath = '../';
$submitLabel = 'Adaugă locația';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Administrare locații</p><h1>Adaugă o locație</h1><p>Completează informațiile care vor apărea în catalogul public.</p></div></section>
<section class="section admin-section"><div class="shell narrow"><div class="form-card">
    <?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="venue-create.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php require __DIR__ . '/venue-form-fields.php'; ?>
    </form>
</div></div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
