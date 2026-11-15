<?php
declare(strict_types=1);

require __DIR__ . '/funkcje.php';
require __DIR__ . '/sesja.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Wylogowanie tylko metodą POST.');
}

csrf_sprawdz();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametry = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametry['path'],
        'domain' => $parametry['domain'],
        'secure' => $parametry['secure'],
        'httponly' => $parametry['httponly'],
        'samesite' => $parametry['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

header('Location: login.php');
exit;
