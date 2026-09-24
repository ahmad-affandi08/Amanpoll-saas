# Runbook Deployment Amanpoll

Dokumen ini dipakai saat memasang atau memperbarui Amanpoll di Niagahoster
Business. Urutannya adalah urutan tindakan. Pemulihan bila terjadi kerusakan
data dibahas terpisah di [RUNBOOK-PEMULIHAN.md](RUNBOOK-PEMULIHAN.md).

## 1. Yang harus ada di server

| Kebutuhan | Nilai | Catatan |
| --- | --- | --- |
| PHP | 8.3 atau lebih baru | `composer.json` menuntut `^8.3` |
| Ekstensi PHP | bawaan Laravel, ditambah `zlib` | `composer.json` tidak mendeklarasikan `ext-*`; `zlib` dipakai `LayananCadangan` untuk membaca isi dump terkompresi |
| Basis data | MySQL atau MariaDB | SQLite tidak dipakai di produksi; `lockForUpdate` tidak berlaku di sana |
| Node | hanya di mesin build | Server produksi tidak perlu Node bila `public/build` diunggah |
| Akses shell | ya | Dibutuhkan untuk `artisan` dan cron |
| `mysqldump` dan `mysql` | ada di PATH | Dipakai pencadangan harian; jalurnya dapat diatur lewat `AMANPOLL_CADANGAN_*` |

Amanpoll sengaja tidak bergantung pada daemon permanen. Antrian dijalankan oleh
cron, bukan supervisor (PRD 12).

## 2. Menyiapkan host

Seluruh host dilayani **satu instalasi yang sama**: subdomain dibuat di hPanel
dengan document root yang sama seperti domain utama, sehingga tidak ada
duplikasi source, `vendor`, build, maupun `.env`.

| Peran | Contoh | Variabel |
| --- | --- | --- |
| Dashboard tenant | `app.amanpoll.id` | `AMANPOLL_DOMAIN_DASHBOARD` |
| Situs publik | `amanpoll.id` | `AMANPOLL_DOMAIN_PUBLIK` |
| Portal partner | `partner.amanpoll.id` | `AMANPOLL_DOMAIN_PARTNER` |

Host yang variabelnya dikosongkan tidak akan terdaftar rutenya sama sekali.
Itu disengaja: periksa dengan `php artisan route:list --path=<jalur>` bila
sebuah halaman tampak hilang setelah deploy.

`SESSION_DOMAIN` diisi domain induk dengan titik di depan (`.amanpoll.id`)
supaya sesi berlaku lintas subdomain. Tanpa itu, pengguna yang berpindah dari
situs publik ke dashboard akan terlempar ke halaman masuk.

## 3. Pemasangan pertama

```bash
git clone <repo> && cd amanpoll
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Isi `.env` — minimal yang tidak boleh memakai bawaan:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.amanpoll.id
SESSION_DOMAIN=.amanpoll.id
SESSION_SECURE_COOKIE=true

DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…

AMANPOLL_DOMAIN_DASHBOARD=app.amanpoll.id
AMANPOLL_DOMAIN_PUBLIK=amanpoll.id
AMANPOLL_DOMAIN_PARTNER=partner.amanpoll.id

MAIL_MAILER=amanpoll            # penyedia email dipilih di konsol platform
MAIL_FROM_ADDRESS=noreply@amanpoll.id

AMANPOLL_CADANGAN_DISK_LUAR=cadangan_luar
AMANPOLL_CADANGAN_LUAR_KEY=…
AMANPOLL_CADANGAN_LUAR_SECRET=…
AMANPOLL_CADANGAN_LUAR_BUCKET=…
AMANPOLL_CADANGAN_LUAR_ENDPOINT=https://<ID-AKUN>.r2.cloudflarestorage.com
```

Template lengkapnya `deploy/niagahoster/.env.production.example`.

Disk luar cadangan menampung salinan cadangan di luar server; tanpa itu
cadangan hilang bersama servernya. Cloudflare R2 cocok untuk ini (egress gratis,
sehingga verifikasi checksum yang membaca ulang salinan tidak berbiaya): buat
bucket privat, lalu token API R2 dengan izin *Object Read & Write* hanya untuk
bucket itu. Region `auto` dan path-style sudah menjadi bawaan disk
`cadangan_luar`. Simpan kredensial ini juga di luar server — pemulihan setelah
server hilang membutuhkannya.

`APP_DEBUG=false` bukan sekadar kerapian: dengan `true`, halaman galat
menampilkan isi `.env` kepada siapa pun yang memicunya.

Lalu:

```bash
php artisan migrate --force
php artisan storage:link      # /storage → storage/app/public
php artisan db:seed --class=IzinSeeder --force
php artisan db:seed --class=FiturPaketSeeder --force
php artisan db:seed --class=FiturPlatformSeeder --force
php artisan db:seed --class=TahapPipelineSeeder --force
```

Keempat seeder itu wajib. Isinya bukan data contoh melainkan **kunci yang
dirujuk kode program**: kode izin seperti `Aset.Buat` dipakai pemeriksaan
otorisasi, dan `FiturPaket` dicari lewat kodenya saat paket langganan disusun.
Tanpa seeder itu, pemeriksaan izin menolak semua orang.

Keempatnya dipanggil satu per satu agar jelas apa yang masuk. `php artisan
db:seed --force` polos juga aman sekarang: `DatabaseSeeder` melewati
`DemoAwalSeeder` di produksi dan tetap keluar dengan kode 0, sementara
`DemoAwalSeeder` sendiri menolak berjalan di sana meski dipanggil langsung
lewat `--class`. Sebelumnya perintah itu menanam organisasi, aset, dan akun
Super Admin contoh berkata sandi yang dapat ditebak ke dalam data sungguhan;
yang menahannya hanya paragraf ini, dan paragraf tidak menahan siapa pun.
Penjaganya kini ada di kode dan ditegakkan
`tests/Feature/Platform/SeederDataContohTest.php`.

Build aset dijalankan di mesin build, lalu `public/build/` diunggah:

```bash
npm ci && npm run build
```

`public/build` tidak masuk git, jadi ia harus ikut diunggah setiap kali ada
perubahan frontend.

## 4. Cron

Satu baris di hPanel sudah cukup; sisanya diatur Laravel scheduler:

```cron
* * * * * cd /home/USER/amanpoll && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan puluhan jadwal (daftar lengkapnya: `php artisan schedule:list`), di antaranya:

| Jadwal | Jalan | Tugas |
| --- | --- | --- |
| `queue:work database --queue=high,default` | tiap menit | job pendek, maksimal 50 detik per jalan |
| `queue:work database-panjang --queue=low --max-jobs=1` | tiap 3 menit | ekspor laporan, satu job (≤ 5 menit) per jalan |
| `cadangan:jalankan` | 01:45 | cadangan basis data dan berkas, disalin ke disk luar |
| `keluhan:proses-eskalasi-sla` | tiap jam | eskalasi SLA |
| `pemeliharaan:jadwalkan-preventif` | harian | jadwal preventif |
| `kalibrasi:kirim-peringatan-jatuh-tempo` | harian | peringatan kalibrasi |
| `kontrak:kirim-peringatan-berakhir` | harian | kontrak akan berakhir |
| `kepatuhan:kirim-peringatan-kedaluwarsa` | harian | sertifikat kedaluwarsa |
| `outbox:proses` dan `panggilan-balik:kirim-ulang` | tiap menit | integrasi keluar dan percobaan ulang webhook |

Antrean dibagi dua pekerja karena `retry_after` dimiliki koneksi, bukan job:
ekspor yang berjalan lebih lama dari `retry_after` diambil ulang pekerja lain
dan dikerjakan dobel. Job pendek memakai koneksi `database`
(`DB_QUEUE_RETRY_AFTER=90`, pekerja `--timeout=45`), ekspor memakai koneksi
`database-panjang` pada tabel yang sama (`DB_QUEUE_PANJANG_RETRY_AFTER=420`,
pekerja `--timeout=310`, kunci 6 menit). Ekspor yang habis waktu langsung
gagal, tidak diulang. Kedua pekerja dan `cadangan:jalankan` berjalan di latar
supaya jadwal lain pada menit yang sama tidak menunggu. Batas waktu job hanya
ditegakkan bila ekstensi `pcntl` ada di PHP CLI; periksa dengan
`php -m | grep pcntl`.

Tiap jadwal menyebut sendiri masa berlaku kuncinya dan tidak memakai bawaan
`withoutOverlapping()` yang 1440 menit. Shared hosting rutin membunuh proses
yang kelamaan, dan kunci yang ditinggalkan proses mati akan menahan jadwal
berikutnya selama kunci belum kedaluwarsa.

## 5. Memperbarui versi

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Unggah `public/build/` yang baru **sebelum** `php artisan up` bila ada perubahan
frontend. Manifest Vite dan berkas aset harus cocok; bila tidak, halaman gagal
dengan `Unable to locate file in Vite manifest`.

Jalankan `php artisan config:clear` lebih dulu setiap kali `.env` berubah —
`config:cache` membekukan nilainya, dan perubahan `.env` setelah itu diabaikan
diam-diam.

## 6. Memastikan deployment berhasil

```bash
php artisan about                     # versi, cache, koneksi basis data
php artisan route:list --except-vendor | head
php artisan cadangan:daftar           # pencadangan dapat menulis
php artisan cadangan:daftar --luar    # disk luar cadangan terjangkau
php artisan schedule:list             # scheduler terbaca
curl -fsS -H 'Accept: application/json' https://<host>/up   # {"status":"up"}
```

`/up` adalah satu-satunya endpoint yang aman dipanggil pemantau luar: tanpa
autentikasi, dan badan responsnya hanya `up`/`down`. Ia menjawab 500 bila salah
satu dari tiga ini mati — koneksi basis data, direktori tulis
(`storage/framework/*`, `storage/logs`, `bootstrap/cache`; isinya diabaikan git,
jadi unggahan baru kerap sampai tanpa direktori itu), atau perjalanan bolak-balik
cache. Nama pemeriksaan yang gagal hanya ditulis ke `storage/logs`, tidak ikut ke
respons. Pemeriksaannya ada di `app/Core/Kesehatan/PeriksaKesehatanSistem.php`;
bawaan Laravel tanpa itu hanya membuktikan PHP hidup dan tetap menjawab 200 di
atas basis data yang kredensialnya salah.

Rute `/up` didaftarkan framework, jadi ia **tidak** muncul di
`route:list --except-vendor` di atas. Ketiadaannya di sana bukan tanda ia hilang.

Lalu buka satu halaman tenant dan satu halaman publik. Kalau situs publik
menampilkan 404 sementara dashboard normal, penyebabnya hampir selalu
`AMANPOLL_DOMAIN_PUBLIK` yang kosong atau tidak cocok dengan host yang diakses.

## 7. Yang tidak dijanjikan dokumen ini

- Tidak ada deployment tanpa jeda. `php artisan down` menutup aplikasi selama
  migrasi berjalan.
- Tidak ada rollback otomatis. Mundur ke versi sebelumnya berarti `git checkout`
  tag lama lalu memulihkan basis data dari cadangan bila migrasinya merusak data;
  migrasi `down()` tidak diuji.
- Tidak ada pipeline CI. Gerbang yang disebut di CLAUDE.md dijalankan manusia
  sebelum mendorong.
