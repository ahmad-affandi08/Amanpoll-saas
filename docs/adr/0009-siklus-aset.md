# ADR 0009 — Siklus Aset

Status: Diterima
Fase: TASK.md FASE 09

## Konteks

FASE 09 adalah konsumen pertama yang menghubungkan mesin persetujuan
domain-agnostik (FASE 06) dengan sebuah domain bisnis konkret sesudah
FASE 06 sendiri selesai -- tiga sub-domain (`PermintaanMutasiAset`,
`SerahTerimaAset`, `PengajuanPenghapusanAset`) yang mengubah state fisik
aset nyata (lokasi, kondisi, status arsip) berdasarkan keputusan manusia
yang melewati alur persetujuan berjenjang. Gate-nya eksplisit: "Lifecycle
aset dapat ditelusuri dari registrasi sampai mutasi/serah terima/
penghapusan" -- artinya setiap perubahan fisik harus punya jejak
persetujuan DAN jejak histori yang bisa diaudit balik, bukan sekadar
tombol yang langsung mengubah baris `Aset`.

## 1. Sinkronisasi Status Lewat Observer, Bukan Memperluas Mesin Generik

Mesin `Persetujuan` (FASE 06) sengaja domain-agnostic: ia hanya tahu
`JenisEntitas`+`EntitasId`, tidak tahu apa arti "disetujui" bagi domain
pemanggilnya. Ketika `PermintaanPersetujuan.Status` berubah jadi
`Disetujui`/`Ditolak`, domain `SiklusAset` perlu ikut mengubah
`PermintaanMutasiAset.Status`/`PengajuanPenghapusanAset.Status` miliknya
sendiri -- tapi mesin generik tidak punya hook callback untuk itu, dan
menambahkannya (event, callback interface, dsb.) berarti menyentuh kode
bersama yang sudah dipakai domain lain hanya demi SATU konsumen baru.
Solusi yang dipilih: `SinkronkanStatusPersetujuanSiklusAset`, sebuah
Eloquent Observer yang didaftarkan lewat
`PermintaanPersetujuan::observe(...)` di `AmanpollServiceProvider::boot()`,
bereaksi pada `updated()` + `wasChanged('Status')`, lalu `match()` pada
`JenisEntitas` untuk menemukan baris `PermintaanMutasiAset`/
`PengajuanPenghapusanAset` yang bersangkutan lewat `EntitasId` dan
menuliskan status barunya. Mesin `Persetujuan` sendiri TIDAK disentuh
sama sekali -- pola ini didokumentasikan sebagai preseden: modul
berikutnya yang jadi konsumen approval engine (mis. Perintah Kerja,
Pengadaan) cukup menambah `match` case baru di observer yang sama atau
observer sejenis, bukan memodifikasi domain `Persetujuan`.

## 2. Approval Mengesahkan, Eksekusi Bertindak — Dua Langkah yang Sengaja Terpisah

TASK.md sendiri memisahkan `09.01` (`PermintaanMutasiAset`: draft ->
submit -> approve/reject -> cancel) dari `09.02` (`Eksekusi Mutasi`:
validasi, update lokasi, tulis histori) sebagai dua unit checklist
berbeda -- ini bukan kebetulan penomoran, tapi mencerminkan kenyataan
lapangan: persetujuan digital atas permintaan pindah barang TIDAK sama
momennya dengan barang itu benar-benar berpindah secara fisik. Karena
itu `EksekusiMutasiAset`/`EksekusiPenghapusanAset` adalah action
terpisah, dipicu manual lewat tombol "Eksekusi" yang HANYA muncul
setelah status `Disetujui`, bukan otomatis dijalankan oleh observer di
atas. Kedua action ini idempotent secara internal: mengeksekusi
permintaan yang statusnya sudah `Selesai` langsung `return` tanpa efek
samping (bukan melempar error), dan hanya baris detail berstatus
`Menunggu` yang diproses per aset -- pemanggilan ulang yang tidak
sengaja (double-click, retry jaringan) tidak menggandakan efek.

## 3. Reuse Izin Granular yang Sudah Diseed, Bukan Kode Baru

`RegistriEntitas` mendaftarkan ketiga entitas baru dengan kode Izin yang
SUDAH ADA sejak `IzinSeeder` FASE 00/01 (`PermintaanMutasiAset` ->
`Aset.Ubah`, `SerahTerimaAset` -> `Aset.Ubah`, `PengajuanPenghapusanAset`
-> `Aset.Hapus`) -- tidak ada kode Izin baru yang diciptakan fase ini.
Alasan: memindahkan/menyerahterimakan aset secara semantik adalah bentuk
"mengubah" aset (lokasi, penanggung jawab, kondisi berubah), dan
menghapuskan aset secara semantik adalah "menghapus" -- granularitas
`Aset.Lihat/Buat/Ubah/Hapus` yang sudah dirancang sejak awal proyek
(ADR 0008 bagian 1) ternyata sudah cukup ekspresif untuk lifecycle
penuh tanpa perlu kode `SiklusAset.Kelola` terpisah. Policy tiga entitas
ini (`PermintaanMutasiAsetPolicy`, `SerahTerimaAsetPolicy`,
`PengajuanPenghapusanAsetPolicy`) memetakan langsung ke kode itu, dengan
satu pengecualian: `update` pada Mutasi/Penghapusan juga mengizinkan
pemohon asli (`Pengguna->Id === DimintaOleh/DiajukanOleh`) mengelola
draft miliknya sendiri sebelum submit, meski dia tidak punya
`Aset.Ubah`/`Aset.Hapus` -- `SerahTerimaAset` tidak diberi pengecualian
serupa karena checklist 09.03 tidak menyebut kepemilikan personal atas
dokumen serah terima, hanya "pihak asal/tujuan" sebagai data, bukan
sebagai hak akses.

## 4. SerahTerimaAset: Dua State, Bukan State Machine Penuh

Kolom `Status` pada `SerahTerimaAset` memang default `'Draft'` di skema,
tapi checklist 09.03 hanya menyebut dua verba: "buat dokumen" dan
"terima" -- tidak ada "submit" atau "approve" yang eksplisit untuk
dokumen serah terima (berbeda dari Mutasi/Penghapusan yang punya alur
persetujuan formal). Keputusan sengaja: `SerahTerimaAset` memakai model
DUA status (`Diserahkan` -> `Diterima`), bukan mewarisi pola Draft/
Menunggu/Disetujui empat entitas lain. `BuatSerahTerimaAset` langsung
menulis `Status=Diserahkan` saat dibuat (bukan `Draft` yang menunggu
submit terpisah) karena tindakan "membuat dokumen serah terima" itu
sendiri SECARA SEMANTIK berarti pihak asal sudah menyerahkan barang --
tidak ada draft yang bisa dibatalkan sebelum penyerahan terjadi.
`TerimaSerahTerimaAset` menegakkan bahwa SEMUA baris detail sudah
dikonfirmasi kondisinya (`KondisiSaatDiterima`) sebelum dokumen bisa
ditandai `Diterima` -- membandingkan `AsetId` pada detail terhadap
`AsetId` pada payload kondisi yang dikirim, menolak (`AturanBisnisDilanggar`)
kalau ada aset yang terlewat. Keputusan lingkup ini akan didokumentasikan
ulang kalau fase mendatang (mis. Peminjaman Aset formal) butuh alur
persetujuan serah terima yang lebih ketat -- untuk sekarang, dua state
ini konsisten dengan apa yang diminta checklist, tidak lebih.

## 5. Nomor Dokumen: LayananNomorDokumen Dipakai Ulang untuk Ketiga Sub-Domain

`BuatPermintaanMutasiAset`, `BuatSerahTerimaAset`, dan
`BuatPengajuanPenghapusanAset` sama-sama memanggil
`LayananNomorDokumen::berikutnya()` (FASE 04) untuk mengisi kolom
`Nomor` masing-masing -- pembuktian lanjutan bahwa generator nomor
sequential-per-organisasi-per-JenisDokumen itu genuinely reusable lintas
domain tanpa modifikasi. Konsekuensi penting: setiap organisasi WAJIB
punya baris `NomorDokumen` untuk ketiga `JenisDokumen`
(`PermintaanMutasiAset`/`SerahTerimaAset`/`PengajuanPenghapusanAset`)
sebelum bisa membuat dokumen jenis itu -- kalau belum ada,
`LayananNomorDokumen::berikutnya()` melempar `DataTidakDitemukan` (404)
alih-alih diam-diam memakai angka default. Ini konsisten dengan
keputusan yang sama di FASE 08 (`KodeAset` manual) TAPI berbeda alasan:
di sini `Nomor` MEMANG dokumen resmi yang perlu dibaca manusia secara
berurutan (mis. untuk arsip fisik "PER-2026-0001"), bukan token
pemindaian internal seperti `KodeQr` -- jadi `LayananNomorDokumen` adalah
alat yang tepat di sini, berbeda dari alasan kenapa ia TIDAK dipakai
untuk `KodeQr` (ADR 0008 bagian 6).

## 6. Larangan Hard-Delete: Soft-Delete + Arsip, Ditegakkan di Satu Titik

Checklist 09.04 eksplisit: "Larang hard-delete aset historis."
`EksekusiPenghapusanAset` menegakkan ini secara struktural, bukan lewat
konvensi yang bisa dilupakan: setiap aset yang dihapuskan diberi
`Status=Aset::STATUS_DIARSIPKAN` TERLEBIH DAHULU, baru kemudian
`$aset->delete()` dipanggil -- dan karena `Aset` memakai trait
`SoftDeletes` (diwarisi sejak FASE 08), `delete()` di sini SELALU berarti
soft-delete (mengisi `DihapusPada`), tidak pernah `forceDelete()`.
Tidak ada satu baris kode pun di seluruh `App\Domain\SiklusAset` yang
memanggil `forceDelete()` pada model `Aset` -- constraint ini dipilih
supaya histori (`RiwayatLokasiAset`, `NilaiAset`, `GaransiAset`, dll.
dari FASE 08) tetap bisa dirujuk balik ke baris Aset yang sudah
diarsipkan. Konsekuensi langsung: query yang menampilkan histori
lifecycle (mis. `PengajuanPenghapusanAsetController::show()`) harus
secara eksplisit memakai `withTrashed()` saat memuat relasi `aset` pada
`detailPenghapusanAset`, supaya `Nama`/`KodeAset` tetap tampil untuk
aset yang sudah diarsipkan alih-alih hilang jadi baris kosong akibat
default scope `SoftDeletes` yang menyaring baris terhapus.

## 7. Isolasi Tenant di Test: Konteks Harus Ditetapkan Ulang Setelah Setiap Request HTTP

Selama menulis `tests/Feature/SiklusAset/SiklusAsetTest.php`, ditemukan
(bukan diasumsikan, lewat test debug sementara dengan trace STDERR)
bahwa `TetapkanKonteksOrganisasi::handle()` memanggil
`$this->konteks->bersihkan()` di blok `finally` SETELAH setiap request
selesai -- artinya query Eloquent apa pun yang dijalankan test SETELAH
memanggil `$this->actingAs(...)->post(...)` akan berjalan tanpa konteks
tenant, dan `ScopeOrganisasi` akan menyaring semua baris (termasuk baris
yang baru saja dibuat oleh request itu sendiri) sampai kosong. Pola yang
sudah ditetapkan `tests/Feature/Aset/AsetTest.php` (FASE 08) --
memanggil `$konteks->tetapkan($organisasi->Id)` ulang sebelum SETIAP
assersi Eloquent pasca-HTTP -- diterapkan konsisten di seluruh test
FASE 09 ini. Dicatat di sini supaya fase berikutnya yang menulis test
Feature baru tidak perlu menemukan ulang perilaku middleware ini lewat
debugging yang sama.

## 8. Bukti Gate 09

Smoke test Playwright menjalankan siklus penuh dua pengguna (admin +
supervisor, dua browser context terpisah) terhadap Chromium sungguhan:
admin membuat draft `PermintaanMutasiAset` (AntarLokasi ke Workshop B),
menambah satu aset, submit -> permintaan muncul di inbox generik
`/persetujuan/permintaan` milik supervisor -> supervisor menyetujui lewat
dialog konfirmasi umum (bukan UI approval khusus domain) -> observer
menyinkronkan status jadi `Disetujui` -> admin mengeksekusi mutasi,
lokasi aset berpindah dan `RiwayatLokasiAset` baru tertulis, status jadi
`Selesai`. Alur yang sama diulang untuk `PengajuanPenghapusanAset`
(alasan "Rusak berat, tidak ekonomis diperbaiki", metode Dimusnahkan) --
setelah eksekusi, halaman detail tetap menampilkan "Air Compressor 02"
(aset yang sudah diarsipkan/soft-deleted) berkat `withTrashed()`. Alur
`SerahTerimaAset` diverifikasi terpisah (tanpa approval, sesuai desain
dua-state di atas): buat dokumen -> tambah aset -> konfirmasi kondisi
per aset -> status `Diterima`. Navigasi mobile (`Sheet` sidebar) juga
diverifikasi menampilkan tiga menu baru (Mutasi Aset/Serah Terima/
Penghapusan) dengan ikon dan proteksi Izin yang benar. Dibuktikan juga
lewat test backend: `tests/Feature/SiklusAset/SiklusAsetTest.php` (7
test: draft->submit->approve->eksekusi mutasi penuh, validasi lokasi/
unit tujuan wajib salah satu, cancel membatalkan permintaan persetujuan
terkait, serah-terima buat->terima dengan validasi kondisi lengkap,
penghapusan draft->submit->approve->eksekusi dengan soft-delete
terverifikasi, isolasi tenant, penolakan hard-delete) -- 240 test lulus
total, PHPStan level 7 bersih (0 error), `tsc --noEmit` bersih, `npm run
build` sukses.

### Gate 09

Lifecycle aset dapat ditelusuri dari registrasi (FASE 08) sampai
mutasi/serah terima/penghapusan (FASE 09) -- setiap perubahan lokasi,
kondisi, atau status arsip aset punya jejak persetujuan (kecuali serah
terima yang secara sengaja di luar lingkup approval) dan jejak histori
yang bisa diaudit balik, tanpa satu pun jalur yang menghapus data aset
secara permanen.
