# ADR 0004 — Struktur Organisasi dan Konfigurasi

Status: Diterima
Fase: TASK.md FASE 04

## Konteks

FASE 04 membangun modul yang dipakai admin organisasi untuk menyiapkan
struktur dasar (unit, lokasi) dan konfigurasi sebelum modul bisnis (Aset,
Pemeliharaan, dst.) bisa dipakai. Dokumen ini mencatat keputusan desain dan
bug nyata yang ditemukan selama implementasi.

## 1. Organisasi: Self-Scoped, Tanpa ID di URL

`/platform/organisasi` tidak punya parameter `{organisasi}` di URL —
`OrganisasiController` selalu mengambil baris lewat
`Organisasi::findOrFail($this->konteks->wajibId())`. Ini sengaja
menghindari kelas bug IDOR yang sama yang harus ditambal manual di
`Pengguna` (ADR 0003 bagian 5): karena `Organisasi` adalah akar tenant itu
sendiri (bukan model yang "dimiliki" organisasi lain), tidak ada
`ScopeOrganisasi` yang bisa melindunginya lewat route model binding biasa.

`Status` dan `Kode` sengaja **tidak** ada di `SimpanOrganisasiRequest` —
TASK.md eksplisit meminta "Status sesuai policy platform", dan `Kode`
adalah identitas login (`KodeOrganisasi` di form login) yang perubahannya
berdampak luas. Keduanya baru bisa diubah dari sisi platform (di luar
scope FASE 04), bukan dari halaman profil tenant biasa. Dibuktikan
`OrganisasiControllerTest::test_status_dan_kode_tidak_bisa_diubah_lewat_endpoint_profil`.

## 2. PemeriksaHierarkiSirkular: Layanan Bersama, Bukan Duplikasi

`UnitOrganisasi.IndukId` dan `Lokasi.IndukId` sama-sama butuh pencegahan
hierarki melingkar. Daripada menduplikasi logic di dua Action, dibuat satu
`App\Shared\Domain\Services\PemeriksaHierarkiSirkular::pastikanTidakSirkular()`
yang generik lewat `DB::table($tabel)` (bukan model Eloquent tertentu) —
konsisten dengan pola `PemeriksaRelasiOrganisasi` (ADR 0002) yang juga
skema-driven. Menelusuri rantai induk ke atas sampai ketemu diri sendiri
(melingkar) atau `null` (aman), dengan guard `$dikunjungi` supaya data yang
sudah korup sebelumnya tidak bikin infinite loop.

Konsekuensi desain: menghapus unit/lokasi yang masih punya sub-unit/lokasi
juga ditolak (`HapusUnitOrganisasi`, `HapusLokasi`) — bukan diminta
eksplisit oleh TASK.md, tapi perlu supaya hierarki tidak punya cabang
menggantung ke baris yang soft-deleted (pola yang sama seperti
"peran masih dipakai tidak bisa dihapus" di FASE 03).

## 3. KonfigurasiOrganisasi: Katalog Tervalidasi, Bukan Key-Value Bebas

`DefinisiKonfigurasi::daftar()` adalah katalog statis kode
(`Notifikasi.EmailAktif`, dst., mengikuti konvensi dot-notation yang sama
seperti kode `Izin`) dengan namespace, tipe, default, dan flag `Rahasia`.
`SimpanKonfigurasiOrganisasiRequest` menolak kunci di luar katalog ini dan
memvalidasi tipe nilai sesuai definisinya — tenant tidak bisa menyimpan
sembarang key-value. `LayananKonfigurasi::ambil()` meng-cache per
`(OrganisasiId, Kunci)` selama 1 jam dan mengembalikan `Default` dari
katalog kalau belum pernah di-override; `SimpanKonfigurasiOrganisasi`
memanggil `bersihkanCache()` setelah menulis supaya perubahan berlaku
langsung, bukan menunggu TTL. Nilai `Rahasia` disamarkan (`null`) saat
dibaca lewat `semua()` untuk ditampilkan di UI, tapi tetap bisa ditimpa.

Katalog awal sengaja kecil (5 kunci lintas 3 namespace) — cukup untuk
membuktikan pola infrastrukturnya (namespace, default, cache invalidation)
bekerja; menambah kunci baru untuk fitur nyata adalah kerja fase yang
membangun fitur itu, bukan FASE 04.

## 4. NomorDokumen: Concurrency Safety Butuh Retry, Bukan Cuma Lock

`LayananNomorDokumen::berikutnya()` membaca baris dengan
`lockForUpdate()` di dalam `DB::transaction()`. Awalnya dites dengan proses
paralel sungguhan (`pcntl_fork`, lihat bagian 6) dan **gagal** dengan error
`database is locked` — SQLite (dipakai test suite) tidak punya row-level
lock; `lockForUpdate()` di sana secara efektif no-op, dan penulis kedua
yang mencoba `UPDATE` saat penulis pertama masih dalam transaksi langsung
ditolak, bukan menunggu.

Perbaikan: `DB::transaction($callback, 5)` — parameter kedua Laravel yang
sudah ada, sering terlewat. `ManagesTransactions::transaction()` otomatis
mengulang seluruh closure kalau exception-nya cocok dengan
`ConcurrencyErrorDetector::causedByConcurrencyError()`, yang daftarnya
sudah mencakup baik `"database is locked"` (SQLite) maupun
`"Deadlock found..."`/`"Lock wait timeout exceeded..."` (MySQL). Satu
baris ini membuat kode yang sama aman di kedua database tanpa retry
manual. Dibuktikan `NomorDokumenKonkurensiTest` (lihat bagian 6).

Format nomor pakai placeholder sederhana (`{Awalan}`, `{Nomor}`/`{Nomor:N}`
untuk zero-padding, `{Tahun}`, `{TahunPendek}`, `{Bulan}`, `{Periode}`)
lewat `preg_replace_callback` satu regex — bukan template engine terpisah,
karena kebutuhannya cuma substitusi token datar.

Reset periode (`Tahunan`/`Bulanan`/`TidakAda`) disimpan sebagai string
periode aktif (`PeriodeAktif`, mis. `"2026"` atau `"2026-09"`); begitu
periode saat ini berbeda dari yang tersimpan, nomor otomatis kembali ke 1.

## 5. Bug Nyata: Cast Eloquent `'date'` Polos Menyimpan Jam "00:00:00"

`Model::fromDateTime()` (dipanggil `setAttribute()` untuk semua cast
date-like) selalu memformat pakai `getDateFormat()` — default
`'Y-m-d H:i:s'` — **terlepas dari cast spesifiknya `'date'` atau
`'datetime'`**. Efeknya: kolom `HariLibur.Tanggal` yang di-cast `'date'`
polos tersimpan sebagai `"2026-12-25 00:00:00"`, bukan `"2026-12-25"`.

Ini nyaris tidak kelihatan karena kolom `Tanggal` bertipe `DATE` asli di
MySQL (target produksi) — MySQL otomatis memotong bagian jam saat
menyimpan string datetime ke kolom `DATE`. **SQLite tidak melakukan itu**
(kolom `DATE` di SQLite cuma TEXT affinity, tidak memaksa format apa pun),
jadi perbandingan tanggal exact-match (`WHERE Tanggal = '2026-12-25'`) di
`LayananKalenderKerja` dan `Rule::unique` di
`SimpanHariLiburRequest` diam-diam selalu gagal cocok saat dites —
ditemukan lewat test yang gagal, bukan diprediksi.

Perbaikan: cast diubah ke `'date:Y-m-d'` (bentuk `custom_datetime` di
Eloquent) yang terbukti secara empiris menyimpan `"2026-12-25"` apa
adanya, tanpa menyentuh `getDateFormat()` global model (yang juga dipakai
`DibuatPada`/kolom datetime asli — mengubah itu akan merusak presisi
timestamp).

**Catatan untuk fase berikutnya:** pola cast `'date'` polos yang sama ada
di ~40 kolom lain di seluruh model FASE 05+ (`Aset.TanggalPerolehan`,
`Kontrak.MulaiPada`, `RencanaKalibrasi.TanggalBerikutnya`, dst.) —
peninggalan generator FASE 00 yang belum pernah dites langsung karena
belum ada use-case nyata yang query kolom itu. Sesuai prinsip "jangan
lompat modul", kolom-kolom itu **tidak** diperbaiki sekarang; setiap fase
yang mulai benar-benar meng-query tanggal-tanggal itu (exact match atau
unique constraint) wajib mengubah cast-nya ke `'date:Y-m-d'` saat itu
juga, bukan berasumsi cast `'date'` polos sudah aman.

## 6. Test Request Paralel Sungguhan (pcntl_fork), Bukan Simulasi

TASK.md eksplisit meminta "Test request paralel" untuk NomorDokumen. Test
biasa (single-process, sequential call) tidak bisa membuktikan keamanan
lock terhadap race condition sungguhan. `NomorDokumenKonkurensiTest`:

- Membuat file SQLite terpisah (bukan `:memory:` bawaan phpunit.xml, yang
  per-koneksi/proses dan tidak bisa dibagi antar proses fork), migrate
  sekali di proses induk, lalu **benar-benar fork 5 proses OS** lewat
  `pcntl_fork()`, masing-masing memanggil `berikutnya()` 10 kali.
- Setiap anak `DB::purge()` dulu sebelum query pertamanya, supaya proses
  itu membuka koneksi PDO miliknya sendiri ke file yang sama, bukan
  mewarisi handle proses induk lintas fork (praktik wajib untuk SQLite:
  berbagi PDO/file descriptor yang sama lintas proses lewat fork tanpa
  reconnect adalah undefined behavior).
- Proses induk `pcntl_waitpid` semua anak, gabungkan hasil, dan pastikan:
  jumlah nomor yang dihasilkan tepat 50, semuanya unik, dan urutannya
  1..50 tanpa bolong (setelah di-sort) — bukti langsung tidak ada nomor
  ganda maupun nomor yang diam-diam dilompati.

Test ini yang pertama kali menemukan bug di bagian 4 (retry attempts).
Stabil dijalankan berulang (5x berturut-turut, semua hijau) sebelum
dianggap selesai.

## 7. Cakupan Test Gate 04

| Skenario wajib | Test |
|---|---|
| Zona waktu tidak valid ditolak | `OrganisasiControllerTest` |
| Status/Kode tidak bisa diubah lewat profil | `OrganisasiControllerTest` |
| Hierarki UnitOrganisasi/Lokasi melingkar ditolak | `UnitOrganisasiControllerTest`, `LokasiControllerTest` |
| Hapus unit/lokasi/kategori yang masih dipakai ditolak | `UnitOrganisasiControllerTest`, `LokasiControllerTest`, `KategoriLokasiControllerTest` |
| Lintas tenant: Unit, Lokasi, Organisasi, HariLibur | `UnitOrganisasiControllerTest`, `LokasiControllerTest`, `OrganisasiControllerTest`, `HariLiburControllerTest` |
| Konfigurasi: kunci tidak dikenal, tipe salah, cache langsung bersih | `KonfigurasiOrganisasiControllerTest` |
| NomorDokumen: format wajib placeholder, duplikat jenis dokumen, pratinjau idempoten, reset periode | `NomorDokumenControllerTest` |
| NomorDokumen: aman dari race condition proses paralel sungguhan | `NomorDokumenKonkurensiTest` |
| HariLibur: berulang tahunan, khusus lokasi, cari hari kerja berikutnya | `LayananKalenderKerjaTest` |
| RBAC: semua endpoint FASE 04 ditolak tanpa `Pengaturan.Kelola` | tiap `*ControllerTest` |

Total 137 test di seluruh suite (FASE 00–04), hijau bersama PHPStan (0
error) dan `npm run build`. Frontend (6 halaman: Organisasi, Unit
Organisasi, Lokasi, Konfigurasi, Nomor Dokumen, Hari Libur) diverifikasi
manual end-to-end dengan Playwright di server lokal — CRUD, toggle
konfigurasi, edit profil organisasi, tanpa error konsol/JS.
