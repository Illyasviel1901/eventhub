<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodă nepermisă.');
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitare invalidă. Reîncarcă pagina și încearcă din nou.');
}

logoutUser();
startSecureSession();
setFlash('success', 'Te-ai deconectat cu succes.');
redirect('index.php');
