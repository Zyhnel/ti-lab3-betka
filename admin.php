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
    } elseif ($akcja === 'usun') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            http_response_code(400);
            exit('Nieprawidłowe id.');
        }

        $zapytanie = $pdo->prepare('DELETE FROM uslugi WHERE id = :id');
        $zapytanie->execute(['id' => $id]);

        header('Location: admin.php?ok=usunieto');
        exit;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
      <div class="container">
        <span class="navbar-brand mb-0 h1">Szprycha · panel</span>
        <div class="d-flex align-items-center gap-3">
          <span class="navbar-text text-light">Witaj, <?= e($uzytkownik) ?></span>
          <form method="post" action="wyloguj.php" class="d-flex">
            <?= csrf_pole() ?>
            <button type="submit" class="btn btn-outline-light btn-sm">Wyloguj</button>
          </form>
        </div>
      </div>
    </nav>

    <main class="container my-4">
      <h1 class="h3 mb-4">Usługi</h1>

      <?php if ($komunikat !== ''): ?>
        <div class="alert alert-success" role="status"><?= e($komunikat) ?></div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th scope="col">Usługa</th>
                  <th scope="col">Cena</th>
                  <th scope="col">Akcje</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($uslugi)): ?>
                  <tr>
                    <td colspan="3">Brak usług.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($uslugi as $usluga): ?>
                    <tr>
                      <td><?= e($usluga['nazwa']) ?></td>
                      <td><?= (int) $usluga['cena'] ?> zł</td>
                      <td>
                        <form method="post" action="admin.php" class="d-inline">
                          <input type="hidden" name="akcja" value="usun">
                          <input type="hidden" name="id" value="<?= (int) $usluga['id'] ?>">
                          <?= csrf_pole() ?>
                          <button type="submit" class="btn btn-sm btn-outline-danger">Usuń</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="col-lg-4">
          <h2 class="h5 mb-3">Dodaj usługę</h2>

          <?php foreach ($bledy as $blad): ?>
            <div class="alert alert-danger" role="alert"><?= e($blad) ?></div>
          <?php endforeach; ?>

          <form method="post" action="admin.php">
            <input type="hidden" name="akcja" value="dodaj">
            <?= csrf_pole() ?>

            <div class="mb-3">
              <label for="nazwa" class="form-label">Nazwa</label>
              <input id="nazwa" name="nazwa" type="text" class="form-control" required maxlength="100" value="<?= e($nazwaWartosc) ?>">
            </div>

            <div class="mb-3">
              <label for="cena" class="form-label">Cena (zł)</label>
              <input id="cena" name="cena" type="number" class="form-control" min="1" max="100000" required value="<?= e($cenaWartosc) ?>">
            </div>

            <button type="submit" class="btn btn-primary">Dodaj</button>
          </form>
        </div>
      </div>
    </main>
  </body>
</html>
