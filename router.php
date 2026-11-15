<?php
declare(strict_types=1);

/**
 * Router dla wbudowanego serwera PHP:
 *   php -S 0.0.0.0:8000 router.php
 *
 * Blokuje katalog dane/, pliki bazy, pliki ukryte oraz bezpośredni dostęp
 * do plików, które powinny być tylko dołączane.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = rawurldecode($uri);

// Odrzuć próby wyjścia poza katalog aplikacji.
if (str_contains($uri, '..')) {
    http_response_code(404);
    echo 'Nie znaleziono.';
    return true;
}

// Katalog dane/ i pliki bazy danych.
if (preg_match('#^/dane(/|$)#i', $uri)
    || preg_match('#\.(sqlite|sqlite3|db|sqlite-journal|sqlite-wal|sqlite-shm)$#i', $uri)
) {
    http_response_code(404);
    echo 'Nie znaleziono.';
    return true;
}

// Pliki i katalogi ukryte (np. .git, .htaccess).
foreach (explode('/', trim($uri, '/')) as $segment) {
    if ($segment !== '' && $segment[0] === '.') {
        http_response_code(404);
        echo 'Nie znaleziono.';
        return true;
    }
}

// Pliki tylko do dołączania — nie wolno ich otwierać bezpośrednio.
$zablokowane = ['baza.php', 'auth.php', 'setup.php', 'router.php'];
if (in_array(strtolower(basename($uri)), $zablokowane, true)) {
    http_response_code(404);
    echo 'Nie znaleziono.';
    return true;
}

$sciezka = __DIR__ . $uri;

// Pliki statyczne obsługuje wbudowany serwer.
if ($uri !== '/' && is_file($sciezka) && !str_ends_with($sciezka, '.php')) {
    return false;
}

// Pliki PHP dołączamy sami.
if (is_file($sciezka) && str_ends_with($sciezka, '.php')) {
    require $sciezka;
    return true;
}

// Strona główna.
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo 'Nie znaleziono.';
return true;
