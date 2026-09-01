<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/venue-management.php';

requireRole('ADMIN', '../login.php');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$venue = $id === false ? null : getVenueById((int) $id);

if ($venue === null) {
    http_response_code(404);
    exit('Locația nu a fost găsită.');
}

$venueInput = [
    'name' => $venue['name'],
    'description' => $venue['description'],
    'address' => $venue['address'],
    'capacity' => (string) $venue['capacity'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venueInput = venueInputFromPost();

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $errors = validateVenueInput($venueInput);

        if ($errors === [] && venueNameExists($venueInput['name'], (int) $id)) {
            $errors[] = 'Există deja o altă locație cu acest nume.';
        }

        if ($errors === []) {
            updateVenue((int) $id, $venueInput);
            setFlash('success', 'Locația a fost actualizată.');
            redirect('venues.php');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodă HTTP nepermisă.');
}

$pageTitle = 'Editează locația';
$pageDescription = 'Modifică informațiile locației EventHub.';
$currentPage = 'admin';
$basePath = '../';
$submitLabel = 'Salvează modificările';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell narrow"><p class="eyebrow">Administrare locații</p><h1>Editează locația</h1><p>Modifici informațiile pentru „<?= e($venue['name']) ?>”.</p></div></section>
<section class="section admin-section"><div class="shell narrow"><div class="form-card">
    <?php if ($errors !== []): ?><div class="notice notice-error notice-left" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="venue-edit.php?id=<?= (int) $id ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php require __DIR__ . '/venue-form-fields.php'; ?>
    </form>
</div></div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
