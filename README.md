# Muzeum Książki Artystycznej

Lekka aplikacja `PHP + PDO + SQL` dla muzeum książki artystycznej. Projekt zawiera:

- publiczną stronę z nawigacją, stroną główną, listingami i detalami
- mały panel CMS z logowaniem, treściami, mediami i ustawieniami
- migracje SQL dla `MySQL` i `SQLite`
- seeder z przykładowymi danymi

## Wymagania

- `PHP 8.2+`
- rozszerzenie `pdo_mysql`
- dostęp do bazy `MySQL/MariaDB`

## Szybki start lokalny

Projekt domyślnie działa na `MySQL`.

```bash
export DB_DRIVER=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=museum_mka
export DB_USERNAME=root
export DB_PASSWORD=
export APP_BASE_URL=http://127.0.0.1:8088

php bin/migrate.php
php bin/seed.php
php -S 127.0.0.1:8088 public/router.php
```

Strona: [http://127.0.0.1:8088/pl](http://127.0.0.1:8088/pl)

CMS: [http://127.0.0.1:8088/admin/login](http://127.0.0.1:8088/admin/login)

Domyślny login:

- `admin@mka.local`
- `Admin!123`

## Konfiguracja MySQL

Domyślna konfiguracja zakłada:

- `DB_DRIVER=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=museum_mka`
- `DB_USERNAME=root`
- `DB_PASSWORD=` (puste)

Jeśli Twoje środowisko różni się od powyższego, ustaw zmienne środowiskowe przed uruchomieniem migracji:

```bash
export DB_DRIVER=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=museum_mka
export DB_USERNAME=twoj_uzytkownik
export DB_PASSWORD=twoje_haslo
export APP_BASE_URL=https://twoja-domena.pl
```

Potem:

```bash
php bin/migrate.php
php bin/seed.php
```

## Opcjonalny fallback SQLite

Jeśli chcesz uruchomić projekt bez lokalnego serwera MySQL, możesz tymczasowo przełączyć się na SQLite:

```bash
export DB_DRIVER=sqlite
export DB_DATABASE=/pelna/sciezka/do/app.sqlite
php bin/migrate.php
php bin/seed.php
```

## Struktura

- `public/` front controller, statyczne assety, router dev-servera
- `src/` aplikacja, kontrolery, repozytoria, serwisy
- `views/` szablony publiczne i admin
- `database/migrations/` migracje per driver
- `../upload/` lokalne uploady
- `storage/database/` lokalna baza SQLite

## Bundle pod FTP

Docelowy układ FTP:

- `public_html/` do wrzucenia jako katalog publiczny domeny
- `mka-app/` do wrzucenia obok `public_html/`
- `upload/` do wrzucenia obok `public_html/` i `mka-app/`

Katalog `upload/` jest oddzielony od aplikacji, żeby pliki wgrywane przez CMS nie były nadpisywane przy aktualizacji `public_html/` i `mka-app/` przez FTP.
