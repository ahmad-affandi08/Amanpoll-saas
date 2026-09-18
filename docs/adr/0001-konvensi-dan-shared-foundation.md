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

## 6. Yang Sengaja Belum Diputuskan di Fase Ini

Strategi database untuk automated test terhadap 136 tabel domain (yang
schema-nya berbasis SQL mentah, bukan migration) belum ditentukan di FASE 01
karena belum ada domain nyata yang butuh query lintas tabel dalam test.
Keputusan ini wajib diambil sebelum FASE 02 (multi-organisasi) karena test
isolasi tenant membutuhkan baris data sungguhan di tabel domain.
