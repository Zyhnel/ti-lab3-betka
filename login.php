<?php
declare(strict_types=1);

require __DIR__ . '/funkcje.php';
require __DIR__ . '/sesja.php';

// Zalogowany użytkownik nie potrzebuje formularza logowania.
if (!empty($_SESSION['uzytkownik'])) {
    header('Location: admin.php');
    exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$blad = '';
$loginWartosc = '';

$limitProb = 5;
$czasBlokady = 60;
$teraz = time();

if (!isset($_SESSION['proby'])) {
    $_SESSION['proby'] = 0;
}
if (!isset($_SESSION['blokada_do'])) {
    $_SESSION['blokada_do'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_sprawdz();

    if ($_SESSION['blokada_do'] > $teraz) {
        $pozostalo = (int) ($_SESSION['blokada_do'] - $teraz);
        $blad = 'Zbyt wiele prób. Spróbuj ponownie za ' . $pozostalo . ' s.';
    } else {
        $loginWartosc = trim((string) ($_POST['login'] ?? ''));
        $haslo = (string) ($_POST['haslo'] ?? '');

        try {
            require __DIR__ . '/baza.php';

            $zapytanie = $pdo->prepare(
                'SELECT login, haslo_hash FROM uzytkownicy WHERE login = :login'
            );
            $zapytanie->execute(['login' => $loginWartosc]);
            $uzytkownik = $zapytanie->fetch();

            // Stały atrapowy hash — password_verify wykonuje się także dla nieistniejącego loginu.
            $atrapa = '$2y$10$zr9mW/Z3c/fCsgGKsEAft.iEEYBPB0ggNqD7mq38jonKi.PW2REwC';
            $hash = $uzytkownik ? $uzytkownik['haslo_hash'] : $atrapa;
            $pasuje = password_verify($haslo, $hash);

            if ($uzytkownik && $pasuje) {
                session_regenerate_id(true);
                $_SESSION['uzytkownik'] = $uzytkownik['login'];
                $_SESSION['proby'] = 0;
                $_SESSION['blokada_do'] = 0;

                header('Location: admin.php');
                exit;
            }

            $_SESSION['proby']++;

            if ($_SESSION['proby'] >= $limitProb) {
                $_SESSION['proby'] = 0;
                $_SESSION['blokada_do'] = time() + $czasBlokady;
                $blad = 'Zbyt wiele nieudanych prób. Logowanie zablokowane na 60 s.';
            } else {
                $blad = 'Błędny login lub hasło.';
            }
        } catch (PDOException $e) {
            error_log('Błąd bazy w login.php: ' . $e->getMessage());
            $blad = 'Chwilowy problem z bazą danych. Spróbuj później.';
        }
    }
}
?>
<!doctype html>
<html lang="pl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logowanie — Szprycha</title>
  </head>
  <body>
    <main>
      <h1>Logowanie do panelu</h1>

      <?php if ($blad !== ''): ?>
        <p role="alert"><?= e($blad) ?></p>
      <?php endif; ?>

      <form method="post" action="login.php">
        <?= csrf_pole() ?>

        <p>
          <label for="login">Login</label><br>
          <input id="login" name="login" type="text" required autofocus value="<?= e($loginWartosc) ?>">
        </p>

        <p>
          <label for="haslo">Hasło</label><br>
          <input id="haslo" name="haslo" type="password" required>
        </p>

        <button type="submit">Zaloguj</button>
      </form>
    </main>
  </body>
</html>
