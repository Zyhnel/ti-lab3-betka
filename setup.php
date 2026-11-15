<?php
declare(strict_types=1);

/**
 * Jednorazowe przygotowanie bazy: tabele, cztery usługi i konto administratora.
 * Uruchamiaj wyłącznie z terminala:
 *   ADMIN_HASLO='...' php setup.php
 * albo bez zmiennej — skrypt zapyta o hasło interaktywnie.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ten skrypt uruchamia się tylko z terminala.');
}

$katalogDanych = __DIR__ . '/dane';

if (!is_dir($katalogDanych)) {
    if (!mkdir($katalogDanych, 0775, true) && !is_dir($katalogDanych)) {
        fwrite(STDERR, "Nie udało się utworzyć katalogu dane/.\n");
        exit(1);
    }
}

try {
    require __DIR__ . '/baza.php';

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS uslugi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nazwa TEXT NOT NULL,
            cena INTEGER NOT NULL,
            popularnosc INTEGER NOT NULL DEFAULT 0
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS uzytkownicy (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login TEXT NOT NULL UNIQUE,
            haslo_hash TEXT NOT NULL
        )'
    );

    // Cztery usługi ze slajdu — tylko gdy tabela jest pusta.
    $ileUslug = (int) $pdo->query('SELECT COUNT(*) FROM uslugi')->fetchColumn();

    if ($ileUslug === 0) {
        $uslugi = [
            ['nazwa' => 'Przegląd', 'cena' => 120, 'popularnosc' => 45],
            ['nazwa' => 'Wymiana dętki', 'cena' => 40, 'popularnosc' => 30],
            ['nazwa' => 'Centrowanie koła', 'cena' => 60, 'popularnosc' => 15],
            ['nazwa' => 'Serwis pełny', 'cena' => 350, 'popularnosc' => 10],
        ];

        $zapytanie = $pdo->prepare(
            'INSERT INTO uslugi (nazwa, cena, popularnosc)
             VALUES (:nazwa, :cena, :popularnosc)'
        );

        foreach ($uslugi as $usluga) {
            $zapytanie->execute($usluga);
        }
    }

    // Konto administratora — tylko gdy tabela użytkowników jest pusta.
    $ileKont = (int) $pdo->query('SELECT COUNT(*) FROM uzytkownicy')->fetchColumn();

    if ($ileKont === 0) {
        $haslo = getenv('ADMIN_HASLO');

        if ($haslo === false || $haslo === '') {
            fwrite(STDOUT, 'Podaj hasło administratora (min. 12 znaków): ');

            $terminal = stream_isatty(STDIN);

            if ($terminal) {
                shell_exec('stty -echo');
            }

            $haslo = fgets(STDIN);

            if ($terminal) {
                shell_exec('stty echo');
                fwrite(STDOUT, "\n");
            }

            if ($haslo !== false) {
                $haslo = rtrim($haslo, "\r\n");
            }
        }

        if (!is_string($haslo) || mb_strlen($haslo) < 12) {
            fwrite(STDERR, "Hasło musi mieć co najmniej 12 znaków. Przerwano.\n");
            exit(1);
        }

        $hash = password_hash($haslo, PASSWORD_DEFAULT);

        $zapytanie = $pdo->prepare(
            'INSERT INTO uzytkownicy (login, haslo_hash) VALUES (:login, :haslo_hash)'
        );
        $zapytanie->execute(['login' => 'admin', 'haslo_hash' => $hash]);
    }

    echo "Baza gotowa.\n";
} catch (PDOException $e) {
    error_log('Błąd setup.php: ' . $e->getMessage());
    fwrite(STDERR, "Błąd bazy danych. Szczegóły zapisano w logu.\n");
    exit(1);
}
