# Installation

## Voraussetzungen

- PHP 8.4 oder neuer (getestet mit 8.4 und 8.5)
- Composer
- Node.js 24
- PostgreSQL (Standard in `.env.example`)

## Lokale Installation

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

`composer dev` startet Webserver, Queue-Worker, Log-Ausgabe und Vite.

> [!CAUTION]
> Der Seeder legt für die lokale Entwicklung das Konto `admin@example.com` mit dem Passwort `password` an. Auf einem öffentlich erreichbaren Server darf dieses Konto nicht existieren.
