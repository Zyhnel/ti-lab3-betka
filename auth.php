<?php
declare(strict_types=1);

require_once __DIR__ . '/funkcje.php';
require_once __DIR__ . '/sesja.php';

if (empty($_SESSION['uzytkownik'])) {
    header('Location: login.php');
    exit;
}
