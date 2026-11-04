# MatSchlacht

[![Tests](../badges/tests.svg)](https://github.com/Vento-Nuenenen/MatSchlacht/actions/workflows/tests.yml)
[![Code-Qualität](../badges/quality.svg)](https://github.com/Vento-Nuenenen/MatSchlacht/actions/workflows/lint.yml)
[![PHPStan Level 7](https://img.shields.io/badge/PHPStan-Level%207-2a5ea7)](phpstan.neon)
[![Coverage](../badges/coverage.svg)](https://github.com/Vento-Nuenenen/MatSchlacht/actions/workflows/tests.yml)

Die Materialverwaltung der Pfadi Nünenen: Material erfassen, für Lager und Anlässe bestellen, bereitstellen und wieder zurücknehmen. Die App läuft auf Smartphone, Tablet und Desktop, im hellen und im dunklen Modus.

## Funktionen

- **Bestellungen:** vom Entwurf über Bestätigung und Ausgabe bis zur Rückgabe, mit Auftrags-, Retouren- und Packliste als PDF
- **Material:** Materialgruppen, Kollektionen, Inhaltslisten und Datei-Anhänge
- **Lagerhaltung:** Lagerort › Lagerraum › Lagerregal › Regalebene › Lagerplatz mit Bestand pro Platz und druckbaren Etiketten mit QR-Code
- **Beschaffung und Wartung:** Beschaffungen mit Belegen, Wartungsprotokoll, Intervalle und Erinnerungsmails
- **Anmeldung:** Passwort, Microsoft Azure und Hitobito (z. B. MiData), mit Rollen und Berechtigungen pro Einheit
- **Dateispeicher:** Amazon S3 und S3-kompatible Dienste, lokaler Server, SFTP, FTP, SharePoint/OneDrive, Nextcloud, Dropbox und Google Drive

Die meisten Funktionen sind Komponenten, die sich unter **Einstellungen** ein- und ausschalten und konfigurieren lassen. Dort werden auch die Hauptfarbe und der Dateispeicher gewählt. Lagerhaltung, Beschaffung und Wartung sind standardmässig aus.

## Voraussetzungen

- PHP 8.4 oder neuer (getestet mit 8.4 und 8.5)
- Composer
- Node.js 24
- PostgreSQL (Standard in `.env.example`)

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

In der `.env` die Datenbank eintragen, danach:

```bash
php artisan migrate --seed
npm run build
composer dev
```

`composer dev` startet Webserver, Queue-Worker, Log-Ausgabe und Vite. Der Seeder legt für die lokale Entwicklung das Konto `admin@example.com` mit dem Passwort `password` an. In Produktion nicht verwenden.

## Betrieb

Auf dem Server braucht die App neben dem Webserver:

- einen **Queue-Worker** (`php artisan queue:work`), weil Mails über die Queue verschickt werden
- den **Scheduler** als Cronjob, für die wöchentliche Wartungs-Erinnerung:

  ```cron
  * * * * * cd /pfad/zur/app && php artisan schedule:run >> /dev/null 2>&1
  ```

Die gebauten Assets liegen in `public/build` und sind eingecheckt. Nach Änderungen an Styles oder JavaScript also `npm run build` ausführen und das Ergebnis committen.

Nützliche Befehle:

| Befehl | Zweck |
| --- | --- |
| `php artisan mail:test {email?}` | Testmail verschicken |
| `php artisan maintenance:remind` | Erinnerung für fällige Wartungen sofort senden |
| `php artisan attachments:transfer {from} {to?} {--delete-source}` | Dateien zu einem anderen Speicher-Provider umziehen |

## Entwicklung

```bash
composer test          # Pint, PHPStan und Tests
php artisan test       # nur Tests
composer lint          # PHP-Code formatieren
composer types:check   # PHPStan
npm run format         # Frontend formatieren (Prettier)
npm run lint           # JavaScript prüfen (ESLint)
```

## Badges

- **Tests:** PHPStan und Pest auf PHP 8.4 und 8.5 (`.github/workflows/tests.yml`)
- **Code-Qualität:** Pint, Prettier und ESLint im Prüfmodus (`.github/workflows/lint.yml`)
- **Coverage:** Zeilenabdeckung von `app/`, gemessen im Workflow `tests.yml`

Weil das Repo privat ist, erzeugen die Workflows die Badges selbst: Bei jedem Push auf `master` legen sie die SVGs auf dem Branch `badges` ab.
