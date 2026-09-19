# ADR 0010 — Persediaan dan Suku Cadang

Status: Diterima
Fase: TASK.md FASE 10

## Konteks

FASE 10 dikerjakan sebelum Perintah Kerja penuh (FASE 12) supaya pemakaian
suku cadang tidak "ditambal belakangan" ke domain aset/pemeliharaan yang
sudah stabil. Gate-nya eksplisit dan sempit: "Tidak ada endpoint yang
mengubah `StokSukuCadang` langsung tanpa transaksi mutasi yang sah" --
artinya satu tabel (saldo stok) menjadi satu-satunya hal yang benar-benar
harus dijaga ketat; semua tabel lain (Gudang, master SukuCadang,
Kompatibilitas) adalah data pendukung berisiko rendah di sekitarnya.

Domain ini sudah pra-terscaffold sejak fase migrasi awal (11 tabel, 11
Model/Request/Resource/Repository placeholder) -- pola yang sama seperti
`SiklusAset` sebelum FASE 09 mengisinya. Konsisten dengan preseden itu,
Actions di sini memanggil Eloquent Model langsung dan TIDAK melalui
Repository interface yang sudah dibuatkan; layer Repository dibiarkan
sebagai infrastruktur generik untuk kebutuhan query di fase mendatang.

## 1. Satu Fungsi Penjaga Gerbang: PostingMutasiStok

Gate 10 diwujudkan bukan lewat validasi tersebar di banyak tempat, tapi
lewat SATU titik masuk tulis: `PostingMutasiStok::jalankan()`. Tidak ada
Controller, Request, atau Policy untuk `StokSukuCadang` yang menerima
`store`/`update`/`destroy` -- `StokSukuCadangController` hanya
mengimplementasikan `index()`, dan `StokSukuCadangPolicy` hanya
mengimplementasikan `viewAny()`. Ini ditegakkan STRUKTURAL, bukan lewat
konvensi yang bisa dilupakan: rute untuk mengubah `StokSukuCadang` secara
langsung TIDAK PERNAH terdaftar sama sekali, sehingga percobaan `POST`
ke `/stok-suku-cadang` mendapat 405 (method tidak diizinkan pada URI yang
sama dengan `GET` index) dan `PUT`/`DELETE` ke `/stok-suku-cadang/{id}`
mendapat 404 (rute itu tidak pernah ada) -- dibuktikan langsung lewat test
`test_gate_10_tidak_ada_endpoint_tulis_langsung_untuk_stok_suku_cadang`.
Setiap baris `StokSukuCadang.JumlahTersedia` HANYA pernah ditulis dari
dalam `PostingMutasiStok::ubahSaldo()`, dan setiap baris `JumlahDitahan`
HANYA dari `LayananSaldoReservasi::ubahDitahan()` -- dua fungsi privat
kecil yang mudah diaudit lengkap, bukan tersebar di banyak Action.

## 2. Lock-or-Create dengan Fallback Race, Bukan Upsert Naif

`StokSukuCadang` diidentifikasi oleh kombinasi (Gudang, LokasiGudang,
SukuCadang, KelompokSukuCadang) yang dua kolom terakhirnya nullable --
constraint unique di database memperlakukan setiap kombinasi NULL sebagai
berbeda (perilaku standar MySQL/SQLite), sehingga tidak bisa diandalkan
sendirian untuk mencegah baris ganda pada percobaan pertama sebuah
kombinasi. `PostingMutasiStok::ubahSaldo()` mengatasi ini dengan pola
lock-atau-buat: `lockForUpdate()` dulu pada query dengan `where()` eksplisit
untuk setiap kolom (termasuk kolom nullable, yang secara otomatis dikonversi
Laravel menjadi `whereNull()`); kalau tidak ditemukan, `create()` baris baru
dengan saldo nol; kalau `create()` gagal karena race (`QueryException` dari
constraint unique yang baru saja dipenuhi request lain), query yang sama
diulang dengan lock supaya request kedua mengambil baris yang baru dibuat
request pertama alih-alih gagal total. Ini bukan proteksi sempurna terhadap
setiap kemungkinan race (mis. dua request pertama-kali yang benar-benar
simultan pada milidetik yang sama masih punya jendela sempit), tapi cukup
untuk beban normal aplikasi CMMS single-tenant-per-request ini --
keterbatasan ini didokumentasikan secara sadar, bukan diasumsikan sempurna.

## 3. Konvensi Tanda Kuantitas per Jenis Mutasi

`DetailMutasiStok.Jumlah` TIDAK selalu berarti "jumlah fisik positif" --
maknanya bergantung pada `MutasiStok.Jenis` header-nya:
- Penerimaan/Pengeluaran/Transfer/Return: `Jumlah` harus > 0 (ditegakkan di
  `TambahDetailMutasiStok`), arah pergerakan (tambah/kurang, dari/ke gudang
  mana) ditentukan oleh `Jenis` header, bukan tanda pada `Jumlah`.
- Adjustment: `Jumlah` BOLEH negatif (susut/hilang) atau positif (temuan
  lebih), diterapkan LANGSUNG sebagai delta ke `GudangAsalId` tanpa
  gudang lawan -- satu-satunya jenis mutasi yang benar-benar
  memperlakukan `Jumlah` sebagai delta bertanda, bukan magnitude.

Keputusan ini menghindari kebutuhan kolom "Arah"/"Tanda" terpisah yang
tidak ada di skema, dan konsisten dengan makna alami "penyesuaian stok
opname" di dunia nyata (satu baris bisa mewakili koreksi naik atau turun).
PRD mewajibkan Adjustment menyertakan alasan -- ditegakkan di
`BuatMutasiStok::jalankan()` yang melempar `AturanBisnisDilanggar` kalau
`Jenis=Adjustment` dan `Catatan` kosong, sebelum draft sempat dibuat.

## 4. Stok Negatif: Ditolak Default, Override Dua Syarat

PRD eksplisit: "Stok negatif ditolak kecuali organisasi mengaktifkan
override dan user memiliki izin" -- dua syarat, BUKAN salah satu.
`PostingMutasiStok::izinkanStokNegatif()` membaca baris
`KonfigurasiOrganisasi` berkunci `Persediaan.IzinkanStokNegatif` (pola
key-value generik dari FASE 04, tidak perlu migrasi kolom baru), DAN
`jalankan()` mengecek `PemeriksaIzin::boleh($dipostingOleh, 'Stok.Override')`
-- kode Izin baru yang ditambahkan fase ini (`Stok.Kelola` sendiri, yang
sudah diseed sejak FASE 00/01, TIDAK cukup; override adalah kemampuan
terpisah yang sengaja lebih sempit). Kedua syarat dicek dengan `&&`, jadi
mengaktifkan konfigurasi organisasi saja tanpa memberi izin ke pengguna
tertentu tidak membuka apa pun bagi pengguna itu, dan sebaliknya.

## 5. Reservasi: Hold di Level Gudang, Bukan Sub-Lokasi/Batch

`ReservasiSukuCadang` hanya punya `GudangId`+`SukuCadangId` (tanpa
`LokasiGudangId`/`KelompokSukuCadangId`), berbeda dari `StokSukuCadang`
yang granular sampai sub-lokasi dan batch. `LayananSaldoReservasi`
menyelesaikan ketidakcocokan granularitas ini dengan menulis SEMUA hold
`JumlahDitahan` ke SATU baris "default" per Gudang+SukuCadang
(`LokasiGudangId`+`KelompokSukuCadangId` keduanya NULL, dibuat otomatis
bila belum ada), sementara kuantitas tersedia-bersih untuk pengecekan
over-reservation dihitung sebagai AGREGAT lintas SEMUA baris
`StokSukuCadang` milik Gudang+SukuCadang itu (menjumlah `JumlahTersedia`
di semua sub-lokasi/batch, dikurangi total `JumlahDitahan`). Konsekuensinya:
reservasi tidak menjanjikan batch atau sub-lokasi tertentu, hanya
menjanjikan bahwa gudang itu SECARA TOTAL punya cukup stok -- keputusan
lingkup yang wajar mengingat `ReservasiSukuCadang.PerintahKerjaId` nullable
dan kebutuhan reservasi (menahan kuota untuk perintah kerja mendatang)
umumnya belum tahu batch mana yang akan dipetik saat barang benar-benar
diambil.

## 6. Consume Reservasi Membuat MutasiStok Sungguhan, Bukan Menulis Saldo Langsung

`KonsumsiReservasiSukuCadang` SENGAJA tidak menulis `JumlahTersedia`
secara langsung (yang akan melanggar Gate 10 sendiri) -- ia justru
memanggil `BuatMutasiStok`+`TambahDetailMutasiStok`+`PostingMutasiStok`
secara terprogram untuk membuat dokumen Pengeluaran resmi (dengan
`ReferensiJenis='ReservasiSukuCadang'`+`ReferensiId` menunjuk balik ke
reservasinya), lalu melepas hold `JumlahDitahan`. Efeknya: bahkan jalur
"internal" yang dipicu dari luar `MutasiStokController` tetap wajib lewat
`PostingMutasiStok`, menjaga Gate 10 benar-benar tanpa pengecualian
tersembunyi, dan tetap menghasilkan dokumen `MutasiStok` yang bisa diaudit
seperti pengeluaran manual biasa.

## 7. PemakaianSukuCadang: Sengaja Di Luar Lingkup Fase Ini

TASK.md FASE 10 (10.01-10.07) tidak menyebut "Pemakaian" sama sekali --
tabel `PemakaianSukuCadang` ada di skema (dan di PRD) tapi checklist-nya
eksplisit tidak. Menu/Controller/Policy untuk mencatat pemakaian suku
cadang pada perintah kerja tertentu BELUM dibangun fase ini, dengan
alasan: (1) UX aslinya melekat pada layar Perintah Kerja yang belum ada
sampai FASE 12, (2) aturan PRD "pemakaian menambah biaya pekerjaan bila
harga tersedia" butuh model `BiayaPerintahKerja` yang juga masih berupa
placeholder pra-scaffold. Model `PemakaianSukuCadang` tetap diberi PHPDoc
relasi yang benar (konsisten dengan seluruh domain ini) supaya tidak
membebani baseline PHPStan, tapi Action/Controller/Policy-nya sengaja
dibiarkan sebagai placeholder untuk diisi bersamaan dengan FASE 12. Fitur
10.06 "Reservasi" tetap lengkap tanpa bergantung pada tabel ini karena
`ReservasiSukuCadang.PerintahKerjaId` nullable dan konsumsinya sudah
menghasilkan jejak `MutasiStok` sendiri (lihat bagian 6).

## 8. Satu Kode Izin untuk Seluruh Domain

Mengikuti pola `Pemeliharaan.Kelola`/`Penyedia.Kelola`/`Kontrak.Kelola`
(satu kode Izin kasar per domain), bukan pola granular `Aset.Lihat/Buat/
Ubah/Hapus`: `Stok.Kelola` (sudah diseed sejak FASE 00/01, mengantisipasi
fase ini) dipakai di SELURUH delapan Policy domain Persediaan
(`GudangPolicy`, `KategoriSukuCadangPolicy`, `SukuCadangPolicy`,
`KelompokSukuCadangPolicy`, `KompatibilitasSukuCadangPolicy`,
`StokSukuCadangPolicy`, `MutasiStokPolicy`, `ReservasiSukuCadangPolicy`) --
delapan kelas kecil identik-pola (bukan satu Policy dibagi ke banyak Model,
mengikuti preseden satu-Policy-per-Model dari `Aset`/`SiklusAset`), semuanya
memeriksa kode yang sama. Satu kode BARU ditambahkan (`Stok.Override`,
`01JAMANPOLL000000000000019`) khusus untuk syarat kedua penyesuaian stok
negatif (bagian 4) -- dipisah dari `Stok.Kelola` karena secara sengaja
harus lebih sempit daripada sekadar bisa mengelola gudang/suku cadang.

## 9. Peringatan Stok Minimum: Command Terjadwal, Widget di Dalam Modul

10.07 "Stock Alert" diwujudkan sebagai command terjadwal
(`suku-cadang:peringatan-stok-minimum`, harian jam 07:00) yang menghitung
stok bersih teragregasi per `SukuCadang` lintas seluruh `Gudang` dalam
satu organisasi, membandingkan terhadap `StokMinimum`, lalu memakai
`LayananNotifikasi` (infrastruktur FASE 06, kode peristiwa baru
`Stok.MinimumTercapai` ditambahkan ke `KatalogPeristiwaNotifikasi`) untuk
mengirim ke setiap pengguna berizin `Stok.Kelola` dalam organisasi itu --
pola query lintas-organisasi yang sama dengan
`catatan-akses:bersihkan` (`Organisasi::query()->cursor()`, tanpa
mengandalkan `KonteksOrganisasi` per-request). "Dashboard widget" dari
checklist diwujudkan sebagai banner peringatan pada halaman
`SukuCadang/Index.tsx` itu sendiri (bukan halaman Dashboard global) --
Dashboard lintas-modul adalah lingkup FASE 21 "Dashboard dan Laporan";
menambahnya di sini akan mendahului fase yang memang dirancang untuk itu.
Reservasi kedaluwarsa (10.06 "Expiry") memakai pola command terjadwal yang
sama (`reservasi-suku-cadang:kedaluwarsakan`, tiap jam), melepas hold
`JumlahDitahan` untuk reservasi yang `KadaluarsaPada`-nya sudah lewat tapi
masih berstatus `Aktif`.

## 10. Bukti Gate 10

Smoke test Playwright menjalankan siklus penuh: admin membuat Gudang +
LokasiGudang (Rak A), Kategori Suku Cadang, dan Suku Cadang (Filter Oli) ->
membuat MutasiStok Penerimaan 50 unit ke Gudang Utama -> posting -> saldo
`StokSukuCadang` naik jadi 50 -> membuat Reservasi 10 unit -> memakai
(konsumsi) reservasi itu, yang secara otomatis membuat + memposting
MutasiStok Pengeluaran 10 unit -> halaman Stok menunjukkan saldo fisik
akhir 40 dengan hold kembali ke 0, tepat sesuai perhitungan manual.
Navigasi mobile (`Sheet` sidebar) menampilkan seluruh enam menu baru
(Gudang/Kategori Suku Cadang/Suku Cadang/Stok/Mutasi Stok/Reservasi)
dengan proteksi Izin yang benar. Dibuktikan juga lewat test backend:
`tests/Feature/Persediaan/PersediaanTest.php` (10 test: CRUD Gudang +
LokasiGudang dengan hierarki sirkular ditolak, CRUD Kategori+Suku Cadang
dengan guard dependency, Kompatibilitas dengan dedupe dan lookup per aset,
Penerimaan menaikkan stok secara idempotent, Pengeluaran ditolak saat
stok tidak cukup kecuali override dua-syarat aktif, Transfer memindahkan
saldo persis antar gudang, Adjustment mewajibkan Catatan, Reservasi
mencegah over-reservation dan konsumsinya benar-benar mengurangi stok
fisik lewat MutasiStok Pengeluaran, Release mengembalikan hold, dan test
Gate 10 eksplisit yang membuktikan tidak ada rute tulis langsung ke
`StokSukuCadang` sama sekali baik lewat 404 maupun 405) -- 250 test lulus
total, PHPStan level 7 bersih (0 error, 0 baseline tak terpakai),
`tsc --noEmit` bersih, `npm run build` sukses.

### Gate 10

Tidak ada endpoint yang mengubah `StokSukuCadang` langsung tanpa transaksi
mutasi yang sah -- satu-satunya jalur penulisan saldo adalah
`PostingMutasiStok` (dipicu manual atau lewat konsumsi reservasi) dan
`LayananSaldoReservasi` untuk kolom hold, keduanya selalu di dalam
transaksi database dan selalu meninggalkan jejak dokumen `MutasiStok`
atau `ReservasiSukuCadang` yang bisa diaudit.
