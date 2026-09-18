# Amanpoll

Amanpoll adalah platform CMMS / Asset & Maintenance Management multi-industri.

## Stack

- Laravel 13 (PHP 8.3+, PHP 8.4 direkomendasikan)
- MySQL 8
- Inertia.js + React + TypeScript
- Tailwind CSS 4 + shadcn/ui
- Queue database untuk kompatibilitas shared hosting
- Scheduler Laravel melalui Cron hPanel
- Penyimpanan local/private secara default; S3-compatible opsional

## Development lokal

```bash
composer install
npm install
npm run dev
php artisan serve
php artisan queue:work database
```

## Production Niagahoster Business

Lihat `deploy/niagahoster/DEPLOY.md`. Build Vite dibuat sebelum upload/deploy. Production tidak bergantung pada Redis, Horizon, Reverb, Docker, Supervisor, atau Octane.

Schema utama: `database/schema/Amanpoll_Database_MySQL.sql`.
