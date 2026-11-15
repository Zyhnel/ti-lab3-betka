<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

require __DIR__ . '/baza.php';

$uzytkownik = (string) $_SESSION['uzytkownik'];

$bledy = [];
$nazwaWartosc = '';
$cenaWartosc = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_sprawdz();

    $akcja = (string) ($_POST['akcja'] ?? '');

    if ($akcja === 'dodaj') {
        $nazwaWartosc = trim((string) ($_POST['nazwa'] ?? ''));
        $cenaWartosc = trim((string) ($_POST['cena'] ?? ''));

        if ($nazwaWartosc === '' || mb_strlen($nazwaWartosc) > 100) {
            $bledy[] = 'Nazwa musi mieć od 1 do 100 znaków.';
        }

        $cena = filter_var($cenaWartosc, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100000],
        ]);

        if ($cena === false) {
            $bledy[] = 'Cena musi być liczbą całkowitą od 1 do 100000.';
        }

        if (empty($bledy)) {
            $zapytanie = $pdo->prepare(
                'INSERT INTO uslugi (nazwa, cena) VALUES (:nazwa, :cena)'
            );
            $zapytanie->execute(['nazwa' => $nazwaWartosc, 'cena' => $cena]);

            header('Location: admin.php?ok=dodano');
            exit;
        }
    } else {
        http_response_code(400);
        exit('Nieznana akcja.');
    }
}

$komunikaty = [
    'dodano' => 'Usługa dodana.',
    'usunieto' => 'Usługa usunięta.',
];

$ok = (string) ($_GET['ok'] ?? '');
$komunikat = $komunikaty[$ok] ?? '';

$uslugi = $pdo->query('SELECT id, nazwa, cena FROM uslugi ORDER BY nazwa')->fetchAll();
?>
<!doctype html>
<html lang="pl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel — Szprycha</title>
  </head>
  <body>
    <header>
      <p>Szprycha · panel</p>
      <p>Witaj, <?= e($uzytkownik) ?></p>
      <form method="post" action="wyloguj.php">
        <?= csrf_pole() ?>
        <button type="submit">Wyloguj</button>
      </form>
    </header>

    <main>
      <h1>Usługi</h1>

      <?php if ($komunikat !== ''): ?>
        <p role="status"><?= e($komunikat) ?></p>
      <?php endif; ?>

      <table>
        <thead>
          <tr>
            <th scope="col">Usługa</th>
            <th scope="col">Cena</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($uslugi as $usluga): ?>
            <tr>
              <td><?= e($usluga['nazwa']) ?></td>
              <td><?= (int) $usluga['cena'] ?> zł</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h2>Dodaj usługę</h2>

      <?php foreach ($bledy as $blad): ?>
        <p role="alert"><?= e($blad) ?></p>
      <?php endforeach; ?>

      <form method="post" action="admin.php">
        <input type="hidden" name="akcja" value="dodaj">
        <?= csrf_pole() ?>

        <p>
          <label for="nazwa">Nazwa</label><br>
          <input id="nazwa" name="nazwa" type="text" required maxlength="100" value="<?= e($nazwaWartosc) ?>">
        </p>

        <p>
          <label for="cena">Cena (zł)</label><br>
          <input id="cena" name="cena" type="number" min="1" max="100000" required value="<?= e($cenaWartosc) ?>">
        </p>

        <button type="submit">Dodaj</button>
      </form>
    </main>
  </body>
</html>
