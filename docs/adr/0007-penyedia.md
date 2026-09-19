# ADR 0007 — Penyedia

Status: Diterima
Fase: TASK.md FASE 07

## Konteks

FASE 07 membangun master data Penyedia (vendor/supplier) — bukti Gate-nya
eksplisit: "Penyedia dapat digunakan oleh aset, procurement, kontrak, dan
kalibrasi." Artinya domain `Penyedia` harus selesai sebagai referensi
mandiri SEBELUM modul manapun yang menunjuk ke sana dibangun, persis pola
"bangun fondasi dulu" yang sama seperti `Persetujuan`/`Notifikasi` di
FASE 06 dan `RegistriEntitas` di FASE 05.

## 1. Lima Tabel, Satu Bounded Context, Pola Rewrite yang Sama

Kelima tabel (`KategoriPenyedia`, `Penyedia`, `PenyediaKategori`,
`KontakPenyedia`, `PenilaianPenyedia`) sudah punya migration + model +
DTO/Repository placeholder + Request/Resource kosong sejak scaffold awal
proyek. Konsisten dengan precedent FASE 05/06: DTO (`PenyediaData`, dst.)
dan Repository interface/impl dibiarkan TIDAK dipakai (sengaja, bukan
lupa) — Action CRUD sederhana memanggil Eloquent langsung
(`Penyedia::create()`, `$penyedia->fill()->save()`), mengikuti pola
`BuatLokasi`/`UbahLokasi` dari FASE 04. Semua Request/Resource ditulis
ulang penuh dari placeholder `['sometimes']`/`parent::toArray()`.

## 2. Penyedia Terdaftar di RegistriEntitas — Bukan Sistem Lampiran Baru

`Penyedia` didaftarkan ke `RegistriEntitas` (`'Penyedia' => Penyedia::class
=> 'Penyedia.Kelola'`) di `AmanpollServiceProvider::boot()`, tepat seperti
`UnitOrganisasi` dan `Lokasi` sebelumnya. Konsekuensinya: lampiran berkas,
tag, kolom kustom, dan komentar pada Penyedia otomatis berfungsi lewat
`PanelKolaborasi` yang sama tanpa satu baris kode baru di domain
`Kolaborasi` — dibuktikan langsung di tab "Kolaborasi" pada dialog Kelola
Penyedia. Ini adalah bukti kedua (setelah Lokasi di FASE 05) bahwa desain
`RegistriEntitas` benar-benar generalizable ke modul yang ditulis
belakangan, bukan cuma bekerja untuk kasus yang memotivasi
pembuatannya.

## 3. PenyediaKategori: Pivot Tanpa OrganisasiId, Isolasi Tenant Lewat Induk

Tabel pivot `PenyediaKategori` (many-to-many `Penyedia` ⋈
`KategoriPenyedia`) sengaja TIDAK punya kolom `OrganisasiId` sendiri di
skema — isolasi tenant-nya transitif lewat FK ke `Penyedia` (yang
`OrganisasiId`-nya di-scope oleh `ScopeOrganisasi`). Konsekuensi desain:
setiap operasi pivot (`TambahkanKategoriKePenyedia`,
`LepaskanKategoriDariPenyedia`) HARUS dimulai dari relasi
`$penyedia->penyediaKategori()`, bukan query `PenyediaKategori::query()`
langsung — kalau langsung, tidak ada scope organisasi yang
melindunginya sama sekali. `PenyediaKategoriController` menegakkan ini
lewat route-model-binding `Penyedia $penyedia` yang sudah tenant-scoped
otomatis (ADR 0002), sehingga `KategoriPenyedia $kategoriPenyedia` di
route hanya dipakai untuk `Id`-nya, tidak pernah untuk query langsung.
Attach bersifat idempoten (cek baris ada dulu sebelum insert, mengikuti
pola `TambahkanTagKeEntitas` dari FASE 05) supaya klik ganda pada
checkbox kategori tidak menghasilkan constraint-violation.

## 4. KontakPenyedia: Invariant "Satu Kontak Utama" Ditegakkan di Action, Bukan Database

Skema tidak punya unique-partial-index untuk memaksa "hanya satu
`Utama=true` per Penyedia" (MySQL/SQLite tidak mendukung itu secara
portable tanpa generated column). `BuatKontakPenyedia`/`UbahKontakPenyedia`
menegakkannya secara eksplisit: sebelum insert/update dengan
`Utama=true`, baris lain milik `Penyedia` yang sama di-set `Utama=false`
dulu, dibungkus `TransaksiDatabase` supaya kedua langkah atomik (mencegah
race condition dua kontak sama-sama `Utama=true` bila dua request
bersamaan). Ini pola yang sama seperti alasan `TransaksiDatabase` dipakai
di `SetujuiPermintaanPersetujuan`/`TolakPermintaanPersetujuan` FASE 06 —
multi-write yang harus konsisten dibungkus transaksi eksplisit, bukan
mengandalkan constraint DB yang tidak bisa mengekspresikan aturannya.

## 5. PenilaianPenyedia: SkorTotal Dihitung Otomatis, Bukan Input Manual

Meski kolom `SkorTotal` ada di skema sebagai kolom independen,
`BuatPenilaianPenyedia` TIDAK menerimanya sebagai input pengguna — ia
dihitung sebagai rata-rata dari komponen skor (Kualitas/KetepatanWaktu/
Harga/Layanan) yang diisi, mengabaikan komponen yang `null` (penilai
boleh mengisi sebagian komponen saja). Keputusan desain: mencegah
inkonsistensi "SkorTotal diisi manual tapi tidak cocok dengan
komponennya" — satu sumber kebenaran untuk perhitungan agregat,
konsisten dengan prinsip "jangan biarkan business logic bocor ke
controller/request" karena perhitungan ini murni domain, bukan validasi
input. `DinilaiOleh` juga diisi otomatis dari `$request->user()->Id`,
tidak pernah dari input klien — mencegah seseorang mengklaim penilaian
atas nama pengguna lain.

**"Rekap" diwujudkan sebagai agregat runtime, bukan kolom tersimpan.**
`PenilaianPenyediaController::index()` mengembalikan `histori` (daftar
lengkap, terbaru dulu) DAN `rekap` (`SkorTotalRataRata`,
`JumlahPenilaian`) dalam satu response JSON — dihitung on-the-fly dari
koleksi yang sama (`$histori->avg('SkorTotal')`), bukan denormalisasi ke
kolom baru di `Penyedia`. Untuk skala organisasi tunggal (puluhan
penilaian per penyedia, bukan jutaan), agregasi runtime ini cukup murah
dan menghindari masalah konsistensi cache yang harus di-invalidate
setiap kali penilaian baru ditambahkan.

## 6. Checklist "Status" pada KategoriPenyedia: Keterbatasan Skema yang Diakui

TASK.md 07.01 mendaftarkan "Status" sebagai butir checklist
`KategoriPenyedia`, tapi skema tabel (`Id`, `OrganisasiId`, `Kode`,
`Nama`, `DibuatPada`) tidak punya kolom untuk itu — konsisten dengan
pola FASE 06 (`KondisiAktivasi` tanpa mesin evaluasi, rejection
fail-fast karena keterbatasan kolom `TahapPersetujuan`), gap ini diakui
eksplisit di sini alih-alih diam-diam diabaikan. `KategoriPenyedia` hanya
CRUD Kode/Nama + validasi penggunaan (tidak bisa dihapus bila masih
dipakai `PenyediaKategori`, meniru pola `HapusKategoriLokasi`). Tidak ada
migration baru ditambahkan untuk menutup gap ini — mengubah skema yang
sudah di-generate dari SQL sumber-kebenaran di luar scope FASE 07 dan
akan diperlakukan sebagai perubahan skema terpisah bila benar-benar
dibutuhkan modul lain nanti.

## 7. Frontend: Satu Dialog Bertab untuk Satu Agregat, Bukan Halaman Terpisah

`Penyedia/Index.tsx` memakai satu `DataTable` daftar penyedia (kolom
Nama/Kategori/Kontak/Status), dengan aksi "Kelola" membuka SATU dialog
bertab (`Info`/`Kategori`/`Kontak`/`Penilaian`/`Kolaborasi`) alih-alih
lima halaman terpisah — Penyedia adalah satu agregat dengan beberapa
relasi anak (kontak, penilaian, kategori), bukan lima entitas independen
yang butuh navigasi halaman sendiri-sendiri. Pola ini memperluas
pendekatan `DialogFormLokasi` (form + `PanelKolaborasi` ditumpuk vertikal)
dari FASE 04/05 menjadi dialog bertab karena jumlah relasi anak yang
harus ditampilkan (3 tab tambahan: Kategori, Kontak, Penilaian) sudah
terlalu banyak untuk ditumpuk vertikal dalam satu dialog tanpa scroll
berlebihan. Tab Kategori/Kontak/Penilaian masing-masing memanggil endpoint
JSON sendiri lewat `apiPenyedia` (bukan di-preload di payload index
halaman) — konsisten dengan alasan yang sama seperti keputusan FASE 06
untuk `PermintaanPersetujuan`: data relasi anak per baris HANYA
dibutuhkan saat dialog itu dibuka, bukan saat memuat seluruh daftar
penyedia, sehingga payload `index()` tetap ringan berapa pun banyaknya
penyedia dan kontak/penilaian yang mereka punya.

## 8. Bukti Gate 07

Smoke test Playwright menjalankan siklus penuh terhadap Chromium
sungguhan: login → buat kategori penyedia → buat penyedia baru → buka
dialog Kelola → assign kategori (tab Kategori) → tambah kontak (tab
Kontak) → tambah penilaian dengan verifikasi `SkorTotal` terhitung
otomatis dan rekap rata-rata muncul (tab Penilaian) → tab Kolaborasi
merender `PanelKolaborasi` (Lampiran/Tag/Kolom Kustom/Komentar) tanpa
kode tambahan → badge kategori muncul di daftar setelah dialog ditutup —
nol error JavaScript. Dibuktikan juga lewat test backend:
`tests/Feature/Penyedia/PenyediaTest.php` (7 test: otorisasi, CRUD +
soft-delete, keunikan Kode per organisasi, validasi-penggunaan kategori,
invariant satu-kontak-utama, perhitungan SkorTotal + rekap, dan isolasi
lintas organisasi) — 214 test lulus total, PHPStan level 7 bersih,
`tsc --noEmit` bersih.

### Gate 07

Modul Aset (FASE 08), Pengadaan, Kontrak, dan Kalibrasi nanti dapat
langsung menunjuk ke `Penyedia::class` (mis. kolom `PenyediaId` pada
tabel mereka sendiri, sudah ada di skema masing-masing) tanpa menyentuh
domain `Penyedia` — data identitas, kategori, kontak, riwayat penilaian,
dan lampiran/tag/kolom kustom/komentar penyedia sudah lengkap dan siap
dipakai sebagai referensi read-only dari modul manapun.
