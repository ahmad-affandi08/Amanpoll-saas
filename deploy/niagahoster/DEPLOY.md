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

`php artisan migrate --force` (langkah 7 di atas) sudah membangun seluruh schema Amanpoll — 136 tabel domain, 4 view operasional, dan tabel infrastruktur Laravel — langsung dari migration di `database/migrations/`. Tidak perlu import manual SQL untuk instalasi baru.

`database/schema/Amanpoll_Schema_Hosting.sql` tetap disimpan sebagai dokumentasi/rujukan skema yang mudah dibaca dan sebagai jalur alternatif bila suatu saat `php artisan migrate` tidak dapat dijalankan (mis. akses SSH terbatas), dengan tetap menjalankan `php artisan migrate --force` sesudahnya untuk melengkapi tabel infrastruktur Laravel yang tidak ada di file SQL tersebut.

Jalankan empat seeder wajib satu kali. Isinya kunci yang dirujuk kode program, bukan data contoh — tanpa `IzinSeeder`, pemeriksaan otorisasi menolak semua orang, dan tanpa `FiturPaketSeeder` master fitur di konsol admin kosong sehingga paket langganan tidak dapat disusun.

```bash
php artisan db:seed --class=Database\\Seeders\\IzinSeeder --force
php artisan db:seed --class=Database\\Seeders\\FiturPaketSeeder --force
php artisan db:seed --class=Database\\Seeders\\FiturPlatformSeeder --force
php artisan db:seed --class=Database\\Seeders\\TahapPipelineSeeder --force
```

`php artisan db:seed --force` polos juga menyemai keempatnya dan melewati data contohnya di produksi.

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
