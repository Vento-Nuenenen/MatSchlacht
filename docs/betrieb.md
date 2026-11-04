# Betrieb

Vor dem Produktivbetrieb:

- In der `.env` `APP_ENV=production`, `APP_DEBUG=false` und die richtige `APP_URL` setzen
- Die App nur über HTTPS ausliefern
- Mailversand konfigurieren und mit `php artisan mail:test` prüfen
- `php artisan migrate --force` ohne `--seed` ausführen, damit das Entwicklungskonto nicht angelegt wird
- Backups für die Datenbank und den gewählten Dateispeicher einrichten

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
