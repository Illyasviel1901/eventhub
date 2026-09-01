<?php

declare(strict_types=1);

require_once __DIR__ . '/config/environment.php';
loadEnvironment(__DIR__ . '/.env');

header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$baseUrl = rtrim((string) environment('APP_URL', 'http://127.0.0.1:8000'), '/');
?>
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /account.php
Disallow: /account-edit.php
Disallow: /login.php
Disallow: /register.php
Disallow: /logout.php
Disallow: /my-reservations.php
Disallow: /reservation-create.php
Disallow: /verify-email.php
Disallow: /verify-email-change.php
Disallow: /weather-forecast.php
Sitemap: <?= $baseUrl ?>/sitemap.php
