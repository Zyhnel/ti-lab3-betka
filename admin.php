<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$uzytkownik = (string) $_SESSION['uzytkownik'];
?>
<!doctype html>
<html lang="pl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel — Szprycha</title>
  </head>
  <body>
    <main>
      <h1>Witaj, <?= e($uzytkownik) ?></h1>
      <p>To jest panel administratora.</p>

      <form method="post" action="wyloguj.php">
        <?= csrf_pole() ?>
        <button type="submit">Wyloguj</button>
      </form>
    </main>
  </body>
</html>
