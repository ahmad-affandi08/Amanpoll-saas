<p align="center">
  <img src="public/images/branding/amanpoll-icon.png" alt="Amanpoll" width="88">
</p>

<h1 align="center">Amanpoll</h1>

<p align="center">
  Manajemen aset dan pemeliharaan (CMMS) multi-industri berbasis SaaS.<br>
  Untuk pabrik, gedung perkantoran, gudang, kampus, hotel, rumah sakit, dan organisasi lain yang merawat banyak aset.
</p>

---

## Daftar isi

- [Tentang Amanpoll](#tentang-amanpoll)
- [Fitur utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Kebutuhan sistem](#kebutuhan-sistem)
- [Menjalankan di komputer lokal](#menjalankan-di-komputer-lokal)
- [Akun demo](#akun-demo)
- [Konfigurasi penting](#konfigurasi-penting)
- [Antrian dan jadwal (cron)](#antrian-dan-jadwal-cron)
- [Perintah artisan khusus](#perintah-artisan-khusus)
- [Struktur proyek](#struktur-proyek)
- [Pengujian dan pemeriksaan kode](#pengujian-dan-pemeriksaan-kode)
- [Deployment ke Niagahoster](#deployment-ke-niagahoster)
- [Dokumentasi lain](#dokumentasi-lain)
- [Aturan kontribusi](#aturan-kontribusi)

---

## Tentang Amanpoll

Amanpoll mencatat seluruh siklus aset organisasi: pendaftaran aset dan label QR, keluhan kerusakan, perintah kerja, pemeliharaan preventif, kalibrasi, suku cadang, pengadaan, kontrak, sampai laporan. Satu instalasi melayani banyak organisasi (multi-tenant). Setiap organisasi hanya melihat datanya sendiri, dan di dalam organisasi, akses dapat dibatasi per unit, ruangan, atau bagian pemeliharaan.

Produk terdiri dari tiga wajah:

| Wajah | Untuk siapa | Isi |
| --- | --- | --- |
| **Dasbor web** | Admin, manajer aset, koordinator, staf gudang, pengadaan, auditor | Seluruh modul operasional, laporan, dan pengaturan organisasi |
| **Mode Lapangan** | Teknisi dan pelapor (staf lokasi) | Aplikasi bergaya HP (PWA): tiket kerja, pindai QR, lapor kerusakan, pantau laporan. Tetap berjalan saat sinyal putus |
| **Konsol platform** | Tim pengelola SaaS | Organisasi, langganan, pemasaran, prospek, partner |

## Fitur utama

**Aset dan organisasi**
- Registri aset dengan kategori bertingkat, model, lokasi, penanggung jawab, garansi, meter, lampiran, tag, dan kolom kustom.
- Label QR per aset dan cetak label massal. Memindai QR membuka layar yang sesuai peran (teknisi, pelapor, atau dasbor).
- Impor aset dari CSV/XLSX dengan pratinjau galat per baris (semua atau tidak sama sekali).
- Siklus aset: mutasi, peminjaman, penghapusan, kelayakan, kodefikasi, katalog ASPAK.
- **Unit Pengelola**: beberapa bagian pemeliharaan dalam satu organisasi (mis. IPSRS dan IT, atau Engineering dan IT). Tiap bagian punya antrian keluhan, teknisi, gudang, dan laporannya sendiri.

**Pemeliharaan**
- Keluhan dengan kategori, SLA, eskalasi, dan pengalihan antar bagian.
- Perintah kerja: penugasan teknisi, checklist, suku cadang, biaya, waktu kerja, tanda tangan penerima (bisa diwajibkan per organisasi), verifikasi.
- Pemeliharaan preventif terjadwal, inspeksi, dan kalibrasi dengan titik ukur.

**Persediaan, pengadaan, dan kontrak**
- Gudang, rak, stok, mutasi, reservasi, dan peringatan stok minimum.
- Rencana pengadaan, anggaran, permintaan dan pesanan pembelian, penerimaan, tagihan penyedia.
- Kontrak layanan penyedia dan sertifikasi kepatuhan dengan pengingat kedaluwarsa.

**Platform**
- Peran dan izin, lingkup akses per unit/ruangan, mesin persetujuan, notifikasi, audit, pencarian global (Ctrl+K).
- Laporan dan dasbor dengan filter tanggal, unit organisasi, lokasi, dan unit pengelola; ekspor PDF/CSV/XLSX berkop.
- Integrasi: kunci API, webhook, outbox, idempotensi.
- SaaS: paket langganan, trial, pemasaran (halaman publik, CMS, kampanye, email, WhatsApp, referral, partner, attribution).
- Login cukup email dan kata sandi. Bila satu email terdaftar di beberapa organisasi, pengguna memilih organisasinya setelah kata sandi terbukti benar.

## Teknologi

| Lapisan | Teknologi |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ (8.4 direkomendasikan) |
| Basis data | MySQL 8 / MariaDB 10.6+ |
| Frontend | Inertia.js v3, React 19, TypeScript |
| Tampilan | Tailwind CSS 4, komponen shadcn/ui, ikon Lucide, ikon 3D Fluent Emoji (MIT) |
| Font | IBM Plex Sans & Mono (dasbor); Plus Jakarta Sans (Mode Lapangan dan halaman autentikasi) |
| Berkas | Penyimpanan lokal privat; S3-compatible opsional |
| Antrian | Driver `database`, diproses worker pendek dari scheduler (tanpa daemon) |
| PDF / Excel / QR | dompdf, OpenSpout, bacon-qr-code |
| Pengujian | PHPUnit, Larastan (PHPStan), Laravel Pint, Prettier, `tsc` |

Produksi sengaja **tidak** bergantung pada Redis, Horizon, Reverb, Octane, Docker, atau Supervisor, supaya bisa berjalan di shared hosting.

## Kebutuhan sistem

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `bcmath`, `fileinfo`, `openssl`
- Composer 2
- Node.js 20+ dan npm (hanya untuk membangun aset frontend)
- MySQL 8 atau MariaDB 10.6+
- `mysqldump` dan `mysql` di PATH bila fitur pencadangan dipakai

## Menjalankan di komputer lokal

```bash
git clone <url-repo> amanpoll
cd amanpoll

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Buat database kosong, lalu isi kredensialnya di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amanpoll
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi dan data awal. Di luar produksi, seeder juga membuat data demo:

```bash
php artisan migrate --seed
```

Jalankan aplikasi:

```bash
composer run dev          # server Laravel, Vite, dan proses pendukung sekaligus
```

atau secara terpisah:

```bash
php artisan serve
npm run dev
php artisan queue:work database
```

Buka **http://localhost:8000**.

> **Penting:** gunakan `localhost`, bukan `127.0.0.1`. Rute dipisahkan per host (`AMANPOLL_DOMAIN_DASHBOARD=localhost`), sehingga alamat lain menghasilkan 404.

Bila perubahan tampilan tidak muncul, pastikan `npm run dev` berjalan atau jalankan `npm run build`.

## Akun demo

Dibuat oleh `DemoAwalSeeder` (tidak dijalankan di produksi). Kata sandi semua akun adalah nilai `AMANPOLL_DEMO_KATA_SANDI` (bawaan `password`).

Perusahaan demo: **PT Sinar Nusantara Industri** (kode `SNI`), produsen kemasan plastik dengan kantor pusat di Jakarta, pabrik di Cikarang, dan gudang distribusi di Surabaya. Isinya 11 unit, 15 lokasi, 60 aset, 10 penyedia, 30 suku cadang di 3 gudang, serta riwayat operasional setahun terakhir: keluhan, perintah kerja, preventif, kalibrasi, pengadaan, kontrak, dan kepatuhan. Riwayat lengkap hanya terbentuk di basis data kosong:

```bash
php artisan migrate:fresh --seed
```

| Email | Peran | Mendarat di |
| --- | --- | --- |
| `admin@amanpoll.test` | Super Administrator | Dasbor |
| `manajer.aset@amanpoll.test` | Manajer Aset | Dasbor |
| `koordinator.teknik@amanpoll.test` | Koordinator Pemeliharaan (Teknik & Fasilitas) | Dasbor (antrian Teknik & Fasilitas) |
| `koordinator.it@amanpoll.test` | Koordinator Pemeliharaan (IT) | Dasbor (antrian IT) |
| `teknisi.teknik@amanpoll.test` | Teknisi mekanikal (Teknik & Fasilitas) | Mode Lapangan |
| `teknisi.listrik@amanpoll.test` | Teknisi elektrikal (Teknik & Fasilitas) | Mode Lapangan |
| `teknisi.it@amanpoll.test` | Teknisi IT | Mode Lapangan |
| `kalibrasi@amanpoll.test` | Petugas Kalibrasi | Dasbor |
| `gudang@amanpoll.test` | Operator Gudang | Dasbor |
| `pengadaan@amanpoll.test` | Staf Pengadaan | Dasbor |
| `penyetuju@amanpoll.test` | Penyetuju (Direktur Operasional) | Dasbor |
| `auditor@amanpoll.test` | Auditor | Dasbor |
| `pelapor@amanpoll.test` | Pelapor (operator produksi) | Mode Lapangan |
| `pelapor.kantor@amanpoll.test` | Pelapor (staf keuangan) | Mode Lapangan |

Admin konsol platform tidak dibuat seeder. Buat satu lewat tinker, lalu masuk di `/admin-platform/login`:

```bash
php artisan tinker --execute '\App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform::create(["Nama" => "Admin Platform", "Email" => "platform@amanpoll.test", "KataSandi" => "password", "Status" => "Aktif", "SuperAdmin" => true]);'
```

## Konfigurasi penting

Seluruh kunci ada di `.env.example` beserta penjelasannya. Yang paling sering diubah:

| Kunci | Fungsi |
| --- | --- |
| `AMANPOLL_DOMAIN_DASHBOARD` | Host sistem (login dan dasbor). Lokal: `localhost` |
| `AMANPOLL_DOMAIN_PUBLIK` | Host landing page dan halaman pemasaran. Kosong = root milik dasbor |
| `AMANPOLL_DOMAIN_PARTNER` | Host portal partner (opsional) |
| `AMANPOLL_DOMAIN_SKEMA` | `https` di produksi |
| `AMANPOLL_ZONA_WAKTU` | Zona bawaan organisasi baru. Waktu disimpan UTC; tampilan mengikuti zona organisasi |
| `AMANPOLL_MATA_UANG` | Mata uang bawaan (`IDR`) |
| `AMANPOLL_AUDIT_AKTIF` | Pencatatan audit |
| `AMANPOLL_QUEUE_CRON` | Worker antrian dijalankan dari scheduler (shared hosting) |
| `AMANPOLL_CADANGAN_*` | Disk, folder, retensi, dan biner untuk pencadangan |
| `AMANPOLL_DEMO_KATA_SANDI` | Kata sandi akun demo |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `local` (privat); S3 opsional |
| `MAIL_MAILER` | `log` di lokal; SMTP di produksi |
| `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE` | Diisi domain induk dan `true` di produksi agar sesi berlaku lintas subdomain |

Pengaturan per organisasi (SLA, notifikasi, ambang persetujuan, wajib tanda tangan penerima, dan lain-lain) diatur admin di halaman **Konfigurasi Organisasi**, bukan di `.env`.

## Antrian dan jadwal (cron)

Semua pekerjaan terjadwal didaftarkan di scheduler Laravel, termasuk worker antrian pendek (`queue:work --stop-when-empty` setiap menit). Cukup satu cron per menit:

```cron
* * * * * cd /path/ke/amanpoll && php artisan schedule:run >> /dev/null 2>&1
```

Lihat daftar lengkapnya dengan `php artisan schedule:list`. Isinya antara lain eskalasi SLA keluhan, penjadwalan preventif, peringatan kalibrasi/kontrak/kepatuhan, reservasi dan stok minimum, antrian sinkronisasi offline, outbox dan webhook, pencadangan harian, serta pekerjaan pemasaran (trial, email, WhatsApp, otomasi, referral).

## Perintah artisan khusus

| Perintah | Kegunaan |
| --- | --- |
| `platform:pasang-peran-awal` | Pasang peran bawaan ke organisasi |
| `platform:terapkan-peran-lapangan` | Terapkan katalog peran Mode Lapangan ke tenant lama (`--organisasi`, `--pratinjau`, `--paksa`) |
| `pemeliharaan:isi-unit-pengelola` | Isi unit pengelola yang kosong pada data lama (`--organisasi`, `--pratinjau`, `--paksa`) |
| `pemeliharaan:jadwalkan-preventif` | Buat perintah kerja preventif sesuai rencana |
| `keluhan:proses-eskalasi-sla` | Periksa dan eskalasi SLA keluhan |
| `sinkronisasi:proses-antrian` | Terapkan mutasi offline yang menunggu |
| `cadangan:jalankan` / `cadangan:daftar` / `cadangan:pulihkan` | Cadangkan, lihat, dan pulihkan basis data serta berkas unggahan |
| `outbox:proses`, `panggilan-balik:kirim-ulang` | Kirim peristiwa ke webhook dan ulangi yang gagal |
| `langganan:segarkan-status`, `langganan:rekonsiliasi` | Selaraskan status langganan dan tagihan |
| `pemasaran:*` | Pekerjaan pemasaran: trial, email, WhatsApp, otomasi, referral, metrik, reset demo |

`php artisan list` menampilkan semuanya beserta keterangannya.

## Struktur proyek

```text
app/
├── Core/            Fondasi lintas domain: izin & lingkup akses, organisasi, audit, entitas, konfigurasi
├── Domain/          Satu folder per bounded context (Aset, Pemeliharaan, Persediaan, Platform, Sinkronisasi, ...)
│   └── <Domain>/
│       ├── Application/     Actions (kasus bisnis) dan Services
│       ├── Domain/          Enum dan value object
│       ├── Http/            Controllers, Requests, Resources, Policies
│       ├── Infrastructure/  Model Eloquent, listener, integrasi
│       └── routes.php
├── Http/            Controller dan middleware lintas domain (autentikasi, pengarahan Mode Lapangan)
└── Shared/          Utilitas bersama (ekspor, impor, QR, pengecualian)
resources/js/
├── components/      Komponen UI bersama (ui, shared, data-table, grafik)
├── features/<Fitur>/  Halaman (pages), komponen, api.ts (semua URL), types.ts
├── layouts/         Kerangka dasbor dan Mode Lapangan
└── lib/             Klien HTTP, penyimpanan offline, utilitas
database/            Migrasi, seeder, factory
docs/                Dokumen produk, desain, ADR, runbook
tests/               Feature, Unit, Architecture
deploy/niagahoster/  Template dan skrip deployment shared hosting
```

Konvensi utama:
- Nama kelas, method, variabel, kolom, rute, dan teks antarmuka berbahasa Indonesia; kolom basis data PascalCase.
- Controller tipis. Logika bisnis ada di Action domain pemiliknya, dan otorisasi lewat Policy.
- Tenancy (`OrganisasiId`) dan lingkup akses berlaku otomatis lewat global scope model.
- Semua URL frontend ada di `api.ts` fitur masing-masing; permintaan non-Inertia lewat `@/lib/http`.

## Pengujian dan pemeriksaan kode

```bash
php artisan test --compact                    # seluruh test (butuh database uji MySQL)
php artisan test --compact --filter=NamaTest  # satu test
vendor/bin/phpstan analyse --memory-limit=2G  # analisis statis (jumlah galat tidak boleh bertambah)
vendor/bin/pint --dirty                       # format PHP
npm run typecheck                             # TypeScript
npm run format                                # format frontend (Prettier)
npm run build                                 # tsc + build Vite produksi
```

Test memakai database MySQL terpisah bernama `amanpoll_testing` (kredensialnya di `phpunit.xml`); buat database itu lebih dulu, lalu jalankan `DB_DATABASE=amanpoll_testing php artisan migrate`. Jangan menjalankan `npm run dev` saat test berjalan: berkas `public/hot` membuat test halaman gagal.

## Deployment ke Niagahoster

Panduan lengkap ada di [`deploy/niagahoster/DEPLOY.md`](deploy/niagahoster/DEPLOY.md), dengan daftar periksa di [`RELEASE-CHECKLIST.md`](deploy/niagahoster/RELEASE-CHECKLIST.md) dan runbook di [`docs/RUNBOOK-DEPLOYMENT.md`](docs/RUNBOOK-DEPLOYMENT.md). Ringkasnya:

1. Bangun frontend di komputer lokal atau CI: `npm ci && npm run build`.
2. Unggah source ke folder **di atas** `public_html` (mis. `~/domains/domain-anda/amanpoll/`). Jangan meletakkan `.env`, `vendor`, atau `storage` di `public_html`.
3. Isi `public_html` dengan template `deploy/niagahoster/public_html/` (index.php dan .htaccess), lalu salin **seluruh isi** `public/` selain `index.php` ke `public_html/`: `build/`, `images/` (logo dan ikon 3D), `icons/`, favicon, `sw.js`, `site.webmanifest`, `offline.html`, `robots.txt`.
4. Buat `.env` produksi dari `deploy/niagahoster/.env.production.example` (`APP_ENV=production`, `APP_DEBUG=false`, domain, SMTP, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE=true`).
5. Lewat SSH:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan db:seed --force      # hanya kunci wajib; data demo dilewati di produksi
   php artisan storage:link
   php artisan optimize
   ```
6. Pasang cron per menit di hPanel memakai `deploy/niagahoster/cron.sh` (lihat `cron.txt`).
7. Cadangkan basis data sebelum setiap perubahan skema.

## Dokumentasi lain

| Dokumen | Isi |
| --- | --- |
| [`docs/source-of-truth/PRD.md`](docs/source-of-truth/PRD.md) | Kebutuhan produk (sumber kebenaran) |
| [`docs/source-of-truth/DESIGN.md`](docs/source-of-truth/DESIGN.md) | Sistem desain, komponen, Mode Lapangan, halaman autentikasi |
| [`docs/source-of-truth/TASK.md`](docs/source-of-truth/TASK.md) | Rencana dan catatan pengerjaan per fase |
| [`docs/source-of-truth/MARKETING.md`](docs/source-of-truth/MARKETING.md) | Platform pemasaran dan growth |
| [`docs/ARSITEKTUR.md`](docs/ARSITEKTUR.md), [`docs/adr/`](docs/adr/) | Arsitektur dan keputusan desain teknis |
| [`docs/PETA-SCHEMA.md`](docs/PETA-SCHEMA.md) | Peta skema basis data |
| [`docs/RUNBOOK-PEMULIHAN.md`](docs/RUNBOOK-PEMULIHAN.md) | Pemulihan dari cadangan |
| [`CHANGELOG.md`](CHANGELOG.md) | Riwayat perubahan |
| `/dokumentasi` (di aplikasi) | Panduan pemakaian untuk pengguna akhir |

## Aturan kontribusi

- Baca `CLAUDE.md`/`AGENTS.md` dan aturan di `.ai/rules/` (dipetakan di `.ai/rules/index.md`) sebelum mengubah berkas.
- Perubahan perilaku disertai feature test, dan penjaganya dibuktikan (test merah bila penjaga dirusak).
- Sebelum commit: Pint, Prettier, `tsc`, PHPStan tidak bertambah galat, dan test terkait hijau.
- Dokumen di `docs/source-of-truth/` mengikat. Perubahan yang membatalkan isinya wajib memperbarui dokumen itu di commit yang sama.
- Tidak ada dependensi baru tanpa persetujuan pemilik produk.
