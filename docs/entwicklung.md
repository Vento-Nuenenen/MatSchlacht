# Entwicklung

```bash
composer test          # Pint, PHPStan und Tests
php artisan test       # nur Tests
composer lint          # PHP-Code formatieren
composer types:check   # PHPStan
npm run format         # Frontend formatieren (Prettier)
npm run lint           # JavaScript prüfen (ESLint)
```

## Browser-Tests

Die End-to-End-Tests in `tests/Browser` prüfen die wichtigsten Abläufe in einem echten Browser, auch in Smartphone- und Tablet-Grösse. Sie ergänzen die Feature-Tests und laufen weder mit `php artisan test` noch in den GitHub-Workflows, sondern nur von Hand:

```bash
npx playwright install chromium   # einmalig
npm run build                     # die Tests nutzen die gebauten Assets
composer test:browser             # in Chromium
composer test:browser -- --browser firefox
```

Schlägt ein Test fehl, liegt ein Screenshot in `tests/Browser/Screenshots`. Echte Datei-Uploads lassen sich mit dem Test-Server des Plugins nicht prüfen, sie sind in den Feature-Tests abgedeckt.

## Sprachen und Übersetzungen

Die App ist auf Deutsch. Alle Texte laufen über `__()`, der deutsche Text ist dabei selbst der Schlüssel. Fehlt eine Übersetzung, erscheint also der deutsche Text. Die Sprache kommt aus dem Benutzerkonto, sonst aus dem Browser, sonst aus `APP_LOCALE`. Mails gehen in der Sprache des Empfängers oder der Empfängerin raus.

Für eine weitere Sprache, zum Beispiel Französisch:

1. `php artisan lang:check fr --write` legt `lang/fr.json` mit allen Texten an. Die Werte sind leer und müssen übersetzt werden.
2. `lang/de/*.php` nach `lang/fr/` kopieren und übersetzen. Das sind die Meldungen von Laravel.
3. Die Sprache in `config/app.php` unter `available_locales` eintragen. Ab zwei Sprachen erscheint die Auswahl im Benutzermenü.

`php artisan lang:check fr` zeigt danach jederzeit, welche Texte noch fehlen.


## Badges

- **Tests:** PHPStan und Pest auf PHP 8.4 und 8.5 (`.github/workflows/tests.yml`)
- **Code-Qualität:** Pint, Prettier und ESLint im Prüfmodus (`.github/workflows/lint.yml`)
- **Coverage:** Zeilenabdeckung von `app/`, gemessen im Workflow `tests.yml`

Die Workflows erzeugen die Badges selbst: Bei jedem Push auf `master` legen sie die SVGs auf dem Branch `badges` ab.
