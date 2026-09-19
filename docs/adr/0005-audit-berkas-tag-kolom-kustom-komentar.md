# ADR 0005 — Audit, Berkas, Tag, Kolom Kustom, Komentar

Status: Diterima
Fase: TASK.md FASE 05

## Konteks

FASE 05 sengaja dikerjakan sebelum Aset karena infrastrukturnya (lampiran
berkas, tag, kolom kustom, komentar, audit) dipakai hampir semua domain
bisnis nanti. Gate 05 mensyaratkan "Aset nanti dapat langsung memakai
file, tag, custom field, komentar, dan audit tanpa refactor" — dibuktikan
konkret di dokumen ini lewat integrasi nyata ke halaman Lokasi, bukan
cuma janji desain.

## 1. RegistriEntitas: Satu Peta untuk Semua Lampiran Polimorfik

`Berkas`/`Tag`/`DefinisiKolomKustom`/`KomentarEntitas` semuanya menempel
ke entitas manapun lewat kolom polimorfik `JenisEntitas` + `EntitasId`
tanpa FK sungguhan (tidak mungkin ada FK ke tabel yang berbeda-beda).
`App\Core\Entitas\RegistriEntitas` (container-bound singleton) memetakan
kode `JenisEntitas` ke kelas model Eloquent + kode Izin "kelola"-nya.
Modul baru (Aset di fase berikutnya) tinggal memanggil
`$registri->daftarkan('Aset', Aset::class, 'Aset.Kelola')` di
`ServiceProvider`-nya sendiri — tidak menyentuh kelas `RegistriEntitas`.

Dua konsekuensi penting:

- **Isolasi tenant otomatis.** `cariEntitas()` memanggil `$kelas::find($id)`
  polos dan mengandalkan `ScopeOrganisasi` milik model TARGET (bukan
  pengecekan `OrganisasiId` manual) — entitas lintas organisasi otomatis
  404, bukan diam-diam bocor lewat lampiran/tag/komentarnya.
- **Otorisasi seragam.** `pastikanBolehKelola(Pengguna, jenisEntitas)`
  dan varian booleannya `bolehKelola()` dipakai identik oleh
  `LampiranEntitasController`, `EntitasTagController`,
  `DefinisiKolomKustomController`, `NilaiKolomKustomController`, dan
  `KomentarEntitasController` — awalnya masing-masing controller
  menduplikasi method privat 4 baris yang sama, dipindah ke
  `RegistriEntitas` begitu duplikasinya jadi lima kali (bukan
  premature abstraction, tapi konsolidasi pola yang sudah nyata
  terbukti berulang).

`BerkasId` pada `LampiranEntitas` justru satu-satunya kolom yang PUNYA FK
sungguhan (ke tabel `Berkas`), jadi validasi "berkas harus seorganisasi"
otomatis didapat gratis dari `PemeriksaRelasiOrganisasi` (ADR 0002) tanpa
kode tambahan.

## 2. LayananAudit: Korelasi ID per Request, Redaksi Rekursif

`App\Core\Audit\KorelasiId` (scoped singleton) menghasilkan satu ULID per
request HTTP (dari header `X-Korelasi-Id` atau auto-generate), ditetapkan
oleh middleware `TetapkanKorelasiId` yang di-prepend ke stack `web`/`api`.
`LayananAudit::catat()` adalah satu-satunya jalur tulis ke `CatatanAudit`
— dipanggil dari Action, bukan dari controller — dan meredaksi rekursif
semua key yang mengandung `katasandi/password/tokenhash/hashkunci/token/
rahasia/secret` (case-insensitive substring match) dari `DataSebelum`/
`DataSesudah` sebelum disimpan. Toggle global lewat
`config('amanpoll.audit_aktif')`.

**Menutup utang ADR 0003.** `BuatKunciApi`/`CabutKunciApi` sudah memanggil
`LayananAudit::catat()` sekarang (field yang dicatat hanya Nama/
AwalanKunci/Cakupan/dll., `HashKunci` tidak pernah ditulis ke audit).
Selebihnya (Lokasi, UnitOrganisasi, NomorDokumen, dst. dari FASE 03/04)
**sengaja tidak** ditambah audit call secara retroaktif — di luar scope
FASE 05, dan menambah call ke puluhan Action lama tanpa diminta melanggar
prinsip "jangan lompat modul" yang sama seperti ADR sebelumnya. Satu-
satunya action FASE 05 baru yang WAJIB memanggil `LayananAudit` adalah
Komentar (`TambahKomentar`/`UbahKomentar`/`HapusKomentar`) karena TASK.md
eksplisit mendaftarkan "Audit" sebagai butir checklist 05.07-nya sendiri.

## 3. CatatanAudit: DataTable Client-Side Sengaja Tidak Dipakai

Tujuh halaman FASE 04 dikonversi ke `DataTable` client-side penuh (semua
baris di-load sekali, filter/sort di browser) karena datanya kecil per
organisasi (puluhan/ratusan baris pengaturan). `CatatanAudit` tumbuh TANPA
BATAS seiring waktu — memuat semua barisnya ke client adalah anti-pattern
nyata, bukan cuma teoretis. Halaman `Audit/Index` karena itu memakai pola
lama pra-DataTable: `paginate(25)` di server + filter lewat query string
(`jenisEntitas`, `aksi`, `dariTanggal`, `sampaiTanggal`) + komponen
`Pagination` sederhana (dikembalikan dari riwayat git, bentuknya identik
dengan yang dihapus di komit DataTable). Tipe `Paginasi<T>` didaftarkan
lagi di `global.d.ts` dengan komentar yang menjelaskan kenapa ia
dikecualikan dari konvensi DataTable, supaya developer berikutnya tidak
bingung kenapa ada dua pola pagination di codebase yang sama.

## 4. CatatanAkses: Retensi Lintas Organisasi, Bukan Per-Tenant

`CatatanAkses` mencatat login berhasil/gagal (bukan setiap GET biasa —
TASK.md eksplisit minta hindari over-logging) lewat
`LayananCatatanAkses::catat()`, dipanggil dari `LoginController` di tiga
titik: organisasi tidak ditemukan (OrganisasiId null, karena
`KonteksOrganisasi` belum sempat ditetapkan), kredensial salah, dan sukses.

`php artisan catatan-akses:bersihkan` (dijadwalkan harian jam 03:00 lewat
`routes/console.php`, konsisten dengan `queue:prune-failed` dan
`auth:clear-resets` yang sudah ada) menghapus baris lebih tua dari
`config('amanpoll.retensi_catatan_akses_hari')` (default 90). Perintah ini
sengaja memanggil `CatatanAkses::withoutGlobalScope(ScopeOrganisasi::class)`
— retensi adalah operasi sistem terjadwal yang harus menyapu SEMUA
organisasi sekaligus, bukan permintaan tenant tunggal. Tanpa bypass ini,
`ScopeOrganisasi` akan fail-closed (`1=0`, lihat ADR 0002) karena command
artisan berjalan tanpa konteks organisasi aktif, sehingga menghapus nol
baris selamanya. Ini satu-satunya tempat di seluruh codebase yang secara
sengaja melewati `ScopeOrganisasi`.

## 5. Berkas: Nama File dari Klien Tidak Pernah Dipercaya

`UnggahBerkas::jalankan()` membuang nama file asli untuk keperluan path
fisik — nama penyimpanan selalu `{ULID}.{ekstensi}` di mana ekstensi
ditebak dari MIME type sungguhan (`UploadedFile::extension()`), bukan dari
nama yang dikirim klien. `NamaAsli` (untuk ditampilkan ke pengguna) boleh
apa saja karena tidak pernah dipakai membentuk path — dibuktikan
`test_unggah_berkas_berhasil_dengan_nama_penyimpanan_aman` yang mengirim
nama file berisi `../../../etc/`. MIME allowlist dan batas ukuran 10 MB
divalidasi di `SimpanBerkasRequest` (bukan di Action) memakai rule Laravel
bawaan (`mimes:`, `max:`), konsisten dengan pola `UnggahLogoOrganisasiRequest`
dari FASE 04. Disk baca dari `config('amanpoll.disk_berkas')` (default
`local`) dan disimpan eksplisit ke kolom `MediaPenyimpanan` per baris —
kalau default disk berubah di masa depan, file lama tetap terbaca dari
disk yang benar. Disk `s3` sudah mendukung `endpoint`/
`use_path_style_endpoint` custom (S3-compatible) tanpa kode tambahan,
cukup ganti `AMANPOLL_DISK_BERKAS=s3` di environment produksi nanti.

**Upload dan lampirkan digabung jadi satu permintaan.** `BerkasController
@store` menerima `JenisEntitas`/`EntitasId` opsional — kalau diisi,
otorisasi `pastikanBolehKelola()` dicek dan `LampirkanBerkas` dipanggil
dalam permintaan yang sama setelah upload sukses. Ini menghindari pola
dua-request (upload lalu attach terpisah) yang canggung di Inertia (visit
kedua tidak punya akses mudah ke response JSON dari visit pertama).
Endpoint upload polos (tanpa entitas) tetap tersedia untuk kasus
"unggah dulu, putuskan nanti" yang belum ada UI-nya.

**Delete berkas menghapus isi fisik, bukan cuma soft-delete metadata.**
`HapusBerkas` menghapus semua `LampiranEntitas` yang menunjuk ke berkas
itu (hard delete, karena tabel itu tidak punya `DihapusPada`) DAN
menghapus byte fisiknya dari disk, baru men-soft-delete baris `Berkas`
lewat repository. Alasan: "hapus" yang diminta pengguna semestinya
benar-benar membuang isi berkas (bisa berisi data sensitif), sementara
baris `Berkas` yang soft-deleted hanya menyisakan jejak metadata untuk
audit siapa-menghapus-apa-kapan.

**Download diotorisasi lewat entitas induk, bukan kepemilikan berkas.**
`BerkasPolicy::view()`/`delete()` memeriksa apakah pengguna adalah
pengunggah ATAU punya izin kelola di SALAH SATU entitas yang melampirkan
berkas itu (lewat `RegistriEntitas`) — berkas tanpa lampiran (baru
diunggah, belum ditempel ke mana pun) hanya bisa diakses pengunggahnya.
Desain ini otomatis berlaku untuk Aset nanti tanpa policy baru, sama
seperti pola otorisasi lampiran/tag/komentar lainnya.

## 6. KolomKustom: Validasi Dinamis Berbasis TipeData

`DefinisiKolomKustom.TipeData` dibatasi enum (`Teks`, `Angka`, `Tanggal`,
`Boolean`, `Pilihan`, `PilihanGanda`) lewat `Rule::in()` di
`SimpanDefinisiKolomKustomRequest`; `Pilihan` wajib diisi (`required_if`)
kalau tipenya `Pilihan`/`PilihanGanda`. `LayananNilaiKolomKustom::simpan()`
memvalidasi nilai sesuai tipe definisinya sebelum upsert
(`AturanBisnisDilanggar` 422 kalau gagal) — termasuk aturan tambahan
bebas lewat `AturanValidasi` (array rule Laravel biasa, dijalankan lewat
`Validator::make()`). Frontend (`KolomKustomTab`) merender form dinamis
per `TipeData` (input angka/tanggal/checkbox/select/multi-checkbox) tanpa
tahu apa-apa soal domain bisnisnya — komponen yang sama dipakai untuk
Lokasi hari ini dan Aset nanti tanpa perubahan.

## 7. Komentar: Aturan Ubah/Hapus Berlapis

Hanya penulis yang boleh **mengubah** komentarnya sendiri
(`UbahKomentarEntitasRequest` + pengecekan `DibuatOleh` di controller).
**Menghapus** (soft-delete, `KomentarEntitas` punya `DihapusPada`) boleh
oleh penulis ATAU siapa pun yang punya izin kelola entitas induknya
(moderasi) — dua kondisi `OR`, bukan `AND`, supaya admin/manager tetap
bisa membersihkan komentar tidak pantas dari pengguna lain.

## 8. TagController: Satu Endpoint, Dua Bentuk Respons

`TagController@index` dipakai baik oleh halaman admin (`Tag/Index`, render
Inertia) maupun oleh `TagTab` di panel kolaborasi (butuh daftar tag mentah
untuk dropdown assign). Daripada membuat endpoint terpisah untuk data yang
identik, controller membedakan lewat `$request->wantsJson()` — request
axios (yang set header `Accept: application/json`) dapat
`TagResource::collection()` polos, navigasi browser biasa dapat halaman
Inertia penuh. Bug nyata yang pernah terjadi sebelum pola ini dipakai:
`TagTab` sempat memanggil endpoint ini lewat axios dan mendapat HTML
halaman Inertia utuh sebagai `res.data` (string), menyebabkan
`TypeError: r.filter is not a function` yang meng-unmount seluruh Dialog
React tanpa error boundary — ditemukan lewat smoke test Playwright
sungguhan terhadap Chromium, bukan lewat review kode.

## 9. Integrasi Nyata ke Lokasi (Bukti Gate 05)

`PanelKolaborasi` (`resources/js/components/kolaborasi/`) adalah satu
komponen generik dengan 4 tab (Lampiran/Tag/Kolom Kustom/Komentar) yang
menerima `jenisEntitas`+`entitasId` sebagai props — ditempel ke dialog
"Ubah Lokasi" yang sudah ada tanpa mengubah struktur halaman Lokasi lain.
Setiap tab memanggil endpoint JSON generik (`/kolaborasi/lampiran`,
`/kolaborasi/entitas-tag`, dst.) lewat instance axios (`apiKolaborasi`,
mengikuti pola `features/{Domain}/api.ts` yang sudah di-scaffold sejak
awal proyek untuk modul yang belum dibangun). Dibuktikan lewat smoke test
Playwright end-to-end: unggah berkas, tempel tag, isi kolom kustom, dan
tambah komentar pada Lokasi sungguhan — nol error JavaScript.

Dibuktikan juga lewat test backend: `tests/Feature/IntegrasiAudit/
CatatanAuditControllerTest.php`, `CatatanAksesTest.php`,
`tests/Feature/Kolaborasi/{BerkasLampiranTest,TagTest,KolomKustomTest,
KomentarEntitasTest}.php`, dan `tests/Feature/Core/Entitas/
RegistriEntitasTest.php` (183 test lulus total, PHPStan level 7 bersih).

### Gate 05

Aset nanti dapat langsung memakai `RegistriEntitas::daftarkan('Aset', ...)`
untuk otomatis mendapat lampiran berkas, tag, kolom kustom, komentar, dan
audit trail — tanpa menyentuh kelas `RegistriEntitas`, tanpa policy baru
per fitur, dan tanpa komponen frontend baru (`PanelKolaborasi` dipakai
apa adanya dengan `jenisEntitas="Aset"`).
