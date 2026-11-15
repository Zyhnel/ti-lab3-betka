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
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    >
  </head>
  <body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
      <div class="container">
        <span class="navbar-brand mb-0 h1">Szprycha · panel</span>
        <a class="link-light text-decoration-none" href="index.php">Strona główna</a>
      </div>
    </nav>

    <main class="container my-5">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <h1 class="h3 mb-3">Logowanie do panelu</h1>

          <?php if ($blad !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= e($blad) ?></div>
          <?php endif; ?>

          <form method="post" action="login.php">
            <?= csrf_pole() ?>

            <div class="mb-3">
              <label for="login" class="form-label">Login</label>
              <input id="login" name="login" type="text" class="form-control" required autofocus value="<?= e($loginWartosc) ?>">
            </div>

            <div class="mb-3">
              <label for="haslo" class="form-label">Hasło</label>
              <input id="haslo" name="haslo" type="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Zaloguj</button>
          </form>
        </div>
      </div>
    </main>
  </body>
</html>
