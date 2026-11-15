<?php
declare(strict_types=1);

/**
 * Połączenie z bazą SQLite.
 * Plik bazy leży w katalogu dane/, który nie jest dostępny z przeglądarki.
 */
$pdo = new PDO('sqlite:' . __DIR__ . '/dane/szprycha.sqlite');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
