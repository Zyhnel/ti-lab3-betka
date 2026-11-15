<?php
declare(strict_types=1);

/**
 * Bezpieczne wypisanie dowolnej wartości w HTML (ochrona przed XSS).
 * Zawsze używaj tej funkcji przy wypisywaniu danych z bazy, $_GET i $_POST.
 */
function e(mixed $wartosc): string
{
    return htmlspecialchars((string) $wartosc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Token CSRF przypisany do bieżącej sesji.
 * Sesja musi być już uruchomiona (np. przez sesja.php).
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Sesja nie została uruchomiona przed csrf_token().');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Ukryte pole formularza z tokenem CSRF.
 */
function csrf_pole(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Sprawdza token CSRF przesłany w formularzu POST.
 * Przy błędzie kończy skrypt kodem 400.
 */
function csrf_sprawdz(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Nieprawidłowy token CSRF.');
    }
}
