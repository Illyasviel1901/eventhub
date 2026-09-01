<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('ADMIN', '../login.php');

$pageTitle = 'Rapoarte și transfer de date';
$pageDescription = 'Exporturi XLSX, import de locații și raport PDF EventHub.';
$currentPage = 'reports';
$basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading"><div class="shell"><p class="eyebrow">Administrare</p><h1>Rapoarte și transfer de date</h1><p>Exportă datele reale ale aplicației, importă locații validate și generează raportul PDF.</p></div></section>
<section class="section admin-section"><div class="shell report-grid">
    <article class="report-card"><p class="eyebrow">XLSX</p><h2>Export locații</h2><p>Descarcă numele, descrierea, adresa și capacitatea tuturor locațiilor.</p><a class="button button-primary" href="export-venues.php">Descarcă XLSX</a></article>
    <article class="report-card"><p class="eyebrow">XLSX</p><h2>Export cereri</h2><p>Descarcă cererile împreună cu clientul, locația, data, participanții și statusul.</p><a class="button button-primary" href="export-reservations.php">Descarcă XLSX</a></article>
    <article class="report-card"><p class="eyebrow">PDF</p><h2>Raport general</h2><p>Generează o sinteză cu indicatori, distribuția statusurilor și lista cererilor.</p><a class="button button-primary" href="report-pdf.php">Descarcă PDF</a></article>
    <article class="report-card"><p class="eyebrow">Import XLSX</p><h2>Import locații</h2><p>Folosește antetele: Nume, Descriere, Adresa, Capacitate. Numele existente sunt actualizate.</p><form method="post" action="venue-import.php" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-group"><label for="venues-file">Fișier XLSX, maximum 2 MB</label><input id="venues-file" type="file" name="venues_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div><button class="button button-primary" type="submit">Importă locațiile</button></form></article>
</div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
