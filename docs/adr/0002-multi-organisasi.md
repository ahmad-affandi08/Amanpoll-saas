# ADR 0002 — Multi-Organisasi (Tenant Isolation)

Status: Diterima
Fase: TASK.md FASE 02

## Konteks

FASE 02 adalah gate keras: tidak ada domain bisnis boleh dibangun sebelum
isolasi tenant terbukti lewat test. Sebagian besar mekanisme dasar sudah
digenerate di FASE 00 (`KonteksOrganisasi`, `ScopeOrganisasi`,
`MilikOrganisasi`, middleware sesi/API key). Dokumen ini mencatat apa yang
ditambahkan/diperbaiki di FASE 02 dan kenapa.

## 1. Resolusi Konteks Organisasi

Tiga jalur resolusi, semuanya **tidak pernah mengambil OrganisasiId mentah
dari request**:

- **Session**: `TetapkanKonteksOrganisasi` membaca `OrganisasiId` dari
  `$request->user()` (pengguna yang sudah terautentikasi), bukan dari input.
- **API key**: `AutentikasiKunciApi` mem-verifikasi hash kunci di tabel
  `KunciApi`, lalu menetapkan konteks dari `OrganisasiId` milik kunci
  tersebut (bukan dari header yang dikirim client).
- **Queue job**: `App\Jobs\PekerjaanOrganisasi` — lihat bagian 4.

`ScopeOrganisasi` fail-closed: bila konteks belum ditetapkan sama sekali,
query menghasilkan `1 = 0` (kosong), bukan lupa filter dan mengembalikan
semua baris. Dibuktikan
`tests/Feature/Core/Organisasi/IsolasiTenantTest::test_query_gagal_tertutup_saat_konteks_organisasi_tidak_ditetapkan`.

## 2. Bug Nyata: Route Model Binding Tidak Tenant-Aware Secara Default

Laravel menaruh `SubstituteBindings` (yang me-resolve `{model}` di URL) di
dalam middleware group `web`/`api` bawaan, pada posisi **sebelum**
middleware kustom apa pun yang didaftarkan lewat
`Route::middleware(['auth', 'organisasi'])`. Laravel memang men-sort ulang
sebagian middleware lewat `$middlewarePriority` bawaan Kernel (mis. `auth`
dipaksa sebelum `SubstituteBindings`), tapi middleware kustom yang **tidak
terdaftar** di priority list itu (termasuk `TetapkanKonteksOrganisasi` dan
`AutentikasiKunciApi`) tidak ikut dipindah — tetap berjalan **setelah**
binding di-resolve.

Akibatnya: route seperti `Route::get('/aset/{aset}', ...)` akan me-resolve
`$aset` **sebelum** konteks organisasi sempat ditetapkan, sehingga
`ScopeOrganisasi` selalu fail-closed (404 untuk siapa pun, termasuk pemilik
data yang sah) — bukan gagal secara aman-tapi-benar, tapi salah secara
diam-diam kalau kebetulan test hanya mencoba kasus "harus ditolak".

Diperbaiki di `bootstrap/app.php` dengan
`$middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ...)`
untuk `TetapkanKonteksOrganisasi` dan `AutentikasiKunciApi`. **Middleware
baru yang menetapkan konteks tenant apa pun di masa depan harus didaftarkan
di priority list yang sama**, atau route model binding untuk model tenant
akan diam-diam salah lagi. Dibuktikan
`tests/Feature/Core/Organisasi/RouteModelBindingTenantTest.php`.

## 3. Relasi Foreign Key Lintas Organisasi Ditolak

`MilikOrganisasi` sekarang memanggil `PemeriksaRelasiOrganisasi` pada event
`creating` (setelah OrganisasiId auto-terisi — urutan event Eloquent adalah
`saving` → `creating`, jadi pemeriksaan **tidak** boleh ditaruh di `saving`)
dan `updating`. Mekanismenya:

- Baca foreign key sungguhan dari database (`Schema::getForeignKeys()`),
  bukan refleksi method relasi Eloquent — lebih tahan terhadap kolom FK
  yang belum punya method relasi, dan menghindari jebakan nyata yang
  ditemukan saat implementasi: memanggil `getAttribute('DibuatOleh')` pada
  kolom yang camelCase-nya sama dengan nama method relasi (`dibuatOleh()`)
  membuat Eloquent salah resolve ke relasi alih-alih kolom mentah. Baca
  langsung dari `$model->getAttributes()`.
- Untuk setiap kolom FK yang terisi, kalau tabel tujuan punya kolom
  `OrganisasiId`, ambil barisnya (tanpa scope) dan bandingkan. Beda →
  `AturanBisnisDilanggar`.
- Berlaku juga untuk self-reference (mis. `UnitOrganisasi.IndukId` ke
  `UnitOrganisasi` lain) — tidak ada pengecualian tabel-ke-diri-sendiri.

Dibuktikan `tests/Feature/Core/Organisasi/RelasiLintasOrganisasiTest.php`.

## 4. Queue Job: Template Method, Bukan Konvensi

`PekerjaanOrganisasi::handle()` dibuat **final**; subclass mengimplementasi
`jalankan()`. Ini sengaja dibuat foolproof — versi awal (helper method yang
harus dipanggil manual) memungkinkan job baru "lupa" membungkus logicnya
dalam konteks organisasi. Dengan template method, itu tidak mungkin terjadi
secara struktural. Dibuktikan `tests/Feature/Core/Organisasi/PekerjaanOrganisasiTest.php`
(konteks ditetapkan selama `jalankan()`, dibersihkan sesudahnya, dan dua job
berurutan untuk organisasi berbeda tidak saling bocor).

## 5. Pengguna Sengaja Tidak Memakai MilikOrganisasi

Model `Pengguna` **tidak** memakai trait `MilikOrganisasi` meski punya
kolom `OrganisasiId` — ini keputusan generator FASE 00 yang dipertahankan,
bukan bug. Alasannya: `TetapkanKonteksOrganisasi` me-resolve
`$request->user()` untuk MENETAPKAN konteks; kalau `Pengguna` punya global
scope tenant, resolusi user itu sendiri butuh konteks yang belum ada
(circular dependency yang akan mematikan seluruh autentikasi). Login flow
sendiri sudah aman karena `LoginController` menetapkan konteks dari
`Organisasi.Kode` yang diinput **sebelum** memanggil `Auth::attempt()`.

Konsekuensinya: kode yang query `Pengguna` untuk kebutuhan bisnis (bukan
autentikasi) **wajib** lewat repository yang secara eksplisit filter
`OrganisasiId` (pola ini sudah ada di `EloquentPenggunaRepository` hasil
generator FASE 00), bukan `Pengguna::find()`/`Pengguna::all()` polos. Ini
batasan yang harus diingat setiap kali domain lain query Pengguna langsung
(mis. daftar teknisi, penanggung jawab aset) — didokumentasikan di sini
supaya tidak terulang sebagai bug di fase berikutnya.

**Koreksi (ditemukan saat implementasi FASE 03, Kunci API):** klaim di atas
pada draf awal ADR ini — bahwa relasi FK ke `Pengguna` (mis.
`KunciApi.DibuatOleh`) tidak diperiksa oleh `PemeriksaRelasiOrganisasi` —
**salah** dan sudah diperbaiki di sini. Guard tersebut membaca metadata FK
sungguhan dari database (`Schema::getForeignKeys()`) dan hanya melihat
apakah tabel **tujuan** punya kolom `OrganisasiId` (`Schema::hasColumn`),
bukan apakah model tujuan memakai trait `MilikOrganisasi`. Tabel `Pengguna`
sendiri punya kolom `OrganisasiId` (dipakai manual, hanya modelnya yang
sengaja tidak memakai trait — lihat alasan circular dependency di atas),
sehingga FK apa pun yang menunjuk ke `Pengguna` — termasuk
`KunciApi.DibuatOleh` — **tetap diperiksa dan ditolak** kalau menunjuk ke
pengguna organisasi lain. Dibuktikan
`RelasiLintasOrganisasiTest::test_menolak_kunci_api_menunjuk_pembuat_milik_organisasi_lain`.

## 6. Cakupan Test Gate 02.03

| Skenario wajib | Test |
|---|---|
| A tidak bisa baca data B | `IsolasiTenantTest::test_organisasi_a_tidak_dapat_membaca_data_organisasi_b` |
| A tidak bisa update data B | `IsolasiTenantTest::test_organisasi_a_tidak_dapat_update_data_organisasi_b` |
| A tidak bisa hapus data B | `IsolasiTenantTest::test_organisasi_a_tidak_dapat_menghapus_data_organisasi_b` |
| A tidak bisa "attach" (FK) ke data B | `RelasiLintasOrganisasiTest` |
| API key A tidak bisa akses B | `KunciApiIsolasiTest` |
| Background job tidak lintas tenant | `PekerjaanOrganisasiTest` |
| Fail-closed tanpa konteks | `IsolasiTenantTest::test_query_gagal_tertutup_saat_konteks_organisasi_tidak_ditetapkan` |
| Route model binding tenant-aware | `RouteModelBindingTenantTest` |

Total 4 file test baru di `tests/Feature/Core/Organisasi/`, semuanya hijau
bersama seluruh suite (43 test).
