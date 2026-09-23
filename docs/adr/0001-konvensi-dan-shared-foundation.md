# ADR 0001 — Konvensi dan Shared Foundation

Status: Diterima
Fase: TASK.md FASE 01

## Konteks

Sebelum domain bisnis (Penyedia, Aset, PerintahKerja, dst) dibangun, seluruh
domain memerlukan aturan yang sama untuk exception, transaction, waktu, dan
uang. Dokumen ini mencatat keputusan tersebut agar tidak diulang berbeda-beda
per domain.

## 1. Konvensi Bahasa dan Kode

- Business class, method, dan variable memakai Bahasa Indonesia
  (`app/Domain/*`, `app/Core/*`, `app/Shared/*`).
- Method kontrak framework (`handle`, `rules`, `authorize`, `boot`, `up`,
  `down`) tetap mengikuti nama framework.
- Status memakai Enum PHP, bukan magic string tersebar di kode.
- Controller tipis: validasi → Action/Service → Resource/Inertia. Business
  rule tidak ditaruh di Controller, Request, React component, atau
  Repository murni.
- Hindari class `Helper` generik; setiap abstraksi baru harus punya
  boundary/tanggung jawab yang jelas (mis. `Uang`, `LayananZonaWaktu`, bukan
  `Utils`/`Helper` serba guna).

## 2. Shared Exceptions (`app/Shared/Domain/Exceptions`)

Lima exception domain, semuanya turunan `PengecualianDomain` yang mewajibkan
`kodeStatusHttp()` dan `kodeError()`:

| Exception | HTTP | Kode Error |
|---|---:|---|
| `AturanBisnisDilanggar` | 422 | `ATURAN_BISNIS_DILANGGAR` |
| `AksesDitolak` | 403 | `AKSES_DITOLAK` |
| `DataTidakDitemukan` | 404 | `DATA_TIDAK_DITEMUKAN` |
| `KonflikData` | 409 | `KONFLIK_DATA` |
| `VersiDataBerubah` | 409 | `VERSI_DATA_BERUBAH` |

Mapping response didaftarkan sekali di `bootstrap/app.php` (`withExceptions`):
request `expectsJson()` menerima JSON `{pesan, kode_error}` dengan status
yang sesuai; request web menerima halaman Inertia `Error` dengan status yang
sama. Karena mapping generik lewat class dasar, domain baru cukup melempar
exception yang sudah ada tanpa menambah mapping baru.

Production (`APP_DEBUG=false`) tidak menampilkan stack trace — ini perilaku
bawaan Laravel yang diverifikasi lewat test
`tests/Feature/Shared/PengecualianDomainTest.php`.

## 3. Transaction Policy

`App\Shared\Domain\Contracts\TransaksiDatabase` (implementasi
`TransaksiDatabaseLaravel`, `DB::transaction(..., attempts: 3)`) dipakai untuk
membungkus setiap operasi **multi-write**. Aturan:

- Wajib dipakai bila satu use-case menulis ke lebih dari satu tabel, atau ke
  satu tabel lebih dari sekali dengan invariant yang harus konsisten
  (contoh nanti: mutasi stok + mutasi kerja, approval + audit).
- Panggilan HTTP eksternal (webhook, integrasi) **tidak** dilakukan di dalam
  transaction bila bisa dihindari — supaya lock database tidak tertahan
  menunggu jaringan luar.
- Efek yang harus dikirim ke sistem lain setelah commit memakai pola outbox
  (`KotakKeluarPeristiwa`, dibangun di FASE 19), bukan dipanggil langsung di
  tengah transaction.

Dibuktikan oleh `tests/Unit/Shared/TransaksiDatabaseLaravelTest.php`: baris
yang ditulis di dalam callback yang melempar exception tidak pernah tersimpan.

## 4. Waktu dan Zona Waktu

- Semua kolom waktu di schema (`DibuatPada`, `DiperbaruiPada`, dst) disimpan
  UTC (`immutable_datetime` cast pada model hasil generator).
- Presentasi memakai zona waktu efektif: `Lokasi.ZonaWaktu` bila diisi,
  jika tidak jatuh ke `Organisasi.ZonaWaktu`.
- Konversi terpusat di `App\Shared\Infrastructure\Clock\LayananZonaWaktu`
  (`keZonaWaktu`, `keUtc`, `zonaWaktuEfektif`). Feature **tidak** boleh
  memanggil `Carbon::now('Asia/Jakarta')` atau timezone hardcoded lain.
- Edge case pergantian tanggal lokal (waktu UTC yang melewati tengah malam
  saat dikonversi, termasuk offset negatif) diuji di
  `tests/Unit/Shared/LayananZonaWaktuTest.php`.
- UTC ditegakkan di tiga lapis, bukan diandalkan pada disiplin pemanggil:
  `config/app.php` mengunci `'UTC'` (tidak dibaca dari `APP_TIMEZONE`), sesi
  basis data disetel `+00:00` di `config/database.php` supaya
  `DEFAULT CURRENT_TIMESTAMP` ikut UTC, dan objek waktu berzona dinormalkan ke
  UTC baik saat disetel ke atribut model (`MenyimpanWaktuDalamUtc`) maupun saat
  diikat ke kueri (`MengikatWaktuDalamUtc` pada koneksi MySQL/MariaDB).
- Keputusan kalender ("hari ini", jatuh tempo, rentang laporan, periode
  penomoran) memakai `App\Core\Organisasi\KalenderOrganisasi`, bukan tanggal
  UTC. `hariIni()` mengembalikan tengah malam UTC dari tanggal lokal, bentuk
  yang sama dengan kolom `date`, sehingga dapat dibandingkan langsung. Kolom
  berjam disaring dengan rentang `awalHari() .. awalHariBerikutnya()`, dan
  pengelompokan per tanggal memakai `CONVERT_TZ(kolom, '+00:00', offsetSql())`.
  Pekerjaan tingkat vendor (pemasaran, penagihan) memakai zona bawaan
  `amanpoll.zona_waktu_default`.
- Pola yang menghasilkan tanggal UTC (`Carbon::today()`, `now()->toDateString()`,
  `CURRENT_DATE`, `whereDate` pada kolom `...Pada`, `toISOString().slice(0, 10)`
  di frontend) ditolak `tests/Architecture/TanggalKalenderTidakDariJamUtcTest.php`.

## 5. Uang dan Angka

- Kolom uang di schema selalu `DECIMAL`; backend **tidak** boleh memakai
  `float` untuk nilai uang karena representasi biner float tidak presisi.
- `App\Shared\Domain\ValueObjects\Uang` menyimpan nilai sebagai integer
  satuan terkecil (mis. sen) dan hanya berinteraksi lewat `dariString()` /
  `keString()` — tidak pernah lewat float untuk kalkulasi.
- Aturan pembulatan: **setengah ke atas (half-up)** pada digit tepat setelah
  skala (default 2 desimal), diterapkan saat parsing (`Uang::dariString`).
  Diuji di `tests/Unit/Shared/UangTest.php`.
- Perkalian hanya menerima faktor integer (`kali(int $faktor)`) — dipakai
  untuk kuantitas (harga satuan × qty), bukan pengali pecahan, supaya float
  tidak pernah masuk ke jalur kalkulasi.
- Formatter tampilan mata uang ada di frontend
  (`resources/js/lib/uang.ts`, `formatUang`); ini murni presentasi. Total
  yang dikirim client tetap **wajib dihitung ulang di server** sebelum
  disimpan — aturan ini berlaku mulai domain yang benar-benar menghitung
  total (Persediaan, Procurement, Anggaran, PerintahKerja) di fase
  berikutnya.

## 6. Strategi Migration dan Test Database (Update)

Keputusan yang sempat ditunda di atas sudah diambil: seluruh 136 tabel
domain + 4 view dikonversi dari `database/schema/Amanpoll_Database_MySQL.sql`
menjadi migration Laravel (`database/migrations/2026_01_02_*`) lewat
`tools/generate-migrations.cjs`. Aturan yang berlaku:

- **Migration adalah cara resmi membangun schema** di semua environment
  (lokal, test, CI, produksi) lewat `php artisan migrate`. SQL mentah di
  `database/schema/` tetap disimpan sebagai dokumentasi/rujukan yang mudah
  dibaca dan untuk import cepat satu kali di shared hosting bila diperlukan
  (lihat `deploy/niagahoster/DEPLOY.md`), tapi **bukan lagi satu-satunya
  jalan** membuat schema.
- Kolom, tipe, default, unique key, index, dan foreign key pada migration
  hasil generate sudah diverifikasi identik dengan hasil import SQL mentah
  (dibandingkan lewat `SHOW CREATE TABLE` di MySQL/MariaDB sungguhan; 136
  tabel, 382 foreign key, dan 4 view cocok persis, kecuali nama constraint
  FK yang memang tidak dipatok di SQL asli).
  Perbedaan nama constraint FK ini tidak berdampak fungsional.
- Foreign key didefinisikan **inline** saat `Schema::create()` masing-masing
  tabel, dalam urutan topological sort dependency graph (bukan dua fase
  create-lalu-alter) — tidak ditemukan siklus FK di antara 136 tabel
  domain saat ini. Bila SQL berubah dan menimbulkan siklus, generator akan
  otomatis menunda FK yang menyebabkan siklus ke migration terpisah
  (`..._tambah_foreign_key_siklus.php`).
- Karena berbasis Schema Builder (bukan raw SQL khusus MySQL), migration
  ini **database-agnostic** dan terbukti jalan bersih di MySQL/MariaDB
  maupun SQLite. Ini menyelesaikan kebutuhan test database: `phpunit.xml`
  tetap memakai SQLite in-memory, dan `RefreshDatabase` di test sekarang
  bisa membangun seluruh schema domain, bukan cuma tabel infrastruktur.
  Lihat `tests/Feature/Shared/MigrasiSchemaDomainTest.php`.
- 4 view operasional memakai sintaks MySQL (`TIMESTAMPDIFF`, `CASE`) yang
  tidak portable ke SQLite; migration view mendeteksi driver koneksi dan
  di-skip otomatis di luar MySQL — test yang butuh view tersebut wajib
  dijalankan terhadap MySQL/MariaDB, bukan SQLite.
- Data seed platform (`Izin` dasar) dipindah dari `INSERT` di SQL mentah ke
  `database/seeders/IzinSeeder.php` (idempotent lewat `upsert`), karena
  data bukan bagian dari struktur schema.
- Bila `Amanpoll_Database_MySQL.sql` berubah, jalankan ulang
  `node tools/generate-migrations.cjs` untuk membuat ulang seluruh migration
  tabel domain, lalu review diff sebelum commit.
