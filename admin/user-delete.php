<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/user-management.php';

requireRole('ADMIN', '../login.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Metodă HTTP nepermisă.'); }
if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Token CSRF invalid.'); }

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$user = $id === false ? null : getUserById((int) $id);
if ($user === null) { http_response_code(404); exit('Utilizatorul nu a fost găsit.'); }
if ($user['role'] !== 'USER') { http_response_code(403); exit('Conturile administrative nu pot fi șterse din această secțiune.'); }

deleteClientUser((int) $id);
setFlash('success', 'Utilizatorul „' . $user['name'] . '” și solicitările sale au fost șterse.');
redirect('users.php');
