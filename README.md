# Szprycha — pracownia rowerowa

Prosta, responsywna strona pracowni rowerowej. Zawiera mobilne menu, cennik i własne grafiki SVG.

- `index.html` — wersja statyczna, nadaje się do publikacji na GitHub Pages.
- `index.php` — wersja PHP z dynamicznym rokiem w stopce i cennikiem generowanym z danych.

Podgląd wersji PHP: w terminalu uruchom `php -S 0.0.0.0:8000` i otwórz `http://localhost:8000/index.php`. GitHub Pages nie uruchamia PHP.

## Lab 3 — mini-CMS

1. `php setup.php` — utworzy bazę, doda cztery usługi i konto administratora. Skrypt zapyta o hasło admina (min. 12 znaków, wpisywane znaki nie są pokazywane); alternatywnie podaj je w zmiennej `ADMIN_HASLO`.
2. `php -S 0.0.0.0:8000 router.php` — uruchomi serwer deweloperski.
3. Strona: `/`, panel: `/login.php`, login: `admin`.

Baza leży w katalogu `dane/` i nie trafia do repozytorium.
