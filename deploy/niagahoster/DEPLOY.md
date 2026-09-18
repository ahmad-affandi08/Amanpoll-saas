# Deploy Amanpoll ke Niagahoster Business

Target ini sengaja **tidak bergantung pada daemon**. Production menggunakan queue database yang diproses worker pendek melalui Laravel Scheduler + Cron hPanel.

## Struktur direktori

```text
/home/u123456789/domains/domain-anda.tld/
├── amanpoll/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   └── build/          # hasil npm run build
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   └── .env
└── public_html/
    ├── build/              # salin public/build ke sini
    ├── index.php
    ├── .htaccess
    ├── favicon.ico
    └── robots.txt
```

Jangan meletakkan `.env`, `vendor`, `storage`, atau source Laravel lain di `public_html`.

## Langkah deployment pertama

1. Di hPanel, buat database MySQL dan user database.
2. Pilih PHP **8.4 jika tersedia**; Laravel 13 minimum PHP 8.3.
3. Upload source ke folder `amanpoll/` satu tingkat di atas `public_html/`.
4. Salin template `deploy/niagahoster/public_html/*` ke `public_html/`.
5. Salin hasil `public/build/` ke `public_html/build/`.
6. Buat `.env` production dari `deploy/niagahoster/.env.production.example`.
7. Via SSH, jalankan Composer 2 dan Artisan.

```bash
cd ~/domains/domain-anda.tld/amanpoll
composer2 install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan optimize
```

Jika Composer melalui SSH memakai versi PHP berbeda dari website, panggil binary PHP yang benar sesuai panel. Contoh umum:

```bash
/opt/alt/php84/usr/bin/php /usr/local/bin/composer2 install --no-dev --prefer-dist --optimize-autoloader
```

## Database utama

Import `database/schema/Amanpoll_Schema_Hosting.sql` satu kali ke database production (phpMyAdmin atau CLI), kemudian jalankan `php artisan migrate --force` untuk tabel infrastruktur Laravel dan migration berikutnya.

## Cron

Buat cron hPanel **setiap menit**. Jalankan `/bin/sh .../deploy/niagahoster/cron.sh`; template command ada di `deploy/niagahoster/cron.txt`. Pendekatan file .sh` juga menghindari masalah karakter khusus pada command cron hPanel.

Scheduler Amanpoll akan menjalankan worker database dengan `--stop-when-empty`, jadi tidak memerlukan Supervisor/Horizon.

## Frontend

Build production di mesin development/CI:

```bash
npm ci
npm run build
```

Upload hasil `public/build`. Jangan bergantung pada Vite dev server di hosting.

## Permission

Pastikan proses PHP dapat menulis ke:

- `storage/`
- `bootstrap/cache/`

Gunakan permission paling ketat yang tetap bekerja; jangan menjadikan seluruh project 777.

## Yang sengaja tidak dipakai di shared hosting

- Docker production
- Laravel Horizon
- Laravel Reverb self-hosted
- Supervisor/systemd
- Octane / FrankenPHP long-running server
- Redis sebagai requirement

Jika suatu hari Amanpoll pindah ke VPS/Cloud, queue/realtime dapat dinaikkan ke Redis + Horizon + Reverb tanpa mengubah domain model.
