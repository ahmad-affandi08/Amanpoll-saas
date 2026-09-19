# ADR 0008 — Master Aset

Status: Diterima
Fase: TASK.md FASE 08

## Konteks

FASE 08 adalah fase pertama yang menyentuh aset fisik sungguhan --
sebelas tabel (`KategoriAset`, `Merek`, `ModelAset`, `Aset`, `RelasiAset`,
`RiwayatLokasiAset`, `RiwayatPenanggungJawabAset`, `GaransiAset`,
`NilaiAset`, `MeterAset`, `PembacaanMeterAset`) yang menjadi fondasi
seluruh modul lifecycle (FASE 09), pemeliharaan, kalibrasi, dan
pengadaan berikutnya. Gate-nya eksplisit: "Detail aset menampilkan
identitas dan histori inti secara benar sebelum lifecycle/maintenance
ditambahkan" -- artinya fase ini harus tuntas sebagai fondasi murni,
tanpa mencicil logika modul yang belum waktunya dibangun.

## 1. Registry, Bukan Sistem Baru, Kali Ketiga

`Aset` (dan `GaransiAset` untuk lampiran dokumen garansi) didaftarkan ke
`RegistriEntitas` (`'Aset' => Aset::class => 'Aset.Ubah'`,
`'GaransiAset' => GaransiAset::class => 'Aset.Ubah'`) di
`AmanpollServiceProvider::boot()`, pola identik dengan `Lokasi` (FASE 05),
`Penyedia` (FASE 07). Ini pembuktian KETIGA bahwa `RegistriEntitas`
benar-benar generalizable lintas fase tanpa modifikasi kelasnya sendiri
-- Aset otomatis mendapat lampiran berkas/tag/kolom kustom/komentar lewat
`PanelKolaborasi` yang sama persis, ditempel di tab "Kolaborasi" pada
halaman detail. Kode Izin yang dipakai (`Aset.Ubah`) sengaja granular
sesuai kode yang sudah diseed sejak awal proyek (`Aset.Lihat/Buat/Ubah/
Hapus`), bukan pola "satu kode Kelola" seperti domain lain -- `AsetPolicy`
memetakan `viewAny`/`view` -> `Aset.Lihat`, `create` -> `Aset.Buat`,
`update` -> `Aset.Ubah`, `delete` -> `Aset.Hapus`; `KategoriAset`, `Merek`,
`ModelAset` (master data pendukung) memakai kode granular yang sama
karena mereka prasyarat langsung bagi Aset, bukan domain independen.

## 2. KategoriAset: Default Property Diwariskan Saat Registrasi, Bukan Referensi Berjalan

Checklist 08.01 minta "default property bila relevan" --
`KategoriAset.UmurManfaatBulan`/`MetodePenyusutanBawaan`/
`PersentaseNilaiResidu` diwujudkan sebagai default yang DISALIN ke Aset
saat `BuatAset::jalankan()` (bukan dirujuk secara live selamanya):
`UmurManfaatBulan`/`MetodePenyusutan` Aset diisi dari Kategori HANYA
kalau pengguna tidak mengisinya sendiri (`??=`), dan `NilaiResidu`
dihitung (`HargaPerolehan x PersentaseNilaiResidu / 100`) kalau
`HargaPerolehan` diisi tapi `NilaiResidu` tidak. Setelah tersimpan, nilai
di baris Aset adalah milik Aset itu sendiri -- mengubah default di
Kategori nanti TIDAK mengubah Aset yang sudah terdaftar. Keputusan ini
konsisten dengan cara kerja "default" pada umumnya (nilai awal, bukan
binding permanen) dan menghindari efek samping tak terduga kalau admin
mengubah kebijakan penyusutan kategori di kemudian hari.

Hierarki `KategoriAset` (`IndukId`, soft-delete) memakai ulang
`PemeriksaHierarkiSirkular` yang sama dari FASE 04 (`UnitOrganisasi`,
`Lokasi`) tanpa modifikasi -- pembuktian keempat kelas itu genuinely
reusable untuk tabel self-referencing manapun.

## 3. Merek: Katalog Organisasi, Bukan Platform Global

`Merek.OrganisasiId` bersifat nullable di skema, tapi FASE ini SENGAJA
tidak membangun jalur untuk membuat baris `OrganisasiId=null` (katalog
merek lintas-tenant) -- setiap Merek yang dibuat lewat aplikasi ini
selalu tenant-owned (`MilikOrganisasi` mengisi `OrganisasiId` otomatis).
Alasan teknis: `ScopeOrganisasi` melakukan `WHERE OrganisasiId = X` ketat
(bukan `IS NULL OR = X`), sehingga baris global tidak akan pernah
terlihat lewat query bertenant normal -- kolom nullable ini kemungkinan
disiapkan untuk seed data platform-wide di masa depan lewat jalur lain
(mis. seeder langsung), bukan sesuatu yang perlu didukung UI sekarang.
Duplicate prevention memakai `Rule::unique` biasa per organisasi (bukan
pencocokan case-insensitive custom) -- "wajar" sesuai checklist, konsisten
dengan pola keunikan `Kode` di domain lain.

## 4. Aset.Versi: Optimistic Locking Sungguhan, Bukan Kolom Dekoratif

Kolom `Versi` (`unsignedInteger`, default 1) sudah ada di skema sejak
awal tapi jelas menyarankan optimistic concurrency control. FASE ini
mengimplementasikannya sungguhan: `UbahAset` membandingkan `Versi` yang
dikirim klien terhadap `Versi` di database SEBELUM `fill()`+`save()` --
kalau berbeda, `KonflikData` (409) dilempar sebelum perubahan apa pun
ditulis, dan `Versi` di-increment tepat 1 setiap update sukses. Frontend
(`TabInfo` di `Aset/Show.tsx`) mengirim `Versi` yang dia terima dari
`AsetResource` -- kalau dua pengguna membuka detail aset yang sama lalu
menyimpan berurutan, pengguna kedua mendapat error jelas alih-alih
diam-diam menimpa perubahan pengguna pertama. Ini kolom skema pertama di
seluruh codebase yang secara eksplisit dipakai untuk optimistic locking
(bukan hanya versi/timestamp yang tidak divalidasi).

## 5. LokasiId: Satu Titik Masuk Wajib, Ditegakkan di Level Action

Checklist 08.05 minta "current location konsisten dengan history" --
ditegakkan STRUKTURAL, bukan lewat validasi tambahan: `UbahAset::jalankan()`
secara eksplisit membuang key `LokasiId` dari `$data` sebelum `fill()`
(`unset($data['Versi'], $data['LokasiId'], $data['KodeQr'])`), sehingga
form edit umum TIDAK PERNAH bisa mengubah lokasi aset betapa pun field itu
dikirim klien. Satu-satunya jalur yang boleh mengubah `Aset.LokasiId`
adalah `PindahkanLokasiAset::jalankan()`, yang SELALU menulis baris
`RiwayatLokasiAset` dalam transaksi yang sama sebelum mengubah kolom itu.
Konsekuensinya: mustahil ada state di mana `Aset.LokasiId` berubah tanpa
jejak histori yang menyertainya -- bukan aturan yang bisa dilanggar
manusia lupa memanggil endpoint yang benar, karena endpoint yang salah
(`PUT /aset/{id}`) secara diam-diam mengabaikan field itu. `KodeQr` diberi
perlakuan sama (tidak bisa diubah lewat edit umum) supaya identifier fisik
yang sudah dicetak di label tidak pernah berubah tanpa jalur regenerasi
eksplisit (belum dibangun fase ini, konsisten dengan lingkup "generate"
saja pada checklist 08.04).

`BuatAset` menulis baris `RiwayatLokasiAset` pertama
(`JenisPerpindahan=Registrasi`, `LokasiAsalId=null`) dalam transaksi yang
sama dengan pembuatan Aset itu sendiri -- checklist "set lokasi awal"
dipenuhi tanpa aset baru pernah melewati state "punya lokasi tapi belum
punya histori barang sedetik pun".

## 6. Generate Identifier/QR: Token Buram, Bukan Sequence Bermakna

`KodeQr` diisi otomatis saat registrasi (`(string) Str::ulid()`) --
BUKAN nomor urut lewat `LayananNomorDokumen` (FASE 04). Alasan: nomor
dokumen sequential (mis. `AST/2026/001`) dirancang untuk NOMOR YANG
DIBACA MANUSIA pada dokumen resmi, sedangkan `KodeQr` adalah token
pemindaian internal yang tidak perlu berurutan atau bermakna -- yang
penting unik dan pendek-cukup untuk dicetak sebagai QR fisik. Memaksa
`KodeAset` (yang memang dibuat manual/deterministik oleh pengguna) untuk
melalui `LayananNomorDokumen` akan berarti setiap organisasi WAJIB
mengatur pola `NomorDokumen` untuk jenis "Aset" sebelum bisa mendaftarkan
aset satu pun -- ketergantungan tersembunyi yang tidak diminta checklist
manapun. `AsetPindaiController::tampilkan()` adalah scan resolver:
menerima kode dari kamera QR/pembaca barcode/pembaca NFC secara SERAGAM
lewat satu endpoint (`WHERE KodeQr = ? OR KodeBatang = ? OR NfcUid = ?`),
lalu redirect ke halaman detail -- satu URL yang bisa dicetak sebagai
satu jenis label QR meski perangkat pemindai fisiknya berbeda-beda.

## 7. RelasiAset: Deteksi Siklus Berbasis Graf, Bukan Kolom Tunggal

`PemeriksaHierarkiSirkular` (dipakai ulang untuk `KategoriAset.IndukId`)
TIDAK dipakai di sini -- `RelasiAset` adalah tabel edge graf umum (satu
Aset bisa punya banyak relasi `Komponen` sekaligus, bukan satu kolom induk
tunggal seperti hierarki kategori/unit/lokasi). `BuatRelasiAset` menulis
BFS sendiri: menambah edge (induk -> anak) hanya ditolak kalau `induk`
sudah bisa DICAPAI dari `anak` lewat rantai relasi `Komponen` yang sudah
ada (artinya edge baru akan membuat siklus). Pengecekan ini HANYA berlaku
untuk `JenisRelasi=Komponen` (hierarki fisik sungguhan, mis. mesin terdiri
atas beberapa sub-komponen) -- `JenisRelasi=Terkait` (aset yang sekadar
berhubungan, bukan hierarkis) tidak pernah diperiksa siklusnya karena
secara semantik memang tidak membentuk pohon. Self-reference
(`AsetIndukId === AsetAnakId`) ditolak terlebih dahulu untuk kedua jenis
relasi sebelum BFS dijalankan.

## 8. GaransiAset: Reminder sebagai Sinyal Visual, Bukan Notifikasi Terjadwal

Checklist 08.08 minta "reminder" tapi TIDAK ada linked concept siapa
yang harus diberi tahu (tidak seperti `Persetujuan.PerluTindakan` FASE 06
yang jelas audiensnya: penyetuju tahap itu). Membangun command terjadwal
+ event notifikasi baru untuk audiens yang belum didefinisikan adalah
spekulasi, bukan implementasi checklist yang ada. `GaransiAsetResource`
karena itu menghitung `SisaHari`/`AkanBerakhir`/`SudahBerakhir` sebagai
field turunan (ambang 30 hari, `Status=Aktif` dan `BerakhirPada` dalam
jendela itu) yang dirender sebagai badge visual di halaman detail --
"reminder" di sini berarti admin yang membuka halaman aset LANGSUNG
melihat peringatan, bukan menunggu notifikasi push. Kalau kebutuhan
notifikasi proaktif (mis. dashboard widget lintas-organisasi seperti pola
"Stock Alert" FASE 10) muncul nanti, infrastruktur `LayananNotifikasi` +
`KatalogPeristiwaNotifikasi` (FASE 06) sudah siap dipakai tanpa perubahan
skema.

## 9. NilaiAset: Pratinjau Terhitung, Baris Tersimpan Selalu Eksplisit

`LayananPenyusutanAset::hitungGarisLurus()` MURNI fungsi kalkulasi
(harga perolehan, nilai residu, umur manfaat, tanggal mulai operasi) yang
dipanggil lewat endpoint `GET /aset/{aset}/nilai/pratinjau` -- meniru pola
`LayananNomorDokumen::pratinjau()` FASE 04 persis: menghitung TANPA
menulis apa pun, hasilnya dipakai mengisi form secara otomatis di
frontend (tombol "Hitung Otomatis"), tapi baris `NilaiAset` yang benar-
benar tersimpan selalu lewat `POST` eksplisit dengan nilai yang bisa
diedit pengguna sebelum submit. Desain ini menjaga `NilaiAset` sebagai
ledger append-oriented yang auditable (setiap baris adalah keputusan
sadar seseorang untuk mencatat nilai pada tanggal itu) sekaligus
memberi kenyamanan otomatisasi tanpa memaksa satu formula jadi
satu-satunya sumber kebenaran. Perhitungan melempar `AturanBisnisDilanggar`
kalau `HargaPerolehan`/`UmurManfaatBulan`/tanggal mulai operasi belum
lengkap -- ditampilkan sebagai pesan error di form (bukan gagal senyap),
mendorong pengguna melengkapi data di tab Info dulu.

Keunikan `(AsetId, TanggalNilai)` ditegakkan dua lapis: constraint unique
di database (jaring pengaman terakhir) DAN pengecekan eksplisit di
`BuatNilaiAset` yang melempar `KonflikData` (409) dengan pesan jelas
sebelum insert. Pengecekan eksplisit memakai `whereDate()` (bukan
`where()` biasa) karena cast `'date'` Eloquent di versi Laravel ini
menyimpan nilai dengan komponen waktu (`Y-m-d 00:00:00`), bukan murni
`Y-m-d` -- ditemukan lewat test yang gagal (`assertStatus(409)` menerima
500 dari pelanggaran constraint mentah), bukan diasumsikan, persis pola
"biarkan test yang gagal mengungkap bentuk data sungguhan" dari FASE 06.

## 10. Meter: Validasi Mundur Hanya untuk yang Benar-Benar Kumulatif

`MeterAset.Jenis` (`Kumulatif`/`NonKumulatif`) menentukan apakah
`CatatPembacaanMeter` menegakkan urutan monoton naik. Untuk meter
kumulatif (mis. jam operasi, odometer -- nilai secara fisik tidak
mungkin berkurang), pembacaan baru dibandingkan terhadap pembacaan
TERAKHIR (`orderByDesc('DibacaPada')->first()`, fallback ke `NilaiAwal`
kalau belum ada pembacaan sama sekali) -- nilai lebih kecil ditolak
(`AturanBisnisDilanggar`). Untuk meter non-kumulatif (mis. suhu, tekanan
-- nilai yang wajar naik-turun), tidak ada pengecekan apa pun. Pemisahan
ini eksplisit lewat kolom `Jenis`, bukan diasumsikan dari nama meter,
supaya modul yang memakai infrastruktur ini nanti (kalibrasi, pemeliharaan
berbasis meter) tidak perlu menebak jenis meter dari string bebas.

## 11. Registry List: Server-Side, Bukan DataTable Client-Side

`AsetController::index()` sengaja TIDAK memakai pola `DataTable`
client-side yang jadi standar sejak FASE 04 (semua baris dimuat sekali,
filter/sort di browser) -- ia memakai pola `paginate(25)` + filter lewat
query string + komponen `Pagination` sederhana, PERSIS seperti
`CatatanAuditController` di FASE 05 (ADR 0005 bagian 3). Alasan yang sama
berlaku: `Aset` adalah tabel yang tumbuh tanpa batas jelas per organisasi
(bisa ribuan baris untuk organisasi besar), berbeda dari tabel pengaturan
FASE 04 yang secara alami kecil (puluhan/ratusan baris). Filter server-
side (`cari` lewat `LIKE` pada Nama/KodeAset/NomorSeri, `kategoriAsetId`,
`lokasiId`, `status`) dan sort (`urutkan`+`arah`, whitelist kolom via
`in:Nama,KodeAset,Status,DibuatPada`) berjalan di database, bukan
mengirim seluruh tabel ke klien.

## 12. Halaman Detail: Tab per Sub-Resource, Bukan Dialog

Berbeda dari `Penyedia` (FASE 07) yang memakai dialog bertab di atas
halaman daftar, `Aset/Show.tsx` adalah HALAMAN PENUH tersendiri
(`/aset/{id}`) dengan delapan tab (Info/Lokasi/Penanggung Jawab/Relasi/
Garansi/Nilai/Meter/Kolaborasi). Keputusan ini murni soal skala konten:
Penyedia punya 3 relasi anak yang muat nyaman dalam dialog, Aset punya
7 -- memaksakan itu ke dalam modal akan membuatnya harus di-scroll
berlebihan dan terasa sempit untuk data sepenting identitas aset. Setiap
tab sub-resource (Lokasi/Penanggung Jawab/Relasi/Garansi/Nilai/Meter)
memanggil endpoint JSON sendiri lewat `apiAset` HANYA saat tab itu dibuka
(pola `useEffect` per komponen tab) -- payload `AsetController::show()`
sendiri tetap ringan (satu baris Aset + eager-load relasi BelongsTo-nya
saja) berapa pun banyaknya riwayat lokasi/penanggung jawab/nilai/meter
yang dimiliki aset itu, konsisten dengan alasan yang sama di balik
desain tab Kontak/Penilaian pada `Penyedia/Index.tsx` FASE 07.

## 13. Bukti Gate 08

Smoke test Playwright menjalankan siklus penuh terhadap Chromium
sungguhan: login -> buat `KategoriAset` (dengan default penyusutan) ->
buat `Merek` -> buat `ModelAset` (terhubung kategori+merek) -> daftarkan
`Aset` baru lewat dialog -> verifikasi redirect ke halaman detail -> isi
`TanggalMulaiOperasi` lewat tab Info -> tab Lokasi (belum ditentukan) ->
tab Penanggung Jawab (belum ditetapkan) -> tab Relasi (kosong) -> tambah
Garansi dan verifikasi badge "Akan berakhir" muncul otomatis -> hitung
penyusutan otomatis dan simpan nilai -> tambah Meter kumulatif dan catat
pembacaan -> tab Kolaborasi merender `PanelKolaborasi` tanpa kode
tambahan -- nol error JavaScript. Dibuktikan juga lewat test backend:
`tests/Feature/Aset/AsetTest.php` (19 test: hierarki kategori sirkular,
validasi-penggunaan sebelum hapus, duplicate-prevention Merek, default
kategori terwarisi ke Aset baru, riwayat lokasi awal tertulis otomatis,
keunikan KodeAset per organisasi, optimistic locking menolak versi usang,
LokasiId tidak bisa diubah lewat edit umum, soft-delete, isolasi tenant,
pindah-lokasi menulis histori, ganti-penanggung-jawab menutup baris lama,
self-reference dan siklus RelasiAset ditolak, reminder garansi terhitung,
keunikan tanggal NilaiAset, pratinjau penyusutan garis lurus akurat,
validasi mundur meter kumulatif vs non-kumulatif) -- 233 test lulus
total, PHPStan level 7 bersih, `tsc --noEmit` bersih.

### Gate 08

Halaman detail Aset menampilkan identitas lengkap (kategori, model,
merek, penyedia, lokasi, penanggung jawab) dan histori inti (lokasi,
penanggung jawab, nilai, meter) secara benar dan konsisten struktural --
siap menjadi fondasi FASE 09 (Siklus Aset: mutasi, serah terima,
penghapusan) tanpa perubahan skema atau refactor pada domain `Aset` yang
sudah ada.
