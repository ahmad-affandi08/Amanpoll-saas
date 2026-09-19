# ADR 0003 — Autentikasi, RBAC, dan Kunci API

Status: Diterima
Fase: TASK.md FASE 03

## Konteks

FASE 03 membangun lapisan Authentication, Session, RBAC, dan Security di
atas fondasi multi-tenant FASE 02. Dokumen ini mencatat keputusan desain
dan bug nyata yang ditemukan selama implementasi, plus satu koreksi
terhadap ADR 0002.

## 1. Rate Limiting Login

`LoginController` membatasi percobaan login per kombinasi
`KodeOrganisasi|Email|IP` (bukan per-IP saja, supaya satu penyerang yang
mencoba banyak akun di satu organisasi tetap kena limit per akun, dan satu
pengguna sah di jaringan kantor yang sama tidak ikut terkunci oleh
percobaan orang lain). Maks 5 percobaan / 60 detik, pakai
`Illuminate\Support\Facades\RateLimiter` bawaan Laravel (bukan
implementasi manual) supaya konsisten dengan strategi backend cache yang
sudah dikonfigurasi. Dibuktikan `tests/Feature/Auth/LoginTest.php`.

## 2. RBAC: Cache Permission Set per Pengguna, Bukan per Izin

`PemeriksaIzin::daftarKodeIzin()` meng-cache **seluruh daftar kode izin**
seorang pengguna dalam satu key (`izin:{OrganisasiId}:{PenggunaId}`), bukan
cache per-pasangan (pengguna, izin). Alasan: dengan cache per-izin, tidak
ada cara murah untuk invalidasi semua entry milik satu pengguna saat
`PeranIzin` atau `PenggunaPeran` berubah tanpa menyimpan index terpisah.
Dengan cache per-set, `bersihkanCache()` cukup satu `forget()` per
pengguna terdampak. `SinkronkanIzinPeran` memanggil ini untuk **setiap**
pengguna yang punya peran tersebut, supaya pencabutan izin langsung
berlaku tanpa menunggu TTL 5 menit kedaluwarsa. Dibuktikan
`RbacAssignmentTest::test_sinkronkan_izin_peran_langsung_memengaruhi_akses`.

## 3. Policy vs Action: Pemisahan 403 vs 422

Policy (`PeranPolicy`, `PenggunaPolicy`, `KunciApiPolicy`) **hanya**
memeriksa izin RBAC (`PemeriksaIzin::boleh()`) — tidak ada aturan bisnis di
dalamnya. Aturan bisnis seperti "peran BawaanSistem tidak boleh dihapus"
atau "pengguna tidak boleh menonaktifkan akun sendiri" sengaja ditaruh di
Action (`HapusPeran`, `UbahStatusPengguna`), yang melempar
`AturanBisnisDilanggar` (→ HTTP 422). Ini menjaga semantik HTTP yang benar:
403 berarti "Anda tidak berwenang melakukan operasi ini sama sekali", 422
berarti "Anda berwenang, tapi permintaan spesifik ini melanggar aturan
data". Mencampur keduanya di policy akan membuat klien tidak bisa
membedakan dua kasus itu tanpa membaca pesan error.

## 4. Reset Kata Sandi Custom, Bukan Password Broker Bawaan Laravel

Laravel `Password` broker bawaan mengasumsikan email unik secara global.
Amanpoll multi-tenant: email hanya unik **per organisasi**
(`SimpanPenggunaRequest` men-scope `Rule::unique` dengan `OrganisasiId`).
Broker bawaan tidak punya jalur untuk membedakan dua pengguna dengan email
sama di organisasi berbeda. Solusi: alur reset kata sandi custom di
`MintaResetKataSandi`/`ResetKataSandi`, dengan:

- Tabel `TokenResetKataSandi` diubah primary key-nya dari `email` (schema
  bawaan Laravel) menjadi `PenggunaId`, karena kita sudah tahu identitas
  pengguna dari `KodeOrganisasi` + `Email` sebelum token dibuat.
- Token disimpan sebagai hash (`TokenHash`), bukan plaintext — pola yang
  sama dengan Kunci API (lihat bagian 6).
- Kedaluwarsa diperiksa dengan `Carbon::parse($baris->DibuatPada)->lt(now()->subMinutes(60))`,
  **bukan** `now()->diffInMinutes($baris->DibuatPada)` — bentuk kedua
  sempat memberi hasil yang tidak konsisten arah tandanya (bug nyata yang
  ditemukan lewat test, bukan diprediksi) sehingga token yang sudah lewat
  60 menit tidak terdeteksi kedaluwarsa.

Dibuktikan `tests/Feature/Auth/ResetKataSandiTest.php`.

## 5. Route Model Binding untuk Model Admin: `resolveRouteBinding` Eksplisit

`Pengguna::resolveRouteBinding()` di-override supaya route seperti
`/platform/pengguna/{pengguna}` hanya me-resolve baris milik
`KonteksOrganisasi::id()` saat ini — perlu eksplisit karena `Pengguna`
sengaja tidak memakai `MilikOrganisasi`/`ScopeOrganisasi` (lihat ADR 0002
bagian 5), jadi tanpa override ini, admin organisasi A bisa mengubah
pengguna organisasi B lewat ID di URL. **Override ini aman untuk
autentikasi** karena `EloquentUserProvider::retrieveById()` (dipakai
`Auth::attempt()`/sesi login) tidak memanggil `resolveRouteBinding()` —
keduanya jalur kode yang sepenuhnya terpisah. Dibuktikan
`PenggunaControllerTest::test_organisasi_a_tidak_dapat_mengubah_pengguna_organisasi_b`.

## 6. Desain Kunci API

- **Format token**: `{prefix}.{secret}`, keduanya `Str::random()`. Prefix
  (kolom `AwalanKunci`, tidak di-hash) dipakai untuk lookup cepat tanpa
  memindai seluruh tabel; hash SHA-256 dari token penuh (kolom
  `HashKunci`) yang dibandingkan pakai `hash_equals()` (constant-time,
  menghindari timing attack).
- **Token mentah hanya ada di memori saat pembuatan** — `BuatKunciApi`
  mengembalikannya lewat return value, bukan disimpan, dan
  `KunciApiController::store()` mengirimnya lewat flash session sekali
  pakai (`tokenKunciApi`). Frontend menampilkannya sekali dalam dialog dan
  tidak menyimpannya di state permanen.
- **Cabut = soft, bukan hapus**: `CabutKunciApi` mengubah `Status` menjadi
  `'Dicabut'`, baris tetap ada untuk jejak. `AutentikasiKunciApi` menolak
  kunci yang `Status != 'Aktif'` atau sudah lewat `KadaluarsaPada`.
- **Cakupan (`Cakupan`) opsional**: array kode `Izin` (bukan ID) yang
  disimpan di kolom kunci itu sendiri, diperiksa lewat middleware
  `PastikanCakupanKunciApi` (`cakupan.kunci:{kode}`) terpisah dari
  autentikasi (`kunci.api`) — endpoint bisnis mendeklarasikan cakupan yang
  ia butuhkan lewat parameter middleware, bukan pengecekan manual di
  controller. Kunci tanpa cakupan (`null`/kosong) berarti akses penuh
  sesuai izin RBAC pemiliknya di FASE berikutnya (belum relevan sampai ada
  endpoint API bisnis nyata).
- **Middleware priority**: `AutentikasiKunciApi` didaftarkan di
  `prependToPriorityList` yang sama seperti `TetapkanKonteksOrganisasi`
  (ADR 0002 bagian 2) — kunci API juga menetapkan konteks tenant, jadi
  butuh jaminan yang sama soal urutan terhadap `SubstituteBindings`.

Dibuktikan `tests/Feature/Platform/KunciApiControllerTest.php` (generate,
autentikasi dengan token asli, pencabutan, pembatasan cakupan, penolakan
tanpa izin, isolasi lintas tenant).

**Audit create/revoke ditunda ke FASE 05.** TASK.md menyebut "Audit
create/revoke" sebagai bagian dari fitur Kunci API, tapi tabel
`CatatanAudit` dan layanan penulisannya adalah scope FASE 05 (Audit &
Compliance) yang belum dibangun — menulis ke tabel itu sekarang berarti
mengasumsikan skema/kontrak yang belum ditentukan, melanggar prinsip
"jangan lompat modul" yang sama yang sudah diterapkan di ADR 0001/0002
untuk gap serupa. Titik integrasinya sudah jelas (`BuatKunciApi::jalankan()`
dan `CabutKunciApi::jalankan()`), tinggal menambah pemanggilan layanan
audit begitu FASE 05 selesai.

> **Update FASE 05**: `LayananAudit::catat()` sudah dipanggil dari
> `BuatKunciApi::jalankan()` dan `CabutKunciApi::jalankan()` (lihat ADR
> 0005). `HashKunci` tidak pernah ditulis ke `DataSesudah` -- hanya field
> non-rahasia (Nama, AwalanKunci, Cakupan, dll) yang dicatat.

## 7. Koreksi ADR 0002: Cakupan `PemeriksaRelasiOrganisasi` terhadap `Pengguna`

Lihat ADR 0002 bagian 5 (sudah diperbarui langsung di file itu). Ringkas:
klaim awal bahwa FK ke `Pengguna` tidak diperiksa guard lintas-organisasi
adalah salah — guard tersebut berbasis metadata skema (`Schema::hasColumn`
pada tabel tujuan), bukan pemakaian trait `MilikOrganisasi` di model
tujuan, dan tabel `Pengguna` punya kolom `OrganisasiId` secara fisik.
Ditemukan dan dibuktikan salah lewat
`RelasiLintasOrganisasiTest::test_menolak_kunci_api_menunjuk_pembuat_milik_organisasi_lain`
saat mengimplementasikan `KunciApi.DibuatOleh`.

## 8. Inertia Prop Wrapping: `JsonResource::withoutWrapping()`

Laravel membungkus resource tunggal (`new SomeResource($model)`) dengan
amplop `{"data": ...}` secara default — bukan hanya untuk respons API,
tapi juga saat resource itu dipakai sebagai prop Inertia (Inertia
memperlakukan `JsonResource` sebagai `Responsable` dan memanggil
`toResponse()`-nya). Ini ditemukan lewat smoke test manual: halaman Profil
crash di frontend (`Cannot read properties of undefined`) karena
`PenggunaResource` tunggal di `ProfilController::edit()` menghasilkan
`props.pengguna.data.Perangkat`, bukan `props.pengguna.Perangkat`.

Diperbaiki dengan `JsonResource::withoutWrapping()` di
`AppServiceProvider::boot()` — berlaku global, bukan per-resource, supaya
semua resource tunggal di masa depan (mis. detail Organisasi di FASE 04)
konsisten tanpa perlu ingat menambahkan penanganan khusus di tiap
controller/halaman. **Koleksi terpaginasi tidak terpengaruh**:
`Resource::collection($paginator)` memakai `PaginatedResourceResponse`
yang selalu menyertakan `data`/`links`/`meta` secara terpisah dari
pengaturan wrap ini — diverifikasi manual lewat payload Inertia mentah
untuk halaman Pengguna (masih `{data, links, meta}`) dan Profil (sudah
tidak berlapis `data`) sebelum dan sesudah perubahan.

## 9. Visibility Helper Frontend, Backend Tetap Satu-satunya Otorisasi

`HandleInertiaRequests` membagikan `izin: string[]` (daftar kode izin
pengguna saat ini, dari `PemeriksaIzin::daftarKodeIzin()`) ke semua
halaman. Hook `useIzin().boleh(kode)` di frontend memakai ini **hanya**
untuk menyembunyikan tombol/menu yang pasti akan ditolak backend — setiap
endpoint tetap memanggil `$this->authorize()`/policy sendiri di server.
Menyembunyikan UI tanpa ini akan membuat pengguna menekan tombol yang
selalu gagal; mengandalkan ini sebagai satu-satunya otorisasi akan mudah
dilewati lewat request langsung.

## 10. Cakupan Test Gate 03

| Skenario wajib | Test |
|---|---|
| Rate limit login | `LoginTest` |
| RBAC: akses ditolak tanpa izin (viewAny, create, update, delete) | `PeranControllerTest`, `PenggunaControllerTest`, `RbacAssignmentTest` |
| RBAC: perubahan izin langsung berlaku | `RbacAssignmentTest::test_sinkronkan_izin_peran_langsung_memengaruhi_akses` |
| Peran BawaanSistem tidak bisa dihapus | `PeranControllerTest::test_peran_bawaan_sistem_tidak_dapat_dihapus` |
| Admin tidak bisa nonaktifkan diri sendiri | `PenggunaControllerTest::test_admin_tidak_dapat_menonaktifkan_akun_sendiri` |
| Lintas tenant: Peran, Pengguna, PenggunaPeran, KunciApi | `PeranControllerTest`, `PenggunaControllerTest`, `RbacAssignmentTest`, `KunciApiControllerTest` |
| Lintas tenant: FK ke Pengguna (koreksi ADR 0002) | `RelasiLintasOrganisasiTest::test_menolak_kunci_api_menunjuk_pembuat_milik_organisasi_lain` |
| Reset kata sandi: kedaluwarsa, token salah | `ResetKataSandiTest` |
| Ganti kata sandi butuh kata sandi lama benar | `ProfilControllerTest` |
| Perangkat: hanya pemilik yang bisa hapus | `PerangkatPenggunaTest` |
| Kunci API: generate, autentikasi, cabut, cakupan | `KunciApiControllerTest` |

Total 89 test di seluruh suite (FASE 00–03), hijau bersama PHPStan (0
error) dan `npm run build`. Frontend (halaman Pengguna, Peran & Izin,
Profil, Kunci API) diverifikasi manual end-to-end dengan Playwright di
server lokal: login, CRUD pengguna, penetapan/pencabutan peran, edit izin
per-peran, buat/cabut kunci API dengan reveal token sekali tampil — tanpa
error konsol/JS.
