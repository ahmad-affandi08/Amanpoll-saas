# TASK — Amanpoll

| Atribut | Nilai |
|---|---|
| Produk | Amanpoll |
| Dokumen | Implementation Task Plan |
| Versi | 1.0.0 |
| Status | Urutan Pengerjaan Wajib |
| Tanggal | 18 September 2026 |
| Prinsip | Kerjakan berdasarkan dependensi, bukan berdasarkan modul yang terlihat menarik |

---

## 1. Cara Menggunakan Dokumen Ini

TASK ini adalah urutan implementasi. Pengerjaan harus mengikuti dependensi.

Jangan langsung mengerjakan `Pemeliharaan`, `Kalibrasi`, `Dashboard`, atau halaman besar lain sebelum fondasinya siap. Contoh, `PerintahKerja` bergantung pada tenant, pengguna, izin, lokasi, aset, penomoran, audit, file, notifikasi, dan sebagian persediaan. Bila fondasi tersebut belum stabil, implementasi PerintahKerja hanya menghasilkan refactor berulang.

Status task:

```text
[ ] Belum
[-] Sedang
[x] Selesai
[!] Terblokir
```

Setiap fase harus melewati **Gate** sebelum fase berikutnya.

---

# FASE 00 — Validasi Fondasi Project

Tujuan: memastikan generator, schema, dependency, dan environment valid sebelum menulis business logic.

## 00.01 Repository dan Environment

- [x] Inisialisasi repository Git Amanpoll.
- [x] Tetapkan branch `main` sebagai protected branch bila platform mendukung.
- [x] Buat `.env.example` tanpa secret.
- [x] Pastikan `.env`, storage private, credential, dan hasil build lokal tidak ter-commit secara tidak sengaja.
- [x] Pastikan PHP 8.4+ lokal.
- [x] Pastikan Node.js 20+.
- [x] Pastikan Composer 2+.
- [x] Pastikan MySQL 8+.
- [x] Jalankan `composer validate`.
- [x] Jalankan `npm install`.
- [x] Jalankan build Vite.
- [x] Jalankan test Laravel default.
- [x] Jalankan PHPStan/Larastan baseline tanpa menutupi error baru.

## 00.02 Validasi Schema

- [x] Import schema Amanpoll ke database development kosong.
- [x] Pastikan seluruh foreign key berhasil dibuat.
- [x] Pastikan charset `utf8mb4`.
- [x] Pastikan timezone aplikasi UTC untuk penyimpanan waktu.
- [x] Cocokkan tabel dengan domain.
- [x] Audit index untuk foreign key dan query utama.
- [x] Pastikan tabel tenant memiliki `OrganisasiId` sesuai kebutuhan.
- [x] Pastikan tabel histori/transaksi tidak menggunakan hard delete tanpa alasan.
- [x] Dokumentasikan perubahan schema sebelum mulai coding fitur.

## 00.03 Validasi Generator

- [x] Jalankan `index.js` pada project kosong.
- [x] Pastikan tidak ada syntax error PHP.
- [x] Pastikan tidak ada syntax error TypeScript.
- [x] Pastikan file generated tidak memiliki namespace salah.
- [x] Pastikan Eloquent model mengarah ke table PascalCase yang benar.
- [x] Pastikan `HasUlids` hanya digunakan pada entitas yang sesuai.
- [x] Pastikan timestamp mapping menggunakan `DibuatPada`, `DiperbaruiPada`, `DihapusPada`.
- [x] Hapus scaffold dummy yang tidak akan digunakan.
- [x] Jangan menerima generated repository/action kosong sebagai implementasi selesai.

### Gate 00

Lanjut hanya jika project dapat:

```text
composer install
npm install
php artisan config:clear
php artisan route:list
php artisan test
npm run build
```

tanpa error fatal.

---

# FASE 01 — Konvensi dan Shared Foundation

Tujuan: menetapkan aturan yang akan dipakai semua domain.

## 01.01 Konvensi Bahasa dan Kode

- [x] Business class menggunakan Bahasa Indonesia.
- [x] Business function menggunakan Bahasa Indonesia.
- [x] Variable bisnis menggunakan Bahasa Indonesia.
- [x] Status menggunakan Enum, bukan magic string tersebar.
- [x] Komentar maksimal satu baris dan menjelaskan alasan.
- [x] Controller tidak memuat business logic kompleks.
- [x] Action hanya memiliki satu use case utama.
- [x] Query kompleks dipisah dari command.
- [x] Repository interface hanya dibuat bila memberi boundary yang nyata.
- [x] Hindari class `Helper` generik.

Contoh:

```php
final class BuatOrganisasi
{
    public function jalankan(DataOrganisasi $data): Organisasi
    {
        // Simpan organisasi dan konfigurasi awal secara atomik.
    }
}
```

Method `jalankan()` diperbolehkan sebagai convention internal. Method kontrak framework tetap mengikuti framework.

## 01.02 Shared Exceptions

- [x] `AturanBisnisDilanggar`
- [x] `AksesDitolak`
- [x] `DataTidakDitemukan`
- [x] `KonflikData`
- [x] `VersiDataBerubah`
- [x] Mapping exception ke response web/API konsisten.
- [x] Production response tidak menampilkan stack trace.

## 01.03 Transaction Helper

- [x] Tetapkan policy kapan `DB::transaction()` wajib.
- [x] Gunakan transaction pada multi-write.
- [x] External HTTP call tidak dilakukan di tengah transaction jika dapat dihindari.
- [x] Event eksternal gunakan outbox.

## 01.04 Time dan Timezone

- [x] Simpan waktu UTC.
- [x] Tampilkan berdasarkan `ZonaWaktu` organisasi/lokasi.
- [x] Buat service konversi waktu terpusat.
- [x] Jangan memanggil timezone hardcoded di feature.
- [x] Test edge case pergantian tanggal lokal.

## 01.05 Uang dan Angka

- [x] Gunakan `DECIMAL` sesuai schema.
- [x] Jangan gunakan float untuk nilai uang.
- [x] Buat formatter mata uang di frontend.
- [x] Kalkulasi total selalu diverifikasi server.
- [x] Tentukan aturan pembulatan.

Isian nominal memakai `InputUang` (`components/shared`), bukan
`<Input type="number">`: pemisah ribuan muncul selagi mengetik, lambang mata
uang tampil di depan (`Rp`, `US$`), dan yang dikirim ke server tetap angka
kanonik tanpa pemisah (`1500000.5`), jadi validasi `numeric` tidak berubah.
Nilai dari server dirapikan ke kebiasaan mata uangnya (rupiah tanpa `,00`).
Kursor dijaga di antara digit yang sama walau titik pemisah bertambah di
depannya.

Dua jebakan ditemukan saat komponennya diuji mengetik di Chromium. Menolak koma
pada rupiah membuat komanya hilang dan digit sesudahnya menempel ke angka bulat:
`15.200,50` menjadi `1.520.050`, seratus kali lipat tanpa peringatan. Karena itu
isian menerima dua desimal untuk semua mata uang. Lalu, tempelan dari sistem
lain (`1500000.50`) dibaca dengan titik desimal, tetapi hanya untuk tempelan:
menghapus digit dari `15.200` meninggalkan `15.20`, yang rupanya sama.
`NominalUangMemakaiInputUangTest` menolak kolom bernama uang yang kembali ke
isian angka mentah; `Jumlah` sengaja tidak ikut dikenali karena dipakai untuk
kuantitas maupun nominal.

### Gate 01

Shared convention terdokumentasi dan minimal satu test membuktikan exception mapping, timezone, serta transaction behavior.

---

# FASE 02 — Multi-Organisasi

Tujuan: membangun boundary keamanan sebelum data bisnis.

## 02.01 Konteks Organisasi

- [x] Buat `KonteksOrganisasi`.
- [x] Resolve organisasi dari session.
- [x] Resolve organisasi dari API key.
- [x] Resolve organisasi untuk queue job.
- [x] Fail closed bila konteks tenant tidak tersedia pada operasi tenant.
- [x] Jangan mengambil `OrganisasiId` mentah dari request untuk menentukan tenant.

## 02.02 Scope Data

- [x] Global scope atau tenant repository yang konsisten.
- [x] Create otomatis mengisi `OrganisasiId`.
- [x] Update memverifikasi organisasi.
- [x] Delete memverifikasi organisasi.
- [x] Route model binding tenant-aware.
- [x] Relation lintas tenant ditolak.

## 02.03 Test Isolasi

- [x] Organisasi A tidak dapat membaca data B.
- [x] Organisasi A tidak dapat update data B.
- [x] Organisasi A tidak dapat delete data B.
- [x] Organisasi A tidak dapat attach relation ke data B.
- [x] API key A tidak dapat mengakses B.
- [x] Background job tidak kehilangan scope tenant.

### Gate 02

Tidak ada domain bisnis berikutnya sebelum tenant isolation test hijau.

---

# FASE 03 — Authentication, Session, RBAC, dan Security

## 03.01 Login

- [x] Login menggunakan kode organisasi + email + password.
- [x] Validasi status organisasi.
- [x] Validasi status pengguna.
- [x] Regenerate session setelah login.
- [x] Logout menghapus session.
- [x] Rate limit login.
- [x] Generic error untuk credential salah.

## 03.02 Pengguna

- [x] Daftar pengguna.
- [x] Buat pengguna.
- [x] Ubah pengguna.
- [x] Aktif/nonaktif.
- [x] Reset password flow.
- [x] Profil pengguna.
- [x] Perangkat pengguna.

## 03.03 RBAC

- [x] CRUD Peran.
- [x] CRUD Izin hanya sesuai policy platform.
- [x] Assign PenggunaPeran.
- [x] Assign PeranIzin.
- [x] Middleware izin.
- [x] Policy per entity.
- [x] Frontend directive/helper untuk visibility action.
- [x] Backend tetap menjadi sumber authorization.

## 03.04 API Key

- [x] Generate secret sekali.
- [x] Simpan hash.
- [x] Prefix untuk lookup.
- [x] Scope/permission.
- [x] Expiry.
- [x] Revoke.
- [x] Optional IP allowlist.
- [x] Audit create/revoke.

### Gate 03

Auth web dan API tenant-aware, RBAC aktif, test unauthorized dan cross-tenant lulus.

---

# FASE 04 — Struktur Organisasi dan Konfigurasi

## 04.01 Organisasi

- [x] Detail organisasi.
- [x] Edit profil.
- [x] Logo.
- [x] Zona waktu.
- [x] Status sesuai policy platform.

## 04.02 UnitOrganisasi

- [x] CRUD.
- [x] Hierarki parent-child.
- [x] Cegah circular hierarchy.
- [x] Filter unit aktif.

## 04.03 KategoriLokasi dan Lokasi

- [x] CRUD kategori.
- [x] CRUD lokasi.
- [x] Hierarki lokasi.
- [x] Cegah circular hierarchy.
- [x] Hubungkan unit.
- [x] Search lokasi.
- [x] Status aktif/nonaktif.

## 04.04 KonfigurasiOrganisasi

- [x] Key-value config yang tervalidasi.
- [x] Namespace config per fitur.
- [x] Default config.
- [x] Cache config dengan invalidation jelas.

## 04.05 NomorDokumen

- [x] Format prefix.
- [x] Sequence.
- [x] Reset period bila diperlukan.
- [x] Lock/concurrency safety.
- [x] Preview nomor.
- [x] Test request paralel.

## 04.06 HariLibur

- [x] CRUD.
- [x] Digunakan oleh service kalender kerja.
- [x] Scope organisasi.

### Gate 04

Admin organisasi dapat menyiapkan struktur dasar sampai lokasi tanpa SQL/manual setup.

---

# FASE 05 — Audit, Berkas, Tag, Kolom Kustom, Komentar

Ini dikerjakan sebelum aset karena akan digunakan hampir semua domain.

## 05.01 CatatanAudit

- [x] Service audit terpusat.
- [x] Actor.
- [x] Organisasi.
- [x] Entitas.
- [x] Aksi.
- [x] Before/after.
- [x] Request/correlation metadata yang aman.
- [x] Filter audit.
- [x] Policy read.

## 05.02 CatatanAkses

- [x] Catat security-sensitive access bila diperlukan.
- [x] Hindari logging berlebihan pada setiap GET biasa.
- [x] Retention policy.

## 05.03 Berkas

- [x] Upload.
- [x] MIME validation.
- [x] Size limit.
- [x] Storage abstraction.
- [x] Download authorized.
- [x] Delete sesuai policy.
- [x] Filename aman.
- [x] Local storage production awal.
- [x] Siapkan driver S3-compatible.

## 05.04 LampiranEntitas

- [x] Attach.
- [x] Detach.
- [x] Authorization berdasarkan entitas induk.
- [x] Urutan/jenis lampiran bila tersedia.

## 05.05 Tag

- [x] CRUD tag.
- [x] Assign/unassign ke entitas.
- [x] Search/filter tag.

## 05.06 KolomKustom

- [x] CRUD definisi.
- [x] Tipe input.
- [x] Required.
- [x] Opsi.
- [x] Validation.
- [x] Nilai per entitas.
- [x] Rendering form dinamis.

## 05.07 Komentar

- [x] Tambah komentar.
- [x] Edit sesuai aturan.
- [x] Hapus/soft delete sesuai aturan.
- [x] Audit.

### Gate 05

Aset nanti dapat langsung memakai file, tag, custom field, komentar, dan audit tanpa refactor.

---

# FASE 06 — Approval Engine dan Notifikasi Dasar

Approval dibangun sebelum transaksi yang membutuhkannya.

## 06.01 AlurPersetujuan

- [x] CRUD alur.
- [x] Jenis entitas.
- [x] Kondisi aktivasi.
- [x] Status aktif.
- [x] Validasi tidak ada tahap kosong.

## 06.02 TahapPersetujuan

- [x] Urutan.
- [x] Approver user/role/unit.
- [x] Jumlah persetujuan bila dibutuhkan.
- [x] Larangan self-approval configurable.
- [x] Rejection behavior.

## 06.03 PermintaanPersetujuan

- [x] Buat permintaan.
- [x] Snapshot konteks penting.
- [x] Status.
- [x] Current stage.
- [x] Cancel.
- [x] Reject.
- [x] Complete.

## 06.04 KeputusanPersetujuan

- [x] Approve.
- [x] Reject.
- [x] Catatan.
- [x] Timestamp.
- [x] Append-oriented.
- [x] Audit.

## 06.05 Notifikasi Dasar

- [x] Templat notifikasi.
- [x] In-app notification.
- [x] Preferensi.
- [x] Email adapter optional.
- [x] Queue database.
- [x] Failure handling.

### Gate 06

Buat satu use case test dummy persetujuan end-to-end sebelum approval dipakai domain lain.

---

# FASE 07 — Penyedia

## 07.01 KategoriPenyedia

- [x] CRUD.
- [x] Status.
- [x] Validasi penggunaan.

## 07.02 Penyedia

- [x] CRUD.
- [x] Identitas.
- [x] Alamat.
- [x] Kontak.
- [x] Status.
- [x] Lampiran.
- [x] Tag.
- [x] Kolom kustom.

## 07.03 PenyediaKategori

- [x] Assign multi kategori.
- [x] Remove.
- [x] Filter.

## 07.04 KontakPenyedia

- [x] CRUD.
- [x] Kontak utama.
- [x] Validasi.

## 07.05 PenilaianPenyedia

- [x] Form penilaian.
- [x] Histori.
- [x] Rekap.

### Gate 07

Penyedia dapat digunakan oleh aset, procurement, kontrak, dan kalibrasi.

---

# FASE 08 — Master Aset

## 08.01 KategoriAset

- [x] CRUD.
- [x] Hierarki.
- [x] Cegah circular.
- [x] Default property bila relevan.

## 08.02 Merek

- [x] CRUD.
- [x] Search.
- [x] Duplicate prevention yang wajar.

## 08.03 ModelAset

- [x] CRUD.
- [x] Hubungkan merek.
- [x] Kategori.
- [x] Metadata teknis.

## 08.04 Asset Registry

- [x] Daftar aset.
- [x] Search server-side.
- [x] Filter.
- [x] Sort.
- [x] Pagination.
- [x] Buat aset.
- [x] Ubah aset.
- [x] Detail aset.
- [x] Soft delete/archive.
- [x] Generate identifier/QR.
- [x] Scan resolver.
- [x] Lampiran.
- [x] Tag.
- [x] Field kustom.

## 08.05 RiwayatLokasiAset

- [x] Set lokasi awal.
- [x] Perubahan lokasi membuat histori.
- [x] Cegah edit histori sembarang.
- [x] Current location konsisten dengan history.

## 08.06 Penanggung Jawab

- [x] Assign.
- [x] Ganti.
- [x] Histori.
- [x] Validasi user/unit tenant.

## 08.07 RelasiAset

- [x] Parent-child.
- [x] Related asset.
- [x] Cegah self-reference.
- [x] Cegah cycle jika relation bersifat hierarchy.

## 08.08 GaransiAset

- [x] CRUD.
- [x] Penyedia.
- [x] Periode.
- [x] Dokumen.
- [x] Reminder.

## 08.09 NilaiAset

- [x] Harga perolehan.
- [x] Nilai buku bila digunakan.
- [x] Histori nilai.
- [x] Formatter uang.

## 08.10 Meter

- [x] Definisi meter per aset.
- [x] Unit.
- [x] Pembacaan.
- [x] Validasi pembacaan mundur bila meter kumulatif.
- [x] Histori.

### Gate 08

Detail aset menampilkan identitas dan histori inti secara benar sebelum lifecycle/maintenance ditambahkan.

---

# FASE 09 — Siklus Aset

## 09.01 PermintaanMutasiAset

- [x] Buat draft.
- [x] Tambah detail aset.
- [x] Submit.
- [x] Hubungkan approval.
- [x] Approve/reject.
- [x] Cancel.
- [x] Audit.

## 09.02 Eksekusi Mutasi

- [x] Validasi aset.
- [x] Validasi lokasi tujuan.
- [x] Update current location hanya setelah syarat terpenuhi.
- [x] Tulis RiwayatLokasiAset.
- [x] Transaction.
- [x] Idempotency internal.

## 09.03 SerahTerimaAset

- [x] Buat dokumen.
- [x] Detail aset.
- [x] Pihak asal.
- [x] Pihak tujuan.
- [x] Kondisi.
- [x] Terima.
- [x] Lampiran.
- [x] Audit.

## 09.04 PenghapusanAset

- [x] Draft.
- [x] Detail.
- [x] Alasan.
- [x] Approval.
- [x] Eksekusi.
- [x] Asset status.
- [x] Histori.
- [x] Audit.
- [x] Larang hard-delete aset historis.

### Gate 09

Lifecycle aset dapat ditelusuri dari registrasi sampai mutasi/serah terima/penghapusan.

---

# FASE 10 — Persediaan dan Suku Cadang

Dikerjakan sebelum PerintahKerja penuh agar pemakaian suku cadang tidak ditambal belakangan.

## 10.01 Gudang

- [x] CRUD gudang.
- [x] Lokasi gudang.
- [x] Status.
- [x] Scope unit/lokasi.

## 10.02 Master SukuCadang

- [x] Kategori.
- [x] Suku cadang.
- [x] Unit.
- [x] SKU/kode.
- [x] Min stock.
- [x] Harga.
- [x] Status.

## 10.03 Kompatibilitas

- [x] Suku cadang ↔ model/aset.
- [x] Filter compatible part.

## 10.04 StokSukuCadang

- [x] Saldo per lokasi.
- [x] Stok fisik.
- [x] Stok reserved.
- [x] Stok tersedia.
- [x] Lock saat mutasi.

## 10.05 MutasiStok

- [x] Penerimaan.
- [x] Pengeluaran.
- [x] Transfer.
- [x] Adjustment.
- [x] Return.
- [x] Detail.
- [x] Nomor dokumen.
- [x] Transaction.
- [x] Audit.

## 10.06 Reservasi

- [x] Reserve.
- [x] Release.
- [x] Consume.
- [x] Expiry bila digunakan.
- [x] Cegah over-reservation.

## 10.07 Stock Alert

- [x] Minimum stock.
- [x] Notifikasi.
- [x] Dashboard widget.

### Gate 10

Tidak ada endpoint yang mengubah `StokSukuCadang` langsung tanpa transaksi mutasi yang sah.

---

# FASE 11 — SLA dan Keluhan

## 11.01 TingkatLayanan

- [x] CRUD.
- [x] Aturan response.
- [x] Aturan resolution.
- [x] Kalender kerja.
- [x] Hari libur.
- [x] Prioritas/kategori.

## 11.02 Service Kalkulasi SLA

- [x] Hitung deadline response.
- [x] Hitung deadline resolution.
- [x] Respect jam kerja.
- [x] Respect hari libur.
- [x] Unit test skenario lintas hari.

## 11.03 KategoriKeluhan

- [x] CRUD.
- [x] Default priority.
- [x] Routing rule bila dibutuhkan.

## 11.04 Keluhan

- [x] Buat keluhan.
- [x] Detail.
- [x] Asset optional sesuai jenis complaint.
- [x] Lokasi.
- [x] Pelapor.
- [x] Lampiran.
- [x] Triage.
- [x] Ubah prioritas sesuai izin.
- [x] Histori status.
- [x] Tutup.
- [x] Batalkan/tolak sesuai policy.

## 11.05 EskalasiTingkatLayanan

- [x] Due soon.
- [x] Breach.
- [x] Prevent duplicate escalation.
- [x] Queue.
- [x] Notification.

### Gate 11

Keluhan memiliki deadline SLA yang dapat diuji dan riwayat status lengkap.

---

# FASE 12 — Perintah Kerja Korektif

## 12.01 PerintahKerja Core

- [x] Buat dari keluhan.
- [x] Buat manual sesuai izin.
- [x] Nomor dokumen.
- [x] Jenis.
- [x] Prioritas.
- [x] Asset.
- [x] Lokasi.
- [x] Histori status.

## 12.02 State Machine

- [x] Definisikan Enum status.
- [x] Definisikan transition yang diperbolehkan.
- [x] Tolak transition ilegal.
- [x] Semua transition masuk histori.
- [x] Audit transition kritis.

## 12.03 Penugasan

- [x] Assign teknisi.
- [x] Reassign.
- [x] Multiple assignee bila schema mendukung.
- [x] Accepted/rejected assignment.
- [x] Notification.
- [x] Workload indicator.

## 12.04 WaktuKerja

- [x] Mulai.
- [x] Pause.
- [x] Resume.
- [x] Selesai.
- [x] Cegah session ganda yang tidak sah.
- [x] Hitung durasi server-side.

## 12.05 WaktuHentiAset

- [x] Mulai downtime.
- [x] Selesai downtime.
- [x] Alasan.
- [x] Hindari overlap yang tidak valid.
- [x] Rekap.

## 12.06 PemakaianSukuCadang

- [x] Reserve dari pekerjaan.
- [x] Consume.
- [x] Return unused.
- [x] Mutasi stok otomatis.
- [x] Biaya sparepart.
- [x] Transaction lintas work order + stock.

## 12.07 BiayaPerintahKerja

- [x] Tenaga kerja bila digunakan.
- [x] Sparepart.
- [x] Vendor.
- [x] Lain-lain.
- [x] Total dihitung server-side.

## 12.08 Kegagalan

- [x] Kode kegagalan.
- [x] Analisis.
- [x] Root cause.
- [x] Tindakan perbaikan.

## 12.09 Penyelesaian

- [x] Catatan hasil.
- [x] Foto.
- [x] Verifikasi.
- [x] Close.
- [x] Reopen sesuai izin dan audit.

### Gate 12

Flow `Keluhan → PerintahKerja → Teknisi → Sparepart → Selesai → Ditutup` harus lulus integration test end-to-end.

---

# FASE 13 — Checklist, Preventive, dan Inspeksi

## 13.01 TemplatDaftarPeriksa

- [x] CRUD.
- [x] Butir.
- [x] Urutan.
- [x] Jenis jawaban.
- [x] Required.
- [x] Min/max.
- [x] Version strategy.

## 13.02 PelaksanaanDaftarPeriksa

- [x] Snapshot template.
- [x] Jawaban.
- [x] Foto/catatan.
- [x] Validasi required.
- [x] Finalisasi.
- [x] Lock final result sesuai aturan.

## 13.03 RencanaPemeliharaan

- [x] Calendar-based.
- [x] Meter-based.
- [x] Asset assignment.
- [x] Interval.
- [x] Next due.
- [x] Aktif/nonaktif.

## 13.04 Generator Jadwal

- [x] Scheduler command.
- [x] Idempotent.
- [x] Tidak membuat duplicate.
- [x] Membuat JadwalPemeliharaan.
- [x] Membuat PerintahKerja jika waktunya.
- [x] Test cron rerun.

## 13.05 Inspeksi

- [x] Templat.
- [x] Pelaksanaan.
- [x] Findings.
- [x] Pass/fail.
- [x] Generate Keluhan/PerintahKerja dari temuan.
- [x] Lampiran.

### Gate 13

Menjalankan scheduler dua kali tidak boleh menggandakan preventive work order yang sama.

---

# FASE 14 — Kalibrasi

## 14.01 JenisKalibrasi

- [x] CRUD.
- [x] Unit/metode metadata.

## 14.02 RencanaKalibrasi

- [x] Asset.
- [x] Interval.
- [x] Penyedia optional.
- [x] Reminder window.
- [x] Next due.

## 14.03 PelaksanaanKalibrasi

- [x] Jadwal.
- [x] Pelaksana.
- [x] Penyedia.
- [x] Hasil.
- [x] Sertifikat.
- [x] Finalisasi.
- [x] Next due generation.

## 14.04 TitikUkur

- [x] Definisi titik.
- [x] Expected.
- [x] Tolerance.
- [x] Actual result.
- [x] Pass/fail.

## 14.05 Reminder

- [x] Due soon.
- [x] Overdue.
- [x] Prevent duplicate notifications.
- [x] Dashboard.

Pesan pengingat terlambat sempat berbunyi "telah terlambat -5 hari":
`diffInDays()` Carbon 3 bertanda, dan selisihnya dihitung dari hari ini ke
tanggal jatuh tempo yang sudah lewat. Kini dihitung dari tanggal yang lebih
awal ke yang lebih akhir dan dijadikan bilangan bulat; test memeriksa isi
pesannya utuh untuk kedua cabang. Kontrak dan kepatuhan diperiksa dan tidak
terkena: selisihnya sudah bilangan bulat searah, dan angkanya hanya dicetak
selagi masih positif.

### Gate 14

Riwayat kalibrasi lengkap, sertifikat authorized, next due konsisten (Terpenuhi).

---

# FASE 15 — Anggaran dan Perencanaan

## 15.01 Anggaran

- [x] Periode.
- [x] Total.
- [x] Status.
- [x] Approval bila dibutuhkan.

## 15.02 PosAnggaran

- [x] CRUD.
- [x] Parent bila digunakan.
- [x] Nilai.
- [x] Scope.

## 15.03 TransaksiAnggaran

- [x] Komitmen.
- [x] Realisasi.
- [x] Pelepasan komitmen.
- [x] Adjustment sesuai izin.
- [x] Sisa dihitung dari ledger/transaksi.
- [x] Cegah race condition.

## 15.04 UsulanAset

- [x] Draft.
- [x] Submit.
- [x] Penilaian.
- [x] Prioritas.
- [x] Approval.
- [x] Histori.

## 15.05 RencanaPengadaan

- [x] Buat dari usulan.
- [x] Detail.
- [x] Estimasi.
- [x] Pos anggaran.
- [x] Status.

### Gate 15

Sisa anggaran dapat direkonsiliasi dari transaksi, bukan angka edit manual (Terpenuhi).

---

# FASE 16 — Procurement

## 16.01 PermintaanPembelian

- [x] Draft.
- [x] Detail item.
- [x] Total.
- [x] Submit.
- [x] Approval.
- [x] Budget validation.

## 16.02 PermintaanPenawaran

- [x] Buat dari request.
- [x] Pilih penyedia.
- [x] Deadline.
- [x] Status.

## 16.03 PenawaranPenyedia

- [x] Header.
- [x] Detail.
- [x] Harga.
- [x] Pajak/biaya bila schema mendukung.
- [x] Lampiran.
- [x] Evaluasi.

## 16.04 PesananPembelian

- [x] Generate dari hasil.
- [x] Nomor.
- [x] Detail.
- [x] Total.
- [x] Approval.
- [x] Kirim/status.

## 16.05 PenerimaanPembelian

- [x] Partial receipt.
- [x] Full receipt.
- [x] Cegah over-receipt.
- [x] Kondisi.
- [x] Dokumen.
- [x] Integrasi register aset untuk item aset.
- [x] Integrasi stok untuk item suku cadang.

## 16.06 TagihanPenyedia

- [x] Invoice.
- [x] Matching ke PO/receipt.
- [x] Status.
- [x] Lampiran.

## 16.07 PembayaranPenyedia

- [x] Payment record.
- [x] Partial/full.
- [x] Reference.
- [x] Audit.

### Gate 16

Flow procurement end-to-end lulus test dan tidak menghasilkan mismatch total/quantity (Terpenuhi).

---

# FASE 17 — Kontrak

## 17.01 Kontrak

- [x] CRUD.
- [x] Penyedia.
- [x] Tanggal.
- [x] Nilai.
- [x] Status.
- [x] Dokumen.

## 17.02 KontrakAset

- [x] Attach aset.
- [x] Validasi tenant.
- [x] Periode coverage.

## 17.03 LayananKontrak

- [x] Jenis layanan.
- [x] SLA.
- [x] Limit bila ada.
- [x] Hubungan ke PerintahKerja vendor.

## 17.04 Reminder

- [x] H-90/H-60/H-30 configurable.
- [x] Expired.
- [x] Notification.
- [x] Dashboard.

### Gate 17

Pekerjaan vendor dapat ditelusuri ke penyedia dan kontrak aktif (Terpenuhi).

---

# FASE 18 — Kepatuhan dan Sertifikasi

## 18.01 StandarKepatuhan

- [x] CRUD.
- [x] Scope.
- [x] Version metadata.
- [x] Status.

## 18.02 PersyaratanKepatuhan

- [x] Requirement.
- [x] Evidence type.
- [x] Frequency bila ada.

## 18.03 KepatuhanAset

- [x] Assign standard.
- [x] Status.
- [x] Evidence.
- [x] Review.
- [x] Expiry.

## 18.04 SertifikasiAset

- [x] Nomor.
- [x] Penerbit.
- [x] Periode.
- [x] File.
- [x] Status.
- [x] Reminder.

### Gate 18

Tidak ada standard regulator spesifik yang di-hardcode sebagai core Amanpoll (Terpenuhi).

---

# FASE 19 — Integrasi, Webhook, Outbox, Idempotensi

## 19.01 IntegrasiEksternal

- [x] CRUD konfigurasi.
- [x] Credential encryption.
- [x] Test connection.
- [x] Status.
- [x] Audit.

## 19.02 PemetaanDataEksternal

- [x] Internal ↔ external mapping.
- [x] Conflict state.
- [x] Manual resolve.

## 19.03 SinkronisasiEksternal

- [x] Pull/push abstraction.
- [x] Queue.
- [x] Status.
- [x] Retry.
- [x] Error details aman.

## 19.04 PanggilanBalikWeb

- [x] Endpoint config.
- [x] Secret.
- [x] Event subscription.
- [x] Disable.

## 19.05 PengirimanPanggilanBalikWeb

- [x] Queue.
- [x] Signature.
- [x] Retry/backoff.
- [x] Delivery log.
- [x] Final failed state.

## 19.06 KotakKeluarPeristiwa

- [x] Write dalam transaction bisnis.
- [x] Worker publish.
- [x] Mark processed.
- [x] Retry.
- [x] Lock concurrency.

## 19.07 KunciIdempotensi

- [x] Middleware/service.
- [x] Scope organisasi + endpoint + key.
- [x] Request fingerprint.
- [x] Cached response bila aman.
- [x] Conflict bila key dipakai payload berbeda.
- [x] TTL/cleanup.

### Gate 19

Satu contoh endpoint kritis dan satu event eksternal harus terbukti idempotent. (Terpenuhi)

---

# FASE 20 — Offline PWA

Jangan mulai sebelum flow online stabil.

## 20.01 Installability

- [x] Manifest.
- [x] Icons.
- [x] Service worker.
- [x] Offline fallback page.
- [x] Update strategy.

## 20.02 Cache Strategy

- [x] App shell.
- [x] Jangan cache response sensitif secara sembarang.
- [x] Tenant/user cache key.
- [x] Clear local data saat logout.

## 20.03 AntrianSinkronisasi

- [x] Local mutation ID.
- [x] Queue.
- [x] Retry.
- [x] Status.

## 20.04 PenandaSinkronisasi

- [x] Last sync.
- [x] Entity version.
- [x] Conflict detection.

## 20.05 Offline Teknisi

- [x] Assignment list.
- [x] Asset summary.
- [x] Checklist.
- [x] Draft pekerjaan.
- [x] Draft catatan.
- [x] Queue perubahan.
- [x] UX indicator offline/unsynced.

## 20.06 Conflict Resolution

- [x] Server version check.
- [x] Tidak overwrite diam-diam.
- [x] UI conflict untuk kasus yang perlu user.
- [x] Audit resolution.

### Gate 20

Simulasi offline → input → reconnect → sync tidak menggandakan transaksi. (Terpenuhi)

---

# FASE 21 — Dashboard dan Laporan

Dikerjakan setelah sumber transaksi stabil agar dashboard tidak dibangun di atas data palsu.

## 21.01 Query Metrics

- [x] Asset counts.
- [x] Asset condition.
- [x] Complaint.
- [x] Work order.
- [x] SLA.
- [x] Downtime.
- [x] MTTR.
- [x] MTBF.
- [x] Cost.
- [x] Stock.
- [x] Calibration.
- [x] Preventive.
- [x] Procurement.
- [x] Budget.
- [x] Contract.
- [x] Compliance.

## 21.02 Dashboard

- [x] Role-aware.
- [x] Date filter.
- [x] Unit/location filter.
- [x] Responsive.
- [x] Empty states.
- [x] No fake chart.

## 21.03 LaporanTersimpan

- [x] Save filter.
- [x] Ownership.
- [x] Shared scope sesuai izin.
- [x] Delete.

## 21.04 DasborTersimpan

- [x] Layout.
- [x] Komponen.
- [x] Preference.
- [x] Validation.

## 21.05 Export

- [x] CSV/XLSX/PDF hanya bila benar-benar diperlukan.
- [x] Queue untuk export besar.
- [x] Notification saat selesai.
- [x] Authorization saat download.

### Gate 21

Setiap KPI utama memiliki definisi formula yang terdokumentasi dan query test. (Terpenuhi)

---

# FASE 22 — SaaS dan Langganan

## 22.01 FiturPaket

- [x] Master feature.
- [x] Key stabil.
- [x] Deskripsi.

## 22.02 PaketLangganan

- [x] CRUD platform.
- [x] Harga.
- [x] Period.
- [x] Status.

## 22.03 PaketFitur

- [x] Entitlement.
- [x] Limit.
- [x] Validation.

## 22.04 Langganan

- [x] Start.
- [x] Trial.
- [x] Active.
- [x] Grace.
- [x] Expired.
- [x] Cancel.

## 22.05 Entitlement Middleware

- [x] Backend check.
- [x] UI check.
- [x] Limit check.
- [x] Clear error.

## 22.06 Tagihan dan Pembayaran

- [x] Invoice.
- [x] Payment.
- [x] Provider abstraction.
- [x] Webhook idempotency.
- [x] Reconciliation.

### Gate 22

Tenant expired/limited tidak dapat bypass restriction melalui API langsung. (Terpenuhi)

---

# FASE 23 — UI/UX Completion

Implementasi visual mengikuti `DESIGN.md`.

## 23.01 Shell

- [x] Sidebar desktop.
- [x] Sidebar collapsed.
- [x] Mobile drawer.
- [x] Topbar.
- [x] Breadcrumb.
- [x] Page header.
- [x] Notification center.
- [x] User menu.

Topbar sempat hanya berisi tombol sidebar dan lonceng. Kini ada pencarian global
yang dibuka dari kotak di header, Ctrl+K / ⌘K, atau "/". Isinya enam modul (aset,
perintah kerja, keluhan, suku cadang, penyedia, kontrak) ditambah halaman menu.

Keputusan dan alasannya:
- Tidak memakai mesin pencari atau indeks terpisah, karena shared hosting tidak
  mengizinkannya. Tiap modul dicari dengan LIKE lewat model Eloquent biasa, pada
  kolom yang sama dengan kotak cari halaman daftarnya. Tenancy dan lingkup
  unit/ruangan ikut berlaku dari global scope tanpa kode tambahan.
- Izin dibaca dari policy `viewAny` halaman daftar masing-masing, jadi modul yang
  daftarnya tidak boleh dibuka memang tidak dicari.
- Kode yang persis sama didahulukan. Tanpa itu, mengetik "AST-12" bisa
  menenggelamkan aset AST-12 di bawah AST-1200.
- Halaman menu dicari di klien dari menu yang sudah tersaring izin dan paket.
  Pencocokannya per awal kata, karena pencocokan substring membuat "vent" memunculkan
  deretan halaman "Preventif" di atas ventilatornya.
- Cmdk ditolak karena menambah dependensi. Dialognya dirakit dari Dialog yang ada,
  dengan peran combobox/listbox untuk papan ketik dan pembaca layar.

Saat mencoba hasilnya, dua halaman ternyata jatuh begitu ada satu baris data:
Inspeksi Berkala dan riwayat pengiriman webhook. Controller-nya mengirim paginator
mentah yang berserialisasi datar, padahal halaman membaca `meta`. Keduanya kini
dibungkus `DaftarTersaring::paginasi`.

## 23.02 Standard Components

- [x] Button.
- [x] Input.
- [x] Select.
- [x] Combobox.
- [x] Date picker.
- [x] Textarea.
- [x] Checkbox.
- [x] Radio.
- [x] Switch.
- [x] Badge.
- [x] Alert.
- [x] Dialog.
- [x] Drawer/Sheet.
- [x] Tabs.
- [x] DataTable.
- [x] Pagination.
- [x] EmptyState.
- [x] ErrorState.
- [x] Skeleton.
- [x] FileUploader.
- [x] SearchFilterBar.
- [x] StatusTimeline.
- [x] StatCard.
- [x] ActivityFeed.

## 23.03 Responsive Audit

Test minimal:

- [x] 360x800.
- [x] 390x844.
- [x] 768x1024.
- [x] 1024x768.
- [x] 1280x800.
- [x] 1440x900.
- [x] 1920x1080.

Audit keterbacaan menyusul setelah pengguna mengeluhkan teks yang hilang saat
di-hover. Pemindai Playwright membaca kontras setiap teks dalam keadaan diam,
hover, dan fokus di 81 halaman tenant dan platform. Pada kode lama ada 944
pelanggaran diam, 190 hover, dan 332 fokus; pada kode baru nol untuk ketiganya.
Tiga jalur persetujuan dan izin yang ikut terpindai ternyata endpoint JSON, jadi
tidak dihitung.

Akar masalahnya sedikit, tetapi menyebar ke mana-mana:
- Token `accent` shadcn, yaitu latar hover, fokus, dan opsi tersorot, bernilai
  biru pekat Teknisi-500, sehingga teks gelap berdiri di atas biru. Kini nilainya
  tint Teknisi-50.
- Grafit-500 sebagai abu metadata tidak lolos 4,5:1.
- Sekitar 200 kelas warna menunjuk shade yang tidak punya token. Tailwind 4 diam
  saja dan tidak menghasilkan CSS untuk kelas itu.
- Aturan `* { border-color }` di luar `@layer` menimpa setiap utilitas border.
- Teks status memakai shade -600 di atas tint.

Opsi mengganti palet ditolak. Cukup menambah tint dan shade -700 lalu memetakan
ulang pemakaiannya. `WarnaPaletTerdefinisiTest` menjaga agar shade tanpa token
tidak kembali. Pekerjaan dibagi ke tiga agen: komponen bersama, halaman
operasional tenant, dan konsol/publik. Tiap agen mengukur, memperbaiki, lalu
mengukur ulang.

Pemindaian itu juga membuka bug yang bukan soal warna:
- Daftar gudang jatuh 500 begitu ada satu gudang.
- Butir checklist dan templat inspeksi tidak pernah tampil, karena halaman membaca
  relasi camelCase sementara model mentah berserialisasi snake_case. Jebakannya:
  dua halaman daftar templat justru menyusun barisnya sendiri dengan camelCase.
  Karena itu tipe barisnya dipisah (`BarisTemplatInspeksi`), bukan diseragamkan.
- Kalkulator publik jatuh 500 karena angka dari formulir tiba sebagai teks,
  padahal aturan `integer` tidak mengubah tipenya. Hasil kalkulator dan label QR
  pun tidak pernah sampai ke halaman.
- Legenda donat kondisi aset menulis 4 aset sebagai "4%". Rincian KPI persen
  berisi jumlah, jam, atau uang, sehingga kini punya `SatuanRincian` sendiri yang
  juga dipakai ekspor.

Pesan validasi bawaan Laravel juga tidak terbaca. `APP_LOCALE=id` tanpa folder
`lang/` membuat setiap aturan tanpa `messages()` tampil sebagai kunci mentah
(`validation.required`) di seluruh aplikasi. Setelah folder dasar baru itu
disetujui, `lang/id/validation.php` menerjemahkan semua kunci bawaan. Laravel
sendiri sudah memecah nama isian PascalCase menjadi kata. Namun isian hasil
pemekaran bintang dibiarkannya mentah ("Detail.0.HargaSatuan"), jadi
`NamaIsianBaris` dipasang lewat resolver validator. Resolver dipilih karena hanya
lewat jalur itu format bawaan berlaku untuk setiap validator, termasuk
`Validator::make` di layanan, bukan hanya FormRequest. Label dari `attributes()`
tetap didahulukan. Hanya berkas validasi yang diterjemahkan: auth, passwords,
dan pagination tidak dipakai, sebab aplikasi menulis pesan dan paginasinya
sendiri.

### Gate 23

Tidak ada halaman utama yang memerlukan desktop untuk menyelesaikan pekerjaan teknisi dasar. (Terpenuhi)

---

# FASE 24 — Security Hardening

- [x] CSRF audit.
- [x] XSS audit.
- [x] Authorization audit.
- [x] Mass assignment audit.
- [x] File upload audit.
- [x] API rate limit.
- [x] Login rate limit.
- [x] Session cookie secure.
- [x] Credential encryption.
- [x] API key hashing.
- [x] Cross-tenant penetration test internal.
- [x] IDOR test.
- [x] Export authorization test.
- [x] Audit log tamper resistance sesuai kemampuan schema.
- [x] Dependency vulnerability check.
- [x] Production debug off.

### Gate 24

Tidak ada known critical/high issue yang belum memiliki keputusan mitigasi. (Terpenuhi)

Temuan yang diperbaiki: sesi bertahan setelah pengguna atau organisasinya
dinonaktifkan; halaman dan pembayaran langganan terbuka bagi setiap pengguna
tenant; dasbor kalibrasi tanpa pemeriksaan policy; injeksi rumus pada ekspor
CSV; `OrganisasiId` dapat menunjuk tenant lain lewat mass assignment; rute API,
webhook, dan ekspor tanpa batas laju; kredensial dan hash kunci API ikut
serialisasi model.

Batas yang diterima secara sadar: `HanyaTambah` tidak menyala pada operasi
massal, sehingga kebijakan retensi `catatan-akses:bersihkan` tetap berjalan dan
akses langsung ke basis data tetap di luar jangkauan kode aplikasi.

---

# FASE 24.5 — Pemisahan Host

Prasyarat wajib `MARKETING.md` bagian 1 dan 33, serta `PRD.md` 5.4. Diberi nomor sisipan supaya FASE 25–28 yang sudah dirujuk di banyak tempat tidak perlu dinomori ulang.

Dikerjakan sebagai satu perubahan tersendiri lengkap dengan test, bukan disisipkan di tengah fitur lain, karena menyentuh rute autentikasi yang sudah ada. Ditempatkan sebelum FASE 26 dan 27 agar perubahan rute ikut tercakup oleh Testing Lengkap dan Deployment, bukan membatalkan keduanya.

## 24.5.01 Konfigurasi Host

- [x] `amanpoll.domain.publik`, `amanpoll.domain.dashboard`, `amanpoll.domain.partner` di `config/amanpoll.php`.
- [x] Nilai berasal dari environment, bukan literal di source.
- [x] Bentuk kanonik dipilih antara `amanpoll.com` dan `www.amanpoll.com`.
- [x] Host lokal/staging/produksi sesuai `MARKETING.md` 1.3.
- [x] Tidak ada host produksi yang ditulis di test.

## 24.5.02 Grup Route per Host

- [x] Grup `Route::domain(...)` untuk host publik.
- [x] Grup `Route::domain(...)` untuk host dashboard.
- [x] Rute aplikasi yang kini di root dipindah ke host dashboard.
- [x] Rute autentikasi ikut pindah ke host dashboard.
- [x] Host publik tanpa middleware `auth` dan `organisasi`.
- [x] Root host publik membuka landing page placeholder.
- [x] Root host dashboard mengarahkan pengunjung anonim ke login.
- [x] Tidak ada pengecekan host di dalam controller.

## 24.5.03 Sesi dan Cookie Lintas Host

- [x] `SESSION_DOMAIN` memakai domain induk lewat environment.
- [x] `SESSION_SECURE_COOKIE` dari environment.
- [x] Cookie `SesiPengunjung` memakai domain induk.
- [x] Sesi login hanya berlaku pada host dashboard.
- [x] Host publik tidak pernah membaca sesi organisasi.
- [x] CSRF formulir publik tetap aktif dan tidak lintas host.

## 24.5.04 SEO dan Redirect Host

- [x] `X-Robots-Tag: noindex` pada host dashboard dan partner.
- [x] `robots.txt` melarang crawl pada host non-publik.
- [x] `canonical` selalu memakai host publik.
- [x] Redirect 301 dari bentuk non-kanonik.
- [x] `sitemap.xml` hanya berisi URL host publik.

## 24.5.05 Cache dan Rate Limit Host Publik

- [x] Rate limit rute publik.
- [x] Cache respons halaman publik.
- [x] Halaman publik tidak pernah memuat data tenant.

## 24.5.06 Test Host

- [x] `RouteHostPublikTest`.
- [x] `RouteHostDashboardTest`.
- [x] `NoindexHostDashboardTest`.
- [x] Test menetapkan host dari konfigurasi.

### Gate 24.5

Landing page tampil di host publik dan tidak pernah di host dashboard; root host dashboard tidak pernah menampilkan landing page; tidak ada host yang ditulis langsung di source maupun di frontend; host non-publik terbukti tidak dapat diindeks. (Terpenuhi)

Host dibaca hanya lewat `PetaHost`, dan larangan menuliskannya di source maupun
di frontend ditegakkan tes arsitektur, bukan sekadar disepakati.

Situs publik baru hidup setelah `AMANPOLL_DOMAIN_PUBLIK` diisi. Sebelum itu grup
rutenya tidak didaftarkan sama sekali, sehingga root tetap milik dashboard dan
tidak ada rute yang bertabrakan — itulah yang membuat 540 test yang sudah ada
tidak perlu diubah satu pun.

Dua hal yang sengaja belum lengkap dan menyusul bersama fiturnya:
- Cache respons baru meliputi `robots.txt` dan `sitemap.xml`. Badan halaman
  Inertia memuat token CSRF milik satu sesi, jadi menyajikannya ulang ke orang
  lain tidak aman; pada FASE 32 yang di-cache adalah isi halaman terbitannya,
  bukan respons HTTP-nya.
- Belum ada formulir publik, sehingga "CSRF formulir publik tidak lintas host"
  baru dapat diuji di FASE 32.

---

# FASE 25 — Performance dan Reliability

## 25.01 Database

- [x] Slow query review.
- [x] EXPLAIN query utama.
- [x] Index update berdasarkan query nyata.
- [x] N+1 detection.
- [x] Pagination semua daftar besar.

`Model::preventLazyLoading` dinyalakan di luar produksi, jadi lazy loading tidak
lagi menunggu produksi untuk ketahuan. Ia langsung menemukan dua N+1 nyata:
pendaftaran sequence email membaca template tiap langkah satu per satu, dan satu
helper test menyusuri eksekusi otomasi tanpa memuat event-nya.

EXPLAIN dijalankan di atas tabel terisi, bukan tabel kosong: optimizer memilih
indeks dari statistik, dan tabel kosong tidak punya statistik sehingga rencana
kuerinya tidak berarti apa-apa. Dengan tiga ribu baris, empat daftar terbukti
menyortir di luar indeks karena indeksnya menutup penyaring tetapi berhenti
sebelum kolom pengurut. Indeks barunya mengikuti kueri yang benar-benar
dijalankan, dan filesort hilang setelahnya.

Paginasi dikerjakan dua lapis, karena daftar besar di aplikasi ini ada dalam dua
bentuk. Lima daftar yang merender sendiri — Keluhan, PerintahKerja, Inspeksi,
MutasiStok, ReservasiSukuCadang — dipaginasi di server. Inspeksi menuntut lebih
dari itu: kartu ringkasan dan kotak pencariannya menghitung di sisi klien di
atas seluruh daftar, sehingga memenggal daftarnya akan membuat kartu itu
diam-diam menghitung satu halaman saja. Keduanya dipindahkan ke server.

Dua puluh satu halaman sisanya memakai komponen DataTable bersama yang
memaginasi, mencari, dan mengurutkan di browser. Memindahkannya ke paginasi
server berarti memindahkan pencarian, pengurutan, dan filter faset sekaligus,
dan itu mengubah perilaku dua puluh satu halaman dalam satu tarikan. Atas
keputusan pemilik produk, yang dipasang sekarang adalah batas aman: tiga puluh
kueri pada tabel yang tumbuh dipotong pada `BatasDaftar::MAKS`, dan DataTable
menyatakan di layar ketika daftarnya menyentuh batas itu. Batas ini bukan
paginasi dan tidak berpura-pura menjadi paginasi — baris di luar batas memang
tidak dikirim, dan itu dikatakan kepada pengguna. Konversi penuh ke paginasi
server dijadwalkan terpisah.

Konversi itu kemudian dikerjakan, tetapi tidak untuk kedua puluh satu halaman.
Mode server dibangun ke dalam DataTable satu kali: tanpa prop `server`
perilakunya persis seperti semula, dengan prop itu paginasi, pencarian,
pengurutan, dan faset semuanya berangkat ke server. Yang dibalik hanya dua
belas daftar yang benar-benar tumbuh — Suku Cadang, Stok Suku Cadang, Lokasi,
Pengguna, Tag, Model Aset, Merek, dan lima daftar Pemasaran (Prospek, Kampanye,
Trial, Redirect, Halaman). Sisanya, yang isinya terbatas pada beberapa puluh
baris per organisasi, tetap diolah di browser; membalik daftar yang tidak
pernah menyentuh batasnya hanya menambah permintaan jaringan tanpa menukarnya
dengan apa pun.

Sisi kueri dipegang satu helper, `DaftarTersaring`. Nama kolom tidak pernah
datang dari request: permintaan hanya menyebut kunci, dan kunci yang tidak ada
di daftar milik controller diabaikan alih-alih diteruskan ke SQL. Joker LIKE
dari pengguna dilolos, kalau tidak `%` akan mencocokkan segalanya.

Tiga jebakan yang muncul saat mengerjakannya, dan pantas diingat:

- Angka ringkasan yang dihitung dari array di tangan akan menyusut tiap kali
  pengguna pindah halaman. "Suku cadang di bawah minimum" dipindahkan ke
  agregat SQL atas seluruh data.
- Keadaan kosong yang muncul saat barisnya nol ikut menyembunyikan kotak cari.
  Dengan pencarian di server, pengguna yang kuerinya tidak cocok akan terkunci
  tanpa cara mengubah pencariannya sendiri.
- Kolom turunan relasi tidak dapat diurutkan server. Membiarkan tombol urutnya
  tetap ada berarti menawarkan tombol yang tidak melakukan apa-apa, jadi
  pengurutannya dimatikan.

Cabang JSON `TagController` sengaja tidak ikut dipaginasi: pemilih tag di layar
lain menghabiskan daftar itu sekaligus, dan kalau ikut, tag ke-26 akan hilang
dari pemilih tanpa satu pun pesan galat.

Empat belas daftar sisanya menyusul setelahnya, sehingga seluruh dua puluh
enam halaman DataTable kini berjalan di server. Yang empat belas itu tidak
terancam batas apa pun — kategori, peran, templat, dan pola nomor dokumen
memang berisi puluhan baris — tetapi menyeragamkannya menghapus satu kelas
kebingungan: tidak ada lagi halaman yang mencari, mengurutkan, atau
memaginasi dengan aturan berbeda dari halaman sebelahnya.

Lima di antaranya memakai daftar yang sama untuk mengisi pemilih "Induk" di
form-nya, persis jebakan pemilih tag di atas: kalau ikut dipaginasi,
kategori atau unit ke-26 hilang dari pilihan tanpa satu pun pesan galat.
Kelimanya menerima prop tersendiri yang tidak dipaginasi.

Dua perbaikan pada `DaftarTersaring` muncul dari pekerjaan itu, keduanya
ditemukan saat menulis test:

- Kunci utama dipakai sebagai pemutus seri. Beberapa urutan bawaan punya
  banyak nilai kembar, dan tanpa urutan total yang pasti MySQL boleh
  menukar posisi baris kembar antar permintaan — satu baris muncul di dua
  halaman sementara baris lain tidak pernah muncul. Test perilakunya sempat
  lolos meski pemutus serinya dicabut, karena pada data kecil MySQL
  kebetulan stabil; yang benar-benar menjaganya adalah pemeriksaan bahwa
  kueri-nya memang membawa pemutus seri itu.
- Nomor halaman dibaca dari permintaan yang diserahkan ke kelas itu, bukan
  dari resolver global Laravel.

Indeks `(OrganisasiId, Nama)` ditambahkan untuk urutan bawaan enam daftar
tenant. Diukur pada lima ribu baris satu organisasi: tanpa indeks, pemindaian
seluruh tabel lalu filesort; dengan indeks, rentang indeks tanpa filesort.
Pencarian sendiri tetap tanpa indeks — `LIKE '%kata%'` berawalan joker tidak
dapat memakai indeks apa pun, dan Amanpoll sengaja tidak memasang mesin pencari
terpisah supaya tetap berjalan di shared hosting.

## 25.02 Queue

- [x] Retry policy.
- [x] Failed job handling.
- [x] Idempotent jobs.
- [x] Cron overlap prevention.
- [x] Queue batch size sesuai shared hosting.

Enam job tidak menyatakan `$tries` sehingga diam-diam memakai `--tries=3` dari
baris perintah `queue:work`. Artinya kebijakan percobaan ulang tersimpan di satu
string cron, bukan di job yang mengetahui apakah mengulang dirinya aman. Tiap
job kini menyatakan sikapnya sendiri, dan sikapnya tidak seragam: yang memegang
tangga percobaannya sendiri atau yang jadwalnya segera kembali memakai
`tries = 1`, sisanya tiga kali karena benar-benar idempoten. Lima job pengirim
yang sudah `tries = 3` ternyata tanpa jeda sama sekali, sehingga mereka
menghantam penyedia tepat pada saat penyedia bermasalah.

Kegagalan permanen sebelumnya hanya mengendap di tabel `PekerjaanGagal` yang
tidak dibaca halaman mana pun lalu dipangkas seminggu kemudian; pekerjaan yang
berhenti terlihat persis seperti pekerjaan yang tidak pernah diantrekan.
`Queue::failing` kini menuliskannya ke log aplikasi.

## 25.03 Scheduler

- [x] `withoutOverlapping()` pada task relevan.
- [x] Preventive.
- [x] SLA.
- [x] Reminder.
- [x] Outbox.
- [x] Webhook retry.
- [x] Cleanup.

Lima jadwal berjalan tanpa penjaga sama sekali, dua di antaranya berbahaya bila
tumpang tindih: satu melepas hold suku cadang, satu lagi menyusuri seluruh
organisasi sambil mengirim notifikasi.

Yang lebih halus, dua belas jadwal memakai `withoutOverlapping()` tanpa argumen,
yang berarti kunci 1440 menit. Shared hosting rutin membunuh proses yang
kelamaan, dan kunci yang ditinggalkan proses mati menahan jalan berikutnya
sampai kunci itu kedaluwarsa — untuk jadwal per jam berarti dua puluh empat kali
jalan yang hilang diam-diam. Seluruh jadwal kini menyebut masa berlakunya
sendiri, dan `JadwalTugasTest` menolak jadwal baru yang lupa memasangnya.

## 25.04 Backup

- [x] Database backup.
- [x] File backup.
- [x] Retention.
- [x] Restore test.
- [x] Dokumentasi recovery.

`cadangan:jalankan` berjalan harian, `cadangan:daftar` menampilkan isinya, dan
`cadangan:pulihkan` memulihkan satu dump dengan konfirmasi yang di produksi
menuntut nama basis data diketik ulang.

Sabotase membuktikan pemeriksaan "berkas tidak kosong" tidak cukup: gzip atas
masukan kosong tetap menghasilkan berkas belasan byte, sehingga dump yang sukses
tanpa mengeluarkan apa pun lolos. Penjaganya diganti menjadi pemeriksaan isi —
dump wajib memuat definisi tabel — dan diuji dengan mengarahkan `mysqldump` ke
`true`, perintah yang selalu sukses tanpa keluaran.

Runbook pemulihannya ada di `docs/RUNBOOK-PEMULIHAN.md`, termasuk yang tidak
dijanjikan: tanpa replikasi, tanpa point-in-time recovery, dan cadangan tinggal
di server yang sama dengan datanya.

### Gate 25

Restore test dilakukan, bukan hanya backup job tersedia.

Terpenuhi, dan dijalankan otomatis. `PemulihanCadanganTest` mencadangkan basis
data, memulihkannya ke basis data lain, lalu membandingkan isinya — termasuk
membuktikan bahwa baris yang lahir sesudah pencadangan tidak ikut terbawa,
karena cadangan yang memulihkan keadaan yang salah lebih berbahaya daripada
cadangan yang gagal terang-terangan. Sembilan sabotase atas penjaga cadangan
seluruhnya tertangkap.

Di luar butir FASE 25, `NomorDokumenKonkurensiTest` ditulis ulang di atas
MariaDB. Ia sebelumnya menguji `lockForUpdate()` di atas SQLite, yang tidak
mengenal klausa itu sama sekali dan mengompilasinya menjadi kosong; test itu
gagal dengan `database is locked` saat suite penuh berjalan dan, ketika lulus,
lulus karena alasan yang salah. Versi MariaDB-nya menangkap penghapusan
`lockForUpdate()` tiga dari tiga kali.

---

# FASE 26 — Testing Lengkap

## 26.01 Unit Test

Prioritas:

- [x] Kalkulasi SLA.
- [x] Kalkulasi anggaran.
- [x] Stock rule.
- [x] State transition.
- [x] Penomoran.
- [x] Timezone.
- [x] Idempotency.
- [x] Approval rule.

Cakupan sudah besar sebelum fase ini dimulai, jadi yang dikerjakan lebih dulu
adalah audit, bukan menulis ulang. Pertanyaannya bukan "adakah test-nya",
melainkan "apakah test itu menjaga". Sebuah butir baru dianggap terjaga bila
kode yang dijaganya dirusak dengan sengaja dan test-nya merah. Membaca test
lalu menilainya cukup tidak dihitung.

Cara itu menemukan celah di test yang sudah lama hijau:

- zona waktu lokasi pada kalkulasi SLA: mencabut `setTimezone` lolos;
- reset nomor dokumen bulanan: mengganti format periode `Y-m` menjadi `Y`
  lolos kedelapan belas test penomoran;
- matriks transisi status keluhan: menambah `Baru -> Selesai` lolos seluruh
  test keluhan;
- lingkup organisasi dan masa berlaku kunci idempotensi;
- masa berlaku penugasan peran penyetuju: mencabut filternya meloloskan 51
  test.

Semuanya kini dijaga, masing-masing dengan sabotase yang menggagalkannya.

Timezone semula belum dicentang karena penyimpanannya bertentangan dengan ADR
0001: `AmanpollServiceProvider` menimpa `app.timezone` menjadi `Asia/Jakarta`
sejak commit pertama, sehingga waktu kerja dari aplikasi offline yang dikirim
berzona (`...Z`) tersimpan bergeser tujuh jam. Keputusannya: pindah ke UTC
sekarang, sebelum ada data produksi. Alternatifnya, tetap WIB dan menambal jalur
offline, ditolak karena rumah sakit WITA dan WIT tetap salah di ekspor dan di
batas "hari ini", dan setiap pintu masuk baru harus ingat mengonversi sendiri.

UTC kini ditegakkan di tiga lapis, bukan pada disiplin pemanggil. Penimpaan
zona dicabut (`config/app.php` mengunci `'UTC'`; `APP_TIMEZONE` dibuang dari
contoh `.env` karena tidak pernah dibaca). Sesi basis data disetel `+00:00`,
karena ratusan kolom `DEFAULT CURRENT_TIMESTAMP` dihitung MySQL dengan zona
sesinya sendiri. Objek waktu berzona dinormalkan ke UTC di dua pintu: saat
disetel ke atribut model (`MenyimpanWaktuDalamUtc`, dipakai `ModelDasar`,
`Pengguna`, `Partner`, `AdminPlatform`) dan saat diikat ke kueri
(`MengikatWaktuDalamUtc` pada koneksi MySQL/MariaDB). Keduanya perlu: Eloquent
memformat jam dinding objek waktu apa adanya, dan `Connection::prepareBindings()`
melakukan hal yang sama untuk `whereBetween` dan `update()` lewat query builder.
Tanggal tanpa jam untuk kolom berjam (dari pemilih tanggal) dibaca sebagai awal
hari di zona organisasi, bukan tengah malam UTC yang di Jakarta sudah 07:00.

Keputusan kalender dipindahkan ke `KalenderOrganisasi`: jatuh tempo kalibrasi,
kontrak, kepatuhan, dan preventif; pengingat dan pencegah duplikatnya; periode
nomor dokumen; tanggal langganan, uji coba, dan tagihan; penyaring tanggal jejak
audit; tanggal kedaluwarsa kunci API. `hariIni()` sengaja mengembalikan tengah
malam UTC dari tanggal lokal, bentuk yang sama dengan kolom `date`, supaya
perbandingan dan selisih hari di sekitarnya tidak perlu ditulis ulang.
`FilterMetrik` kini memegang zonanya: `dari`/`sampai` adalah momen UTC batas
hari lokal untuk kolom berjam, `tanggalDari()`/`tanggalSampai()` untuk kolom
`date`, dan tren harian dikelompokkan dengan `CONVERT_TZ(kolom, '+00:00',
offsetSql())`. Tabel zona bernama MySQL tidak tersedia di shared hosting, jadi
dipakai offset; Indonesia tidak mengenal waktu musim panas, sehingga satu offset
berlaku untuk seluruh rentang. Pemasaran dan penagihan adalah milik vendor, jadi
memakai zona bawaan, bukan zona tenant. Kolom berjam di ekspor dicetak di jam
dinding organisasi; kolom `date` tidak digeser.

Di frontend, enam formulir `datetime-local` mengirim jam dinding tanpa zona dan
kini mengonversinya lewat `@/lib/waktu`. Ditemukan juga bug yang tidak
bergantung pada penyimpanan: lima belas nilai awal "hari ini" dan preset
rentang dasbor memakai `new Date().toISOString().slice(0, 10)`, yaitu tanggal
UTC, sehingga sebelum pukul 07:00 WIB formulir terisi tanggal kemarin.

Jebakan yang ditemukan di jalan:

- `CURRENT_DATE` mengikuti jam server basis data, yang tidak ikut dibekukan
  test. Sabotase yang mengembalikannya sempat lolos karena jam asli server
  kebetulan menjangkau tanggal uji. Kini hari ini diikat sebagai parameter, dan
  test-nya memakai tahun 2099 supaya regresi itu terlihat.
- Cast `date:Y-m-d` bukan "kolom tanggal" bagi Eloquent (`custom_datetime`),
  jadi tidak dapat dipakai untuk menguji bahwa tanggal kalender tidak digeser.
- Kegagalan eskalasi SLA pada simulasi awal ternyata bug penyimpanan itu
  sendiri: batas berzona WIB tersimpan sebagai jam UTC. Kini lulus tanpa diubah.
- Satu test kepatuhan memakai `CarbonImmutable::today()->addDay()` sebagai
  "besok". Ia gagal pukul 19:38 UTC justru karena kodenya sudah benar: pada jam
  itu besok UTC adalah hari ini di Jakarta.
- `LayananEksporLaporan` adalah singleton; menyuntikkan `KalenderOrganisasi`
  (scoped) ke sana akan membekukan konteksnya di worker antrean. Zonanya dibaca
  dari `FilterMetrik`.

Setiap perbaikan diuji pada jam batas (umumnya 18:30 UTC, sudah 01:30 WIB hari
berikutnya), dan 29 sabotase yang mengembalikan perilaku lama seluruhnya
tertangkap. `TanggalKalenderTidakDariJamUtcTest` menolak pola yang menghasilkan
tanggal UTC di `app/` dan `resources/js`; dijalankan terhadap kode sebelum
perubahan, ia menandai 65 titik PHP dan 22 titik frontend. Sebelas test yang
menyatakan jam tersimpan dalam WIB kini membaca momennya di zona WIB.

Belum ada data produksi, jadi tidak ada migrasi data. Lingkungan yang sudah
berisi data dari sebelum perubahan ini (demo, staging) sebaiknya disemai ulang:
kolom yang ditulis aplikasi tersimpan dalam WIB, sedangkan kolom yang diisi
`DEFAULT CURRENT_TIMESTAMP` mengikuti zona server saat itu, sehingga satu
pergeseran seragam tidak akan benar untuk keduanya.

## 26.02 Feature Test

- [x] Auth.
- [x] Tenant.
- [x] RBAC.
- [x] Aset.
- [x] Mutasi.
- [x] Stock.
- [x] Keluhan.
- [x] PerintahKerja.
- [x] Preventive.
- [x] Kalibrasi.
- [x] Procurement.
- [x] Approval.
- [x] Integration.
- [x] Subscription.

Keempat belas butir sudah dijaga test yang ada, dibuktikan dengan cara yang
sama. Satu celah tenant ditemukan di luar daftar butir: tiga formulir kalibrasi
memeriksa ID penyedia, perintah kerja, pelaksana, jenis kalibrasi, aset, dan
kategori hanya sebagai string. ID milik organisasi lain lolos dan tersimpan,
sehingga kalibrasi rumah sakit A dapat menunjuk penyedia milik rumah sakit B.
Rujukannya kini dibatasi dengan `Rule::exists(...)->where('OrganisasiId', ...)`,
konvensi yang sudah dipakai request lain. Rencana milik organisasi lain
karenanya ditolak di validasi, bukan lagi 404 dari `findOrFail`. Keduanya
menolak; yang pertama lebih awal dan menyebut kolomnya.

Satu pengamatan dibiarkan sebagai pertanyaan produk, bukan bug: perintah kerja
preventif dapat ditutup walau daftar periksanya masih Draft.

## 26.03 End-to-End Critical Paths

- [x] Setup tenant → user → location → aset.
- [x] Keluhan → perintah kerja → sparepart → close.
- [x] Preventive → schedule → work order → checklist → close.
- [x] Kalibrasi → hasil → sertifikat → next due.
- [x] Usulan → procurement → receipt → asset/stock.
- [x] Mutasi → approval → handover → history.
- [x] Webhook retry.
- [x] Offline sync idempotent.

Sebelum fase ini tidak ada test yang menjalankan satu alur penuh; yang ada
menguji potongannya per domain. Delapan alur di `tests/Feature/AlurKritis/`
berjalan lewat rute HTTP seperti pengguna sungguhan, memeriksa invarian di
tengah jalan (stok bertambah tepat sebesar yang diterima, status berpindah
sesuai urutan, persetujuan benar-benar dibutuhkan sebelum langkah berikutnya),
dan menyemai organisasi kedua yang dipastikan tidak tersentuh.

Justru sambungan antardomain itulah yang patah. Empat bug ditemukan dan
diperbaiki, masing-masing dibuktikan dua arah (tanpa perbaikan test-nya merah
dengan pesan yang tepat, dengan perbaikan hijau):

- Jadwal preventif tidak pernah ditandai selesai. Laporan menghitung
  keterlambatan dari jadwal "Terjadwal" dan kepatuhan dari jadwal "Selesai",
  jadi pekerjaan yang tuntas tetap tampil terlambat dan KPI kepatuhan
  preventif selalu 0%. Status jadwal kini diturunkan dari status perintah
  kerjanya, termasuk saat dibuka kembali dan dibatalkan.
- Mutasi antar unit mengosongkan lokasi aset: permintaan yang hanya menyebut
  unit tujuan menimpa `LokasiId` dengan null.
- Penyetuju berbasis unit tidak memeriksa masa berlaku penugasan, sehingga
  orang yang sudah dipindah tugas tetap dapat menyetujui atas nama unit
  lamanya.
- Koreksi titik ukur kalibrasi tersimpan kosong bila referensinya diambil dari
  templat, karena dihitung sebelum referensinya dilengkapi.

Webhook keluar (`outbox:proses`, `panggilan-balik:kirim-ulang`) sebelumnya tidak
diuji sama sekali; kini keduanya diuji dengan jadwal kirim ulang dan tanpa
pengiriman ganda. Pada sinkronisasi offline, kembaran baris pada akhirnya dijaga
indeks unik basis data; hitungan efeknya tidak dapat dibuktikan merah terpisah
dari indeks itu tanpa mengubah skema, jadi yang dibuktikan dengan sabotase
adalah jalur kodenya.

Test dikerjakan tiga agen serentak di satu working tree. Masing-masing memakai
basis data test sendiri (`DB_DATABASE` dari lingkungan menang atas
`phpunit.xml`, karena `<env>` di sana tanpa `force`), dan sabotase dijalankan di
bawah kunci baca-tulis: test biasa mengambil kunci bersama, sabotase kunci
eksklusif, dan berkasnya dipulihkan otomatis. Tanpa itu sabotase satu agen
akan merusak test agen lain yang sedang berjalan di berkas `app/` yang sama.

### Gate 26

Tidak ada critical path release yang hanya diuji manual. Terpenuhi untuk
kedelapan alur di atas, dan zona waktu penyimpanan di 26.01 sudah diputuskan
dan diterapkan.

---

# FASE 27 — Deployment Niagahoster Business

## 27.01 Struktur Hosting

Target:

```text
/home/USER/domains/DOMAIN/
├── amanpoll/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   └── .env
└── public_html/
    ├── index.php
    ├── .htaccess
    └── build/
```

- [ ] Source utama di luar `public_html`.
- [ ] Hanya public assets exposed.
- [ ] `.env` tidak public.
- [ ] Storage permission benar.
- [ ] APP_ENV=production.
- [ ] APP_DEBUG=false.
- [ ] HTTPS.

## 27.02 Host dan Subdomain

Situs publik dan sistem dipisah per host sesuai `PRD.md` 5.4 dan `MARKETING.md` bagian 1, tetapi tetap satu instalasi:

```text
amanpoll.com            → public_html   (situs publik)
dashboard.amanpoll.com  → public_html   (sistem penuh)
partner.amanpoll.com    → public_html   (tahap lanjut)
```

- [ ] Subdomain dibuat di hPanel dengan document root sama seperti domain utama.
- [ ] Tidak ada duplikasi source, `vendor`, `build`, atau `.env` per host.
- [ ] Host diisi lewat environment, bukan literal di source.
- [ ] SSL aktif untuk setiap host, termasuk subdomain.
- [ ] `SESSION_DOMAIN` diisi domain induk agar sesi berlaku lintas subdomain.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] Redirect 301 dari bentuk non-kanonik ke bentuk kanonik yang dipilih.
- [ ] Host non-publik mengirim `noindex` dan `robots.txt` yang melarang crawl.
- [ ] Root host publik membuka landing page; root host sistem mengarahkan pengunjung anonim ke login.

## 27.03 Build

- [ ] `composer install --no-dev --optimize-autoloader`.
- [ ] Build frontend sebelum upload bila Node tidak tersedia/diinginkan di server.
- [ ] Upload `public/build`.
- [ ] `php artisan optimize`.
- [ ] `php artisan storage:link` bila strategi file public memerlukannya.

## 27.04 Cron

- [ ] Scheduler.
- [ ] Queue worker pendek.
- [ ] Queue command tidak overlap berbahaya.
- [ ] Test log cron.
- [ ] Test reminder.
- [ ] Test outbox.

## 27.05 Database Production

- [ ] Buat database dari hPanel.
- [ ] Gunakan schema hosting tanpa `CREATE DATABASE`.
- [ ] Backup sebelum deployment schema change.
- [ ] Migration/SQL change terdokumentasi.
- [ ] Tidak menjalankan destructive change tanpa backup.

### Gate 27

Smoke test production lulus setelah deployment, dan setiap host terbukti melayani isi yang benar: landing page pada host publik, login/dashboard pada host sistem, tanpa kebocoran halaman antar host.

---

# FASE 28 — UAT dan Release

## 28.01 UAT Role

- [ ] Admin Organisasi.
- [ ] Manager.
- [ ] Supervisor.
- [ ] Teknisi.
- [ ] Pelapor.
- [ ] Gudang.
- [ ] Pengadaan.
- [ ] Auditor.

## 28.02 UAT Device

- [ ] Mobile Android browser.
- [ ] Mobile iOS Safari.
- [ ] Tablet.
- [ ] Laptop 1366/1440.
- [ ] Desktop FHD.

## 28.03 Release Checklist

- [ ] Tidak ada lorem ipsum.
- [ ] Tidak ada data dummy.
- [ ] Tidak ada TODO critical.
- [ ] Tidak ada debug endpoint.
- [ ] Tidak ada default password.
- [ ] Error page production.
- [ ] Terms/privacy bila dibutuhkan.
- [ ] Backup verified.
- [ ] Cron verified.
- [ ] Queue verified.
- [ ] Email verified bila digunakan.
- [ ] Domain/SSL verified.
- [ ] Version/tag release dibuat.

---

# FASE 29–38 — Growth & Marketing

Sumber: `MARKETING.md`. Fase-fase ini dimulai setelah FASE 28 dan mengasumsikan FASE 24.5 sudah lulus gate; tanpa pemisahan host, attribution lintas host tidak dapat dibuktikan.

Urutan mengikuti `MARKETING.md` bagian 33: prasyaratnya adalah Foundation + IAM (FASE 01–04), Notifikasi + Integrasi (FASE 06 dan 19), Langganan (FASE 22), UI Core (FASE 23), dan Hardening (FASE 24) — seluruhnya sudah terlewati pada titik ini.

FASE 29–37 adalah MVP `MARKETING.md` bagian 32 butir 1–10. FASE 38 memuat butir 11–17 sebagai garis besar; checklist rincinya disusun setelah MVP lulus gate, supaya tidak lapuk sebelum dikerjakan.

Berlaku untuk seluruh fase di bawah: domain `Pemasaran` tidak boleh menduplikasi Langganan, Billing, Notifikasi, Integrasi, Audit, Persetujuan, Organisasi, dan IAM; mutasi ke domain lain lewat application service atau domain contract; seluruh job asynchronous memakai database queue tanpa Redis, Horizon, Supervisor, atau daemon permanen.

---

# FASE 29 — Fondasi Pemasaran

## 29.01 Bounded Context

- [x] `app/Domain/Pemasaran` sesuai struktur `MARKETING.md` bagian 3.
- [x] Routes domain terdaftar lewat `DomainServiceProvider`.
- [x] Berkas dibuat saat ada isinya, tanpa barrel `index.ts` di frontend.

## 29.02 Konfigurasi Pemasaran

- [x] Tabel `KonfigurasiPemasaran`.
- [x] Trial, lead scoring, attribution, referral, consent configurable.
- [x] Secret provider tetap di environment, tidak di database.
- [x] Dashboard tidak pernah menampilkan secret penuh.

## 29.03 Permission

- [x] Izin `platform.pemasaran.*` sesuai `MARKETING.md` bagian 26.
- [x] Izin ekspor terpisah dari izin lihat.
- [x] Seeder izin diperbarui.
- [x] Hanya role platform yang memperoleh izin ini.

## 29.04 Feature Flag

- [x] Mekanisme feature flag platform. Belum ada di FASE 00–28 dan belum pernah dibuat.
- [x] Flag `marketing.*` sesuai `MARKETING.md` bagian 31.
- [x] Flag mati berarti menu dan rutenya tidak dapat diakses, bukan sekadar disembunyikan.

## 29.05 Navigasi Dashboard Platform

- [x] Menu `Growth & Marketing` sesuai `MARKETING.md` bagian 4.
- [x] Hanya pada host dashboard.
- [x] Submenu `Pengaturan → Domain` menampilkan host aktif secara baca-saja.

## 29.06 Audit

- [x] Action sensitif `MARKETING.md` bagian 27 tercatat di audit yang sudah ada.
- [x] Tidak membuat tabel audit kedua.

### Gate 29

Menu Growth & Marketing hanya dapat diakses role platform berizin; mematikan flag `marketing.*` menutup rutenya, bukan hanya menyembunyikan menunya. (Terpenuhi)

Izin platform disimpan sebagai daftar kode pada baris AdminPlatform, bukan
sebagai tabel peran tersendiri: admin platform berjumlah sedikit dan tidak punya
hierarki unit seperti pengguna tenant, sehingga meniru RBAC tenant di sini hanya
akan menduplikasi IAM. `SuperAdmin` dipertahankan supaya platform tidak pernah
dapat mengunci dirinya sendiri di luar konsolnya.

Flag disemai dalam keadaan mati, dan kode yang tidak dikenal katalog juga
dianggap mati — salah ketik pada gerbang rute menutup halaman, bukan
membukanya.

---

# FASE 30 — Pengunjung, UTM, dan Attribution

## 30.01 Sesi Pengunjung

- [x] Tabel `SesiPengunjung`.
- [x] Cookie berdomain induk.
- [x] Pengunjung anonim tidak pernah membaca sesi organisasi.

## 30.02 Event Pemasaran

- [x] Tabel `EventPemasaran`.
- [x] Event publik sesuai `MARKETING.md` bagian 23.
- [x] Collector menolak event di luar taxonomy.

## 30.03 UTM

- [x] Tabel `UtmPemasaran`.
- [x] Capture `utm_*`, referrer, landing URL, first page, session ID, device.

## 30.04 Attribution

- [x] Tabel `AttributionPemasaran`.
- [x] First touch dan last touch.
- [x] First touch ditetapkan di host publik dan tidak dapat ditimpa host dashboard.
- [x] Last touch dapat diperbarui.
- [x] Kunjungan langsung ke host dashboard tanpa riwayat publik dicatat `direct`.

## 30.05 Identity Merge

- [x] Pengunjung anonim di-merge setelah form submit, login, atau trial register.
- [x] Merge tidak dilakukan berdasarkan sinyal lemah.
- [x] UTM boleh diteruskan sekali lewat parameter CTA lintas host, lalu segera dipindah ke cookie.

## 30.06 Kampanye

- [x] Tabel `Kampanye`, `KampanyeChannel`.
- [x] Field, status, channel, dan objective sesuai `MARKETING.md` bagian 13.
- [x] Event dan attribution menunjuk kampanye, bukan sekadar string `utm_campaign`.
- [x] Biaya, audience, dan target kampanye menyusul di FASE 38; tanpa biaya, CAC per channel belum dapat dihitung.

## 30.07 Job

- [x] Job `HitungAttribution` di database queue.
- [x] Job `SinkronkanStatusProvider` dipindah ke FASE 34 bersama penyedia emailnya; tanpa penyedia, kelasnya hanya kerangka kosong.

## 30.08 Test

- [x] `UtmTersimpanTest`.
- [x] `AttributionPertamaTerjagaTest`.
- [x] `AttributionTerakhirDiperbaruiTest`.
- [x] `AttributionLintasHostTest`.
- [x] `HitungAttributionTest`.

### Gate 30

Pengunjung yang datang dari kampanye di host publik lalu mendaftar trial di host dashboard tetap membawa first touch aslinya. (Terpenuhi)

Attribution ditulis saat kunjungan terjadi, dan tiga aturannya ditegakkan di
satu tempat supaya tidak ada pemanggil yang dapat melewatinya: first touch
ditulis sekali saja, last touch selalu diperbarui, dan kedatangan tanpa riwayat
dicatat `direct` alih-alih dibiarkan kosong.

Penyeberangan identitas antar host memakai cookie berdomain induk bila kedua
host memang berbagi induk. Bila tidak — pengembangan lokal, staging terpisah —
pengenalnya dititipkan sekali lewat parameter tautan, dan titipan itu hanya
diterima ketika cookie belum ada, sehingga pengenal orang lain tidak dapat
ditempelkan kepada pengunjung yang riwayatnya sudah terbentuk.

Test bagian 36 yang dipenuhi di sini: `UtmTersimpanTest`,
`AttributionPertamaTerjagaTest`, `AttributionTerakhirDiperbaruiTest`,
`AttributionLintasHostTest`, dan `HitungAttributionTest`.

---

# FASE 31 — Prospek dan CRM

## 31.01 Prospek

- [x] Tabel `Prospek`, `KontakProspek`, `OrganisasiProspek`.
- [x] Data lead minimal sesuai `MARKETING.md` 5.2.
- [x] Sumber lead sesuai `MARKETING.md` 5.1.

## 31.02 Pipeline

- [x] Tabel `TahapPipeline`, `RiwayatTahapProspek`.
- [x] State `BARU → DIHUBUNGI → TERLIBAT → DEMO → TRIAL → AKTIF → QUALIFIED → MENANG`.
- [x] State alternatif `TIDAK_COCOK`, `HILANG`, `UNSUBSCRIBE`.
- [x] Setiap perubahan tahap tercatat di timeline.

## 31.03 Skor Prospek

- [x] Tabel `SkorProspek` dan `AturanSkorProspek`.
- [x] Aturan configurable, angka tidak di-hard-code.
- [x] Job `HitungSkorProspek`.

## 31.04 Tag dan Aktivitas

- [x] Tabel `TagProspek`, `ProspekTag`, `AktivitasProspek`.
- [x] Timeline gabungan sesuai `MARKETING.md` bagian 7.
- [x] Sumber event: website, aplikasi, email, billing, subscription, referral, automation.

## 31.05 Masuk dan Keluar

- [x] Import CSV.
- [x] Lead capture lewat API dan webhook.
- [x] Ekspor dengan izin terpisah dan tercatat di audit.
- [x] Rate limit ekspor.

## 31.06 Test

- [x] `ProspekDibuatTest`.
- [x] `PipelineProspekTest`.
- [x] `SkorProspekTest`.
- [x] `TransisiStatusProspekTest`.
- [x] `HitungSkorProspekTest`.
- [x] `PermissionPemasaranTest`.

### Gate 31

Lead dari seluruh sumber masuk ke satu pipeline dengan timeline yang utuh; ekspor lead tidak dapat dilakukan tanpa izin ekspor dan selalu meninggalkan jejak audit. (Terpenuhi)

Satu pintu untuk formulir publik, impor CSV, API, webhook, dan entri manual,
karena tiga hal harus terjadi pada setiap prospek baru dan tidak boleh
bergantung pada siapa yang membuatnya: penggabungan dengan prospek yang sudah
ada, pengambilan kampanye dari first touch, dan penempatan pada tahap awal.

Penggabungan hanya memakai sinyal kuat — alamat email yang sama atau pengenal
pengunjung yang sama — sesuai larangan MARKETING.md 14. Menggabungkan dua orang
berbeda jauh lebih mahal daripada menyimpan satu prospek ganda.

Bobot skor sempat hidup sebagai satu objek JSON di `KonfigurasiPemasaran`.
Bentuk itu memenuhi tuntutan bagian 5.4 di atas kertas, tetapi membawa
kegagalan yang tidak bergejala: kunci yang salah ketik tersimpan tanpa keluhan
lalu diabaikan diam-diam oleh penghitungnya. Domain ini sudah menolak kegagalan
sejenis di tempat lain — `PerekamEventPemasaran` menolak jenis peristiwa asing —
jadi aturannya dipindahkan ke tabel `AturanSkorProspek` yang kode sinyalnya
divalidasi saat disimpan. Migrasinya membawa serta bobot yang sudah disetel
operator dan membuang kunci yang tidak dikenal.

Perpindahan itu memunculkan satu hal yang selama ini tidak terlihat: bobot
bawaan `EmailBounce` (-10) tidak pernah berlaku, karena belum ada yang
menghasilkan sinyal itu sampai domain email lahir di FASE 34. Sekarang
`KatalogPeristiwaSkor` menyatakan asal tiap sinyal — dari peristiwa, turunan
keadaan prospek, atau tertunda — dan konsol menandai aturan yang belum berlaku
alih-alih membiarkannya tampak bekerja.

Rincian `SkorProspek` menunjuk aturan yang menghasilkannya, sehingga pertanyaan
"kenapa angkanya segini" dapat dijawab sampai ke barisnya. Penunjuknya boleh
kosong: aturan yang dihapus tidak menghapus penjelasan skor yang terlanjur
dihitung darinya.

Skor disusun ulang dari nol setiap dihitung, bukan diakumulasi: skor yang
ditambahkan akan ikut menyimpan setiap kesalahan sebelumnya dan tidak pernah
dapat dikoreksi. Rinciannya disimpan per peristiwa supaya angkanya dapat
dijelaskan; skor yang tidak dapat dijelaskan akan diabaikan tim penjualan.

Test bagian 36 yang dipenuhi di sini: `ProspekDibuatTest`, `PipelineProspekTest`,
`SkorProspekTest`, `TransisiStatusProspekTest`, dan `PermissionPemasaranTest`
(gerbang izin konsol diuji di `FondasiPemasaranTest` dan pada tiap rute CRM),
ditambah `AturanSkorProspekTest` untuk tabel aturannya.

---

# FASE 32 — Halaman Publik dan Formulir

## 32.01 Halaman Pemasaran

- [x] Tabel `HalamanPemasaran`, `VersiHalamanPemasaran`, `BlokHalamanPemasaran`.
- [x] Tipe dan blok sesuai `MARKETING.md` bagian 8.
- [x] Status `DRAF → REVIEW → TERJADWAL → TERBIT → DIARSIPKAN`.

Isi halaman tidak disimpan pada barisnya sendiri melainkan pada versi;
`HalamanPemasaran` hanya menunjuk versi mana yang terbit dan mana yang sedang
disunting. Bentuk itu membuat revision history, rollback, dan terbit terjadwal
menjadi satu mekanisme yang sama. Versi dan bloknya hanya-tambah.

`Terbit → Terbit` diizinkan dalam peta transisi: menerbitkan draf baru di atas
halaman yang sedang tayang adalah operasi yang paling sering terjadi, dan
memaksanya turun ke draf lebih dulu hanya melahirkan jalur kedua yang harus
sama-sama diingat.

## 32.02 Penerbitan

- [x] Terbit dan tarik terjadwal.
- [x] Revision history dan versioning.
- [x] Rollback.
- [x] Publikasi tercatat di audit.
- [x] Pratinjau draf memakai URL bertanda tangan pada host publik dan wajib `noindex`.

Rollback menyalin isi versi lama menjadi versi baru, bukan menunjuk balik:
nomor versi selalu maju dan riwayatnya terbaca lurus. Penjadwalnya
(`pemasaran:jalankan-jadwal-halaman`, tiap lima menit) membandingkan waktu yang
sudah lewat, bukan waktu yang persis sekarang, sehingga jadwal yang terlewat
tetap dijalankan alih-alih hilang.

Isi halaman terbit di-cache, bukan respons HTTP-nya — itu jawaban atas
penundaan dari FASE 24.5: badan respons Inertia memuat token CSRF milik satu
sesi, sedangkan isi halaman tidak memuat apa pun yang mengikat ke seseorang.

## 32.03 Redirect

- [x] Tabel `RedirectPemasaran`.
- [x] Dukungan 301, 302, 410.
- [x] Hanya berlaku pada host publik.

Middleware-nya dipasang pada grup host publik saja, dan berjalan setelah
pengenal pengunjung ditetapkan supaya cookienya tetap terkirim bersama respons
pengalihan.

## 32.04 Formulir

- [x] Tabel `FormulirPemasaran`, `FieldFormulirPemasaran`, `PengirimanFormulir`.
- [x] Field sesuai `MARKETING.md` bagian 10, termasuk hidden UTM dan consent.
- [x] Config: success message, redirect, source, campaign, tags, trigger otomasi, webhook.

Jawaban mentah disimpan apa adanya pada `PengirimanFormulir` (hanya-tambah),
lalu diterjemahkan menjadi prospek lewat `CatatProspek`. Field UTM tersembunyi
diisi server dari sesi kunjungan; nilai UTM yang datang dari browser diabaikan.

Pemicu otomasi dan webhook baru tersimpan sebagai konfigurasi. Yang
menjalankannya adalah mesin otomasi di FASE 35.

## 32.05 Anti-spam

- [x] Honeypot.
- [x] Rate limit.
- [x] CAPTCHA opsional.

Honeypot dijawab persis seperti pengiriman yang berhasil. Rate limit `formulir`
5/menit per IP. Verifikasi CAPTCHA agnostik penyedia dan gagal tertutup: tanpa
kunci yang terpasang, atau saat penyedianya tidak dapat dihubungi, pengirimannya
ditolak. Widget yang disertakan adalah Turnstile.

## 32.06 Frontend Publik

- [x] `resources/js/features/Publik` sebagai satu-satunya feature pada host publik.
- [x] Inertia + React dengan build Vite yang sama.
- [x] Mengikuti `DESIGN.md`.
- [x] Rute publik sesuai `MARKETING.md` 34.1 sebatas yang dicakup MVP.
- [x] `/trial` adalah halaman penjelasan; formulir pendaftaran berada di host dashboard.

Alamat halaman ditentukan data, bukan kode: satu rute penampung melayani seluruh
slug, sehingga rute `MARKETING.md` 34.1 dibuat dari konsol tanpa deploy. Host
publik tidak punya rute autentikasi sama sekali, jadi `/trial` tidak mungkin
memuat formulir pendaftaran — tombolnya menyeberang ke host dashboard.

## 32.07 Test

- [x] `FormulirPemasaranTest`.
- [x] Draf tidak dapat diakses tanpa tanda tangan dan tidak terindeks.

### Gate 32

Landing page dapat dibuat, diterbitkan, dan dikembalikan dari dashboard tanpa deploy, dan formulir publiknya menghasilkan lead lengkap dengan UTM.

**Terpenuhi.** Dibuktikan `HalamanPemasaranTest` (draf tak terlihat, terbit
dilayani, sunting tidak mengubah yang tayang, rollback mengembalikan isi lama),
`FormulirPemasaranTest` (pengiriman menjadi prospek lengkap dengan UTM dari
kunjungan), `PratinjauDrafTest`, `RedirectPemasaranTest`,
`KonsolHalamanPemasaranTest`, dan `KonsolFormulirDanRedirectTest`.

Catatan jujur:

- CAPTCHA diverifikasi agnostik penyedia, tetapi widget yang disertakan di
  frontend hanya Turnstile. Penyedia lain perlu widgetnya dipasang sendiri.
- Isi blok disunting sebagai JSON di konsol. Formulir khusus per jenis blok
  baru sepadan setelah bentuk tiap blok mengendap.
- CMS konten dan keyword manager (`MARKETING.md` 9) belum dikerjakan; yang masuk
  FASE 32 hanyalah bagian redirect dan metadata SEO per halaman.

---

# FASE 33 — Trial Event dan Aktivasi

## 33.01 Konfigurasi Trial

- [x] Durasi, paket, kebutuhan kartu, batas user/lokasi/aset, grace period, kebijakan perpanjangan.
- [x] Dibaca dari domain Langganan, tidak diduplikasi.
- [x] Perubahan konfigurasi tercatat di audit.

`PembacaKonfigurasiTrial` merakit setelan dari domain Langganan: durasi dari
`langganan.hari_uji_coba`, paket dan batas dari `PaketFitur`/`KatalogFitur`,
masa tenggang dari `hari_tenggang`. Yang tinggal di Pemasaran hanyalah yang
memang miliknya — kebutuhan kartu dan extension policy.

Kunci `trial.hari` dihapus dari `KonfigurasiPemasaran`. Ia menyalin
`langganan.hari_uji_coba`, dan dua sumber untuk angka yang sama adalah dua
angka yang cepat atau lambat akan berbeda. `KatalogFitur::BATAS_LOKASI`
ditambahkan supaya batas lokasi punya tempat yang sama dengan batas aset dan
pengguna, bukan tempat baru di Pemasaran.

## 33.02 State Trial

- [x] State `TERDAFTAR → SETUP → AKTIF → TERAKTIVASI → KONVERSI`.
- [x] State alternatif `KADALUARSA`, `DIBATALKAN`, `DIPERPANJANG`.
- [x] Mutasi langganan lewat application service, bukan langsung ke Billing.

Konversi dapat datang dari keadaan hidup mana pun, termasuk `SETUP`: orang yang
mendaftar lalu langsung membayar tanpa menyentuh checklist tetap pelanggan, dan
transisi yang menolaknya akan menolak uang.

Perpanjangan memanggil `KelolaLangganan::perpanjangUjiCoba()`, metode baru yang
dipisah dari `perpanjang()` — yang itu justru mengakhiri uji coba karena
periodenya sudah dibayar.

## 33.03 Activation Checklist

- [x] Organisasi, lokasi, aset pertama, undangan pengguna, perintah kerja pertama, preventive pertama.
- [x] Event trial sesuai `MARKETING.md` bagian 23.

Checklist tidak pernah dicentang manusia. Observer `PerekamAktivasiTrial`
mendengar pembuatan Lokasi, Aset, Pengguna, PerintahKerja, dan
RencanaPemeliharaan — itulah yang membedakan aktivasi dari sekadar mendaftar.
Pencatatannya idempoten: aset keseratus tidak mencentang ulang "aset pertama".

## 33.04 Prospek ke Organisasi

- [x] Trial yang menghasilkan workspace menautkan `Prospek → Organisasi`.
- [x] Referensi attribution tetap dipertahankan.

`EventPemasaran` mendapat kolom `OrganisasiId`. Peristiwa di dalam aplikasi
tidak punya pengenal pengunjung, hanya organisasi; tanpa kolom itu perjalanan
satu calon pelanggan putus tepat pada saat ia menjadi tenant. Timeline prospek
kini membaca kedua kunci sekaligus.

## 33.05 Event Revenue

- [x] Event revenue sesuai `MARKETING.md` bagian 23 dari domain Langganan.
- [x] Event masuk timeline prospek.

Langganan menyiarkan `PeristiwaLangganan` dan tidak tahu ada yang
mendengarkan; Pemasaran yang memasang pendengarnya. Arah ketergantungannya
satu arah, sehingga domain penagihan tidak pernah bergantung pada modul
pemasaran.

## 33.06 Test

- [x] `TrialActivationTest`.
- [x] Konversi trial terhubung ke Langganan.

## 33.07 Pendaftaran Trial Mandiri

- [x] Formulir pendaftaran di host dashboard, bukan host publik.
- [x] Satu transaksi menghasilkan organisasi, pemilik, langganan uji coba, dan trial.
- [x] Pengenal kunjungan dibawa dari cookie berdomain induk, attribution tidak putus.
- [x] Kebutuhan kartu (`trial.kartu_diperlukan`) benar-benar ditegakkan.

`DaftarkanTrial` menulis tanpa konteks tenant mana pun — ia satu-satunya tempat
yang memang harus membuat baris untuk organisasi yang belum ada saat
permintaannya dimulai, dan `ScopeOrganisasi` menolak yang sebaliknya. Pemilik
workspace baru memegang seluruh izin karena tidak ada orang lain yang dapat
memberinya.

Kebijakan kartu ditegakkan lewat antarmuka `MenerimaKartuDiMuka`, terpisah dari
`PenyediaPembayaran` karena tidak semua penyedia bisa: transfer manual tidak
punya kartu untuk disimpan. Gagal tertutup dua kali — kebijakan yang menyala
sementara penyedianya tidak mendukung menutup pendaftaran mandiri seluruhnya,
dan formulirnya menyatakan alasannya alih-alih menerima lalu mengabaikan.

### Gate 33

Perjalanan satu pengunjung dari kunjungan pertama sampai berlangganan terbaca utuh dalam satu timeline, dan revenue-nya tertaut ke channel asalnya.

**Terpenuhi.** `KonversiTrialTest::test_perjalanan_terbaca_utuh_dalam_satu_timeline`
menelusuri satu orang dari formulir anonim, lewat `TrialDimulai`,
`AsetPertamaDibuat`, dan `TrialTeraktivasi`, sampai `PembayaranBerhasil` — semua
dalam satu timeline prospek, dengan attribution kunjungan pertamanya utuh.
`AktivasiDariModelNyataTest` membuktikan checklist terisi dari pembuatan Lokasi,
Aset, Pengguna, PerintahKerja, dan RencanaPemeliharaan yang sungguhan, sehingga
observer yang lupa didaftarkan tidak akan lolos.

Satu bug yang ditemukan test model nyata: setelah `TERAKTIVASI`, butir checklist
yang tersisa berhenti dicatat. Corong onboarding tetap perlu tahu apakah
preventive akhirnya dibuat, dan trial yang teraktivasi tetap dapat kedaluwarsa,
jadi `berjalan()` kini berarti "belum berakhir", bukan "belum teraktivasi".

---

# FASE 34 — Consent dan Email Pemasaran

## 34.01 Consent

- [x] Simpan consent, timestamp, sumber, versi kebijakan.
- [x] Unsubscribe.
- [x] Suppression list.
- [x] Permintaan penghapusan/anonimisasi.

Consent hanya-tambah: pencabutan adalah baris baru yang menyatakan
`Diberikan = false`, sehingga riwayatnya tetap terbaca sebagai bukti lengkap
dengan versi kebijakan yang berlaku saat itu.

Daftar supresi dikunci pada `EmailHash`, bukan alamatnya. Itulah yang membuat
penghapusan data benar-benar penghapusan: alamatnya dibuang dari prospek,
konsen, kiriman, dan bahkan dari baris supresinya sendiri, sementara sidiknya
tetap menolak orang yang sama masuk kembali lewat formulir. Tanpa itu,
"penghapusan" harus memilih antara menyimpan alamat yang diminta hilang atau
mengirimi surat lagi orang yang sudah meminta berhenti.

Anonimisasi menyisakan baris prospeknya tanpa identitas sehingga corong dan
attribution tetap terbaca jujur; penghapusan membuang barisnya.

## 34.02 Template dan Sequence

- [x] Tabel `TemplateEmailPemasaran`, `SequenceEmailPemasaran`, `LangkahSequenceEmail`.
- [x] Variabel sesuai `MARKETING.md` bagian 15.
- [x] Sequence trial configurable, bukan hard-code.

Variabel yang salah ketik ditolak saat menyimpan, bukan dibiarkan lolos. Saat
kirim, `{{NamaDepan}}` hanya lenyap tanpa suara, dan surat yang menyapa
"Halo ," baru ketahuan setelah ribuan orang membacanya.

Sequence onboarding trial ditunjuk lewat setelan `email.sequence_trial`.
Tanpa setelan, tanpa consent, atau tanpa sequence aktif, trial tetap berjalan:
email pemasaran bukan syarat orang boleh mencoba produknya.

Langkah tidak dapat disunting selagi ada pendaftaran berjalan. Kiriman
dijadwalkan di muka, jadi mengubah hari atau template setelah itu tidak lagi
mengubah apa pun yang sudah terjadwal — hanya membuat konsol berbohong tentang
apa yang akan orang terima.

## 34.03 Pengiriman

- [x] Tabel `PengirimanEmailPemasaran`.
- [x] Status `TERJADWAL`, `DIKIRIM`, `TERKIRIM`, `DIBUKA`, `DIKLIK`, `BOUNCE`, `GAGAL`, `UNSUBSCRIBE`.
- [x] Kontrak `PenyediaEmailPemasaran`.
- [x] Job `KirimEmailPemasaran` idempoten; retry tidak menghasilkan kiriman ganda.
- [x] Job `SinkronkanStatusProvider` untuk status kiriman yang datang belakangan (dipindah dari FASE 30).
- [x] Rate limit pengiriman.

Idempotensi bersandar pada kunci unik di basis data, bukan pada pemeriksaan
sebelum menulis: seluruh langkah dijadwalkan di muka dengan
`sequence:{pendaftaran}:langkah:{langkah}`, sehingga penjadwal yang berjalan
dua kali menabrak indeks alih-alih melahirkan kiriman kedua.

Status hanya boleh maju. Laporan "terkirim" yang tiba setelah "diklik" tidak
memundurkan apa pun, karena penyedia tidak menjanjikan urutan.

Cap harian dihitung dari yang benar-benar berangkat hari ini, bukan dari yang
diantrekan; antrean yang gagal tidak memakan jatah kiriman orang lain.

## 34.04 Penegakan

- [x] Unsubscribe menghentikan seluruh pesan pemasaran.
- [x] Suppression list dihormati sebelum pengiriman.
- [x] Consent diperiksa di domain, bukan hanya di UI.

Consent diperiksa lagi pada saat kirim, bukan hanya saat dijadwalkan. Jeda
antara keduanya bisa berhari-hari, dan justru di sanalah orang menekan tombol
berhenti langganan.

Tautan berhenti langganan bertanda tangan dan tanpa kedaluwarsa: email lama
tetap harus dapat dipakai bertahun kemudian, sedangkan tautan yang dapat
ditebak memungkinkan siapa saja mencabut langganan orang lain. Satu klik,
tanpa masuk, tanpa konfirmasi — meminta orang membuktikan dirinya sebelum
boleh berhenti dikirimi surat adalah cara memperlambat pencabutan, bukan cara
mengamankannya.

Bounce keras masuk daftar supresi sendiri: alamat yang memantul akan memantul
lagi, dan reputasi pengirimlah yang membayarnya.

## 34.05 Test

- [x] `SequenceEmailTest`.
- [x] `SuppressionListTest`.

### Gate 34

Menjalankan ulang pengiriman yang gagal tidak menghasilkan email ganda, dan penerima yang unsubscribe tidak pernah menerima pesan pemasaran berikutnya.

**Terpenuhi.** `SequenceEmailTest::test_menjalankan_ulang_pekerjaan_tidak_mengirim_dua_kali`
menjalankan job yang sama dua kali dan penyedia hanya menerima satu pesan;
`test_kegagalan_penyedia_menyisakan_kiriman_untuk_dicoba_lagi` membuktikan
paruh yang lebih sulit — kiriman yang gagal tetap `Terjadwal`, dicoba lagi,
lalu berangkat tepat sekali.
`SuppressionListTest::test_pesan_berikutnya_tidak_pernah_berangkat_setelah_unsubscribe`
menempuh jalurnya utuh: satu email berangkat, penerimanya mengklik tautan di
dalamnya, waktu dimajukan melewati jadwal dua langkah berikutnya, dan tidak
satu pun berangkat.

Kedua paruh Gate diuji lewat sabotase: mematikan pemeriksaan consent saat
kirim membuat ketiga email berangkat; mematikan tanda tangan URL membuat
tautan tebakan dapat mencabut langganan orang lain; mengabaikan daftar supresi
menghidupkan kembali alamat yang sudah dihapus datanya.

Satu hal yang ditemukan saat menulisnya: supresi harus berdiri sendiri, tidak
boleh menumpang pada konsen negatif. Setelah bounce, consent orang itu masih
positif — hanya daftar supresi yang menghentikannya.

---

# FASE 35 — Otomasi Pemasaran

## 35.01 Struktur

- [x] Tabel `OtomasiPemasaran`, `VersiOtomasiPemasaran`, `LangkahOtomasiPemasaran`.
- [x] Bentuk `Trigger → Condition → Delay → Action`.

Pemicu melekat pada otomasinya, sedangkan langkah-langkahnya milik satu versi.
Versi dikunci ke eksekusi saat eksekusi itu lahir, sehingga menyunting otomasi
tidak mengubah apa yang sedang berjalan di tengah jalan. Versi aktif tidak dapat
disunting sama sekali; perubahan selalu lewat draf baru yang boleh menyalin
langkah versi aktifnya.

## 35.02 Trigger dan Condition

- [x] Trigger sesuai `MARKETING.md` bagian 17.
- [x] Condition sesuai `MARKETING.md` bagian 17, termasuk consent.

`KatalogPemicuOtomasi` memetakan tiap pemicu ke kode `EventPemasaran` yang
menyalakannya. Pemicu yang belum ada sumbernya — `ReferralTerdaftar` menunggu
FASE 36, `LeadTidakAktif` menunggu pekerjaan terjadwalnya — tetap boleh dipilih,
tetapi konsol menyatakan terus terang bahwa otomasi itu tidak akan pernah
berjalan. Perlakuannya sama seperti aturan skor di FASE 31: daftar tertutup yang
jujur soal apa yang belum berlaku, bukan daftar yang diam-diam tidak menyala.

Tiga peristiwa dilahirkan supaya pemicunya bukan janji kosong: `ProspekDibuat`
dari `CatatProspek`, `TrialBerakhir` dari `KedaluwarsakanTrial`, dan
`TrialAkanBerakhir` dari pekerjaan harian yang memeriksa peristiwa sebelumnya
agar satu trial tidak diperingatkan setiap pagi sampai masanya habis.

Kondisi membaca dari satu tempat, `PembacaBidangKondisi`, dan nilai yang tidak
diketahui tidak pernah cocok: otomasi lebih baik diam daripada salah sasaran.
Bidang angka dibandingkan sebagai angka, bukan sebagai teks — kalau tidak, skor
9 akan terbaca lebih besar dari 10.

## 35.03 Action

- [x] Action dasar: kirim email, tambah tag, update skor, update status, enroll/remove sequence, notifikasi internal, webhook.
- [x] Action yang menyentuh Langganan lewat domain contract.

Sembilan aksi di balik kontrak `TindakanOtomasi` dan satu registri tertutup;
kode yang tidak terdaftar ditolak saat langkahnya disimpan, bukan saat
peristiwanya sudah lewat dan tidak dapat diulang. Konfigurasi tiap aksi
divalidasi dengan aturan yang aksinya sendiri umumkan.

Perpanjangan trial lewat aksi domain `PerpanjangTrial`, yang memegang batas
kebijakan dan memanggil `KelolaLangganan`. Otomasi tidak diberi jalur pintas ke
tabel langganan, sebab batas perpanjangan justru ada untuk menahan pemberian
otomatis.

Webhook memakai tanda tangan HMAC yang sama dengan FASE 19, dengan rahasia dari
environment, bukan dari konfigurasi langkah — konfigurasi langkah tersimpan
sebagai JSON biasa dan terbaca siapa pun yang membuka konsol.

## 35.04 Eksekusi

- [x] Tabel `EksekusiOtomasiPemasaran`, `LogEksekusiOtomasi`.
- [x] Setiap eksekusi idempoten.
- [x] Execution cap, daily message cap, retry cap.
- [x] DLQ memakai mekanisme FASE 19.
- [x] Job `ProsesOtomasiPemasaran` di database queue.

Idempotensinya berlapis dua, dan keduanya diperlukan. Lapis pertama adalah
kunci unik `otomasi:{versi}:event:{peristiwa}`, yang menahan peristiwa yang sama
melahirkan eksekusi kedua sekalipun dua pekerja berjalan bersamaan. Lapis kedua
adalah log per langkah: langkah yang sudah punya baris sukses dilewati, sehingga
pekerja yang mati setelah aksinya berjalan tetapi sebelum kemajuannya tersimpan
tidak mengulang aksi itu saat dicoba lagi.

DLQ mengikuti bentuk FASE 19: percobaan dihitung di baris eksekusinya, mundur
makin lama tiap kali gagal, lalu berhenti permanen di `GagalPermanen` yang tetap
terbaca di konsol lengkap dengan galat dan jejak langkahnya.

Cap eksekusi membatasi berapa yang diantrekan sekali jalan, cap percobaan
menentukan kapan menyerah, dan cap pesan harian tetap dipegang pengirim email
FASE 34 — otomasi tidak diberi jalan memutarinya.

## 35.05 Test

- [x] `AutomationTriggerTest`.
- [x] `AutomationIdempotencyTest`.
- [x] `EvaluasiKondisiOtomasiTest`.

### Gate 35

Otomasi yang dijalankan dua kali atas peristiwa yang sama tidak menghasilkan aksi ganda, dan kegagalan penyedia berhenti di DLQ tanpa mengulang tanpa batas.

**Terpenuhi.** `AutomationIdempotencyTest::test_menjalankan_pekerjaan_dua_kali_tidak_menggandakan_aksinya`
menjalankan job yang sama dua kali dan hanya satu email yang terjadwal;
`test_kegagalan_berulang_berhenti_di_dlq` membiarkan webhook gagal sampai cap
percobaan habis, lalu membuktikan barisnya berhenti di `GagalPermanen` dan
dijalankan lagi tidak menambah panggilan keempat.

Satu lubang ditemukan justru lewat sabotase, bukan lewat test. Mematikan
pelewatan langkah berbasis log tidak menggagalkan satu test pun, karena penanda
`LangkahBerikutnya` sudah menutupi kasus yang diuji. Yang tidak tertutup adalah
pekerja yang mati di antara aksinya berjalan dan kemajuannya tersimpan — persis
kasus yang membuat lapis kedua itu ada.
`test_aksi_tidak_terulang_walau_penanda_kemajuannya_hilang` kini memerankannya
dengan aksi yang sengaja tidak punya kunci uniknya sendiri, sehingga
pengulangan benar-benar akan terlihat.

Enam sabotase lain menggigit: kunci idempotensi yang memakai waktu alih-alih
peristiwanya melahirkan eksekusi kembar; cap percobaan yang diabaikan mengulang
tanpa batas; nilai kosong yang dianggap cocok membuat kondisi menyasar orang
yang datanya tidak diketahui; kondisi gagal yang tidak menghentikan eksekusi
mengirim email yang seharusnya tidak berangkat; dan validasi langkah yang
dimatikan meloloskan bidang serta operator yang tidak ada.

---

# FASE 36 — Referral Dasar

## 36.01 Program

- [x] Tabel `ProgramReferral`, `Referral`, `RewardReferral`.
- [x] Referral code dan referral URL.

Ada tabel keempat yang tidak diminta, `KodeReferral`, dan itu disengaja. Satu
pelanggan punya satu kode yang dipakai berkali-kali, sedangkan satu baris
`Referral` adalah perjalanan satu orang yang diajak. Menggabungkan keduanya
memaksa baris tanpa pengunjung hidup di tabel yang setiap barisnya seharusnya
punya satu, dan membuat state machine-nya berbohong tentang apa yang ia lacak.

URL-nya `/r/{kode}` di host publik. Kode yang tidak dikenal tetap dialihkan ke
beranda tanpa berkata apa-apa: memberi tahu bahwa satu kode tidak ada mengubah
tautan itu menjadi alat menebak kode orang lain.

## 36.02 State

- [x] State `DIBUAT → DIKLIK → LEAD → TRIAL → PAID → REWARD_PENDING → REWARDED`.
- [x] Anti self-referral.

Status hanya boleh maju, dan pengunjung yang sama mengklik dua kali tidak
melahirkan referral kedua — indeks unik pada (program, pengunjung) yang
menahannya.

Anti self-referral diperiksa dua kali: sekali saat kliknya masuk, dan sekali
lagi sebelum imbalannya diberikan. Sekali saja tidak cukup, sebab pada klik
pertama pengunjungnya masih anonim; siapa dia baru terbaca setelah ia mengisi
formulir atau mendaftar. Yang ditolak: organisasi yang sama dengan perujuknya,
prospek yang sudah menjadi bagian organisasi perujuk, dan alamat email yang
terdaftar sebagai pengguna organisasi perujuk.

## 36.03 Reward

- [x] Reward configurable: extension, credit, coupon, custom.
- [x] Pemberian reward lewat domain contract Langganan, bukan mutasi Billing langsung.
- [x] Perubahan reward tercatat di audit.
- [x] Job `ProsesRewardReferral`.

Keempat bentuk dapat dipilih, tetapi hanya dua yang benar-benar dapat
diselesaikan hari ini: `Perpanjangan`, karena Billing punya tempatnya, dan
`Kustom`, karena memang diselesaikan manusia di luar sistem. `Kredit` dan
`Kupon` tidak punya buku besar maupun tabel kupon di domain Langganan, jadi
`PemberiImbalanLangganan::mendukung()` menjawab tidak dan program yang
menjanjikannya ditolak saat disimpan. Janji imbalan yang tidak dapat ditepati
terbaca pelanggan hari ini sementara gagalnya baru terlihat berminggu kemudian
di antrean imbalan — itu lebih buruk daripada tidak menawarkannya.

Pemberian lewat kontrak `PemberiImbalanLangganan`, tidak pernah dengan menulis
ke tabel Billing dari domain Pemasaran. Satu referral paling banyak satu
imbalan, dijaga indeks unik pada `ReferralId`.

## 36.04 Test

- [x] `ReferralConversionTest`.
- [x] `HitungRewardReferralTest`.

### Gate 36

Referral dapat ditelusuri dari klik sampai pembayaran, dan seseorang tidak dapat memberi referral kepada dirinya sendiri.

**Terpenuhi.** `ReferralConversionTest::test_referral_tertelusur_dari_klik_sampai_pembayaran`
menempuh jalurnya utuh lewat jalur sungguhan — klik tautan, formulir prospek,
`MulaiTrial`, lalu pembayaran yang masuk lewat `CatatPembayaranLangganan` —
dan memeriksa keempat stempel waktunya terisi serta imbalannya terbit atas nama
perujuk.
`test_organisasi_tidak_dapat_mereferensikan_dirinya_sendiri` dan
`test_email_pengguna_perujuk_ditolak_saat_menjadi_lead` menjaga paruh keduanya,
dan `test_referral_yang_ditolak_tidak_pernah_menghasilkan_imbalan` membuktikan
penolakan itu benar-benar memutus rantainya sampai ke imbalan.

Lima sabotase menggigit: mematikan anti self-referral meloloskan tiga test
sekaligus; membiarkan status mundur merusak urutan perjalanannya; menerima
semua bentuk imbalan membuat program kredit lolos tersimpan; menerbitkan
imbalan untuk referral yang belum dibayar melanggar syaratnya; dan mengabaikan
status final memberi imbalan dua kali.

Sabotase terakhir itu awalnya tidak menggigit. Penjaga "sudah final" di
layanannya terlindungi oleh penjaga serupa di job-nya, sehingga tidak pernah
teruji — padahal konsol punya tombol coba lagi yang memanggil layanannya
langsung. Test idempotensinya kini memanggil kedua jalur.

Satu bug ditemukan saat menulis test: memberi imbalan menyentuh langganan
tenant perujuk, sementara yang sedang berjalan adalah tenant yang baru saja
membayar. Barisan auditnya akan tercatat di buku tenant yang salah. Konteks
tenant kini dikosongkan selama pemberian, seperti yang sudah dilakukan
`DaftarkanTrial`.

Pemicu otomasi `ReferralTerdaftar` yang di FASE 35 masih dinyatakan belum ada
sumbernya kini menunjuk `ReferralMenjadiLead`, dan `AutomationTriggerTest`
berpindah menguji `LeadTidakAktif` yang memang masih menunggu pekerjaannya.

---

# FASE 37 — Dashboard Growth

## 37.01 KPI

- [x] KPI sesuai `MARKETING.md` bagian 5.
- [x] Definisi KPI terdokumentasi seperti `KatalogKpi` FASE 21, bukan rumus tersebar.

Sembilan belas KPI di `KatalogKpiPemasaran`, tiap satu membawa rumus dan nama
tabel sumbernya, dan rumus itu terbaca di layar pada tab Definisi KPI. Yang
menghitung membaca dari katalog yang sama, sehingga tidak ada rumus kedua yang
diam-diam berbeda.

Dua KPI dinyatakan belum tersedia beserta alasannya: `cac_per_channel` menunggu
`KampanyeBiaya` di FASE 38.08, dan `revenue_partner` menunggu program partner di
FASE 38.09. Keduanya tampil kosong, bukan nol — angka nol adalah pernyataan
bahwa tidak ada biaya dan tidak ada revenue partner, dan itu tidak benar.

## 37.02 Funnel

- [x] Funnel `Visitor → Lead → Demo → Trial → Activated → Qualified → Paid`.
- [x] Angka dapat ditelusuri ke sumber transaksinya.

Tiap tahap dihitung dari satu tabel yang disebutkan namanya di layar, bukan
diturunkan dari angka tahap sebelumnya. Funnel yang menurunkan angka dari angka
lain akan tetap terlihat rapi walau datanya sudah tidak cocok dengan transaksi.

## 37.03 Filter

- [x] Tanggal, channel, campaign, industri, landing page, device, paket, referral, partner.

Delapan penyaring bekerja; `partner` tersedia di bentuk filternya tetapi belum
menyaring apa pun sampai FASE 38.09 melahirkan datanya. Seluruhnya bermuara pada
satu subkueri pengunjung yang dipakai ulang tiap tahap, sehingga id pengunjung
tidak pernah ditarik ke PHP.

## 37.04 Alert

- [x] Alert platform sesuai `MARKETING.md` bagian 5.
- [ ] Memakai engine Notifikasi yang sudah ada.

Tujuh dari sembilan alert diperiksa; `whatsapp_gagal_kirim` dan
`komisi_partner_tertunda` menunggu FASE 38.01 dan 38.09, dan katalog menyebut
alasannya. Ambangnya dari setelan, bukan angka di kode, dan satu kode hanya
menghasilkan satu baris per hari.

Butir kedua sengaja tidak dicentang. Mesin Notifikasi memakai `MilikOrganisasi`
dan dialamatkan ke satu `Pengguna`; alert growth tidak punya keduanya. Satu-satunya
cara memakainya adalah melonggarkan scope tenant yang dipakai seluruh aplikasi
sejak FASE 02, dan itu harga yang terlalu mahal untuk satu daftar peringatan.
Alert platform karena itu disimpan di `AlertPemasaran` dan dibaca di dashboard.
Kanal notifikasi tingkat platform layak dibangun sendiri kelak.

## 37.05 Job Metrik

- [x] Job `HitungMetrikKampanye`.
- [x] Tidak ada N+1 pada halaman dashboard.

Enam kueri agregat untuk seluruh kampanye sekaligus, hasilnya ditulis ke
`MetrikKampanye`. Dashboard membaca baris jadi.
`DashboardGrowthTest::test_menambah_kampanye_tidak_menambah_kueri_halaman`
menghitung kueri halaman sebelum dan sesudah lima kampanye ditambahkan, dan
angkanya harus sama persis.

## 37.06 Test

- [x] `AuditPemasaranTest`.
- [x] Funnel terbukti konsisten dengan data transaksi.

### Gate 37 — Gate MVP Pemasaran

Seluruh acceptance criteria `MARKETING.md` bagian 35 terpenuhi. Founder dapat membuka satu dashboard dan menjawab channel mana menghasilkan customer, campaign mana menghasilkan revenue, dan landing page mana paling efektif.

**Terpenuhi.** `AuditPemasaranTest::test_funnel_konsisten_dengan_data_transaksi`
menghitung ulang tiap tahap langsung dari tabelnya dan membandingkannya dengan
yang dilaporkan funnel. Ketiga pertanyaan founder terjawab di satu layar:
channel dari Revenue per Channel, campaign dari tabel metrik kampanye, dan
landing page dari tabel konversi per halaman.

Sabotase menemukan satu test yang tidak menguji apa pun. Membuat tahap Activated
diam-diam menyalin angka Trial tidak menggagalkan satu test pun, karena data
semaiannya kebetulan bernilai sama di tiap tahap — persis kelemahan yang
dimaksud "angka dapat ditelusuri ke sumber transaksinya". Semaiannya kini memuat
satu trial yang berhenti sebelum aktivasi, sehingga tiap tahap bernilai berbeda
dan penyalinan angka langsung ketahuan.

Sabotase kedua yang awalnya lolos: penjaga satu-alert-per-hari. Test hanya
menghitung baris, dan barisnya memang ditahan indeks unik, sehingga cabang
penangkap galatnya tidak teruji. Test kini memeriksa bahwa pemeriksaan kedua
melaporkan nol alert baru, bukan sekadar tidak menulis baris baru.

Empat sabotase lain menggigit sejak awal: rentang tanggal yang diabaikan,
penyaring channel yang tidak diterapkan, rasio yang dihitung ulang sendiri
alih-alih dibaca dari funnel, dan tabel kampanye yang kembali menyusuri relasi
satu per satu.

Acceptance criteria `MARKETING.md` bagian 35 ditelusuri satu per satu dan
seluruhnya punya test yang menjaganya, kecuali dua hal yang memang bukan kode:
"seluruh UI responsive" dijaga konvensi komponen, dan "test tersedia" dijawab
suite itu sendiri.

---

# FASE 38 — Pemasaran Lanjutan

Garis besar `MARKETING.md` bagian 32 butir 11–17, ditambah tiga butir yang
bagian 32 tidak sebut. Checklist rinci disusun setelah Gate 37 lulus.

## Urutan pengerjaan

`MARKETING.md` bagian 32 sudah menetapkan urutan untuk tujuh butir, dan urutan
itu dipakai apa adanya kecuali satu pengecualian yang disebut di bawah. Tiga
butir sisanya — 38.03, 38.07, dan 38.08 — tidak disebut di sana, jadi
penempatannya ditentukan ketergantungan, bukan selera:

```text
1.  38.08 Kampanye lanjutan     tanpa ketergantungan; membuka CAC yang kini kosong
2.  38.03 Demo management       membuka tahap Demo di funnel yang kini selalu nol
3.  38.01 WhatsApp automation   bagian 32 butir 11
4.  38.02 Social scheduler      bagian 32 butir 12
5.  38.06 Eksperimen A/B        bagian 32 butir 14
6.  38.04 SEO manager           bagian 32 butir 15
7.  38.05 Lead magnet           bagian 32 butir 16
8.  38.10 Advanced attribution  bagian 32 butir 17
9.  38.07 Pricing presentation  presentasi saja, tidak menghalangi apa pun
10. 38.09 Partner program       ditunda ke paling akhir atas keputusan pemilik
```

Partner program berpindah dari butir 13 bagian 32 ke urutan terakhir. Itu satu-
satunya penyimpangan dari urutan dokumen, dan diambil sebagai keputusan pemilik
produk, bukan karena alasan teknis. Konsekuensinya dicatat supaya tidak lupa:
KPI `revenue_partner` dan alert `komisi_partner_tertunda` tetap kosong lebih
lama, dan pemicu otomasi `PartnerMengirimLead` tetap tanpa sumber sampai butir
itu dikerjakan. Ketiganya sudah menyatakan alasannya sendiri di katalog
masing-masing, jadi dashboard tidak berbohong selama penundaan ini.

Penundaan itu berakhir bersama 38.09. Sejak butir itu selesai, tidak ada lagi
KPI maupun alert di katalog yang menunggu sumbernya, dan seluruh pemicu otomasi
punya produsennya.

Tiap butir dikerjakan dan di-commit sendiri, dengan gate-nya sendiri. Satu gate
untuk sepuluh modul yang saling lepas berarti tidak ada yang dapat diverifikasi
sampai semuanya selesai, dan itu bertentangan dengan cara FASE 29–37 dikerjakan.

## Yang sudah tersedia dan tidak perlu dibuat ulang

Diperiksa terhadap kode yang ada, bukan diasumsikan:

- sepuluh feature flag `marketing.*` sudah lengkap, termasuk whatsapp, social,
  partner, dan experiment;
- izin `platform.{whatsapp,partner,konten,eksperimen}.{lihat,kelola}` sudah ada
  di `KatalogIzinPemasaran`;
- form builder sudah mendukung sebelas jenis field, persis daftar bagian 10;
- `KatalogKpiPemasaran` sudah menyediakan slot `cac_per_channel` dan
  `revenue_partner` beserta alasan kekosongannya;
- `KatalogAlertPemasaran` sudah menyediakan slot `whatsapp_gagal_kirim` dan
  `komisi_partner_tertunda`;
- pemicu otomasi `PartnerMengirimLead` sudah terdaftar dan menunggu sumbernya;
- host `partner.amanpoll.com` sudah ada di `PetaHost` sejak FASE 24.5;
- kontrak penyedia, registri aksi otomasi, dan pola DLQ sudah terbukti di
  FASE 34 dan 35, jadi kanal baru mengikuti bentuk yang sama.

## Peristiwa yang masih menunggu produsennya

Sepuluh kode sudah terdaftar di `KatalogPeristiwaPemasaran` tetapi belum ada
yang menuliskannya. Ini bukan utang tersembunyi: taxonomy-nya memang ditulis
lebih dulu, dan tiap butir di bawah menyebut mana yang ia hidupkan.

```text
CTA_DIKLIK, HARGA_DILIHAT                       38.07 — sudah punya produsen
FORMULIR_DIMULAI                                blok formulir, belum ada produsen
DEMO_DIMULAI, DEMO_SELESAI                      38.03 — sudah punya produsen
ARTIKEL_DILIHAT                                 38.04 — sudah punya produsen
TEMPLATE_DIUNDUH                                38.05 — sudah punya produsen
PARTNER_MENGIRIM_LEAD, KOMISI_PARTNER_DIBUAT    38.09 — sudah punya produsen
CHECKOUT_DIMULAI                                domain Langganan, di luar FASE 38
```

## 38.08 Kampanye Lanjutan

- [x] Tabel `KampanyeBiaya`, `KampanyeTarget`, `KampanyeKonten`.
- [x] Field kampanye lengkap sesuai bagian 13: budget, audience, landing page, form, offer, UTM.
- [x] State `DRAF → SIAP → AKTIF → DIJEDA → SELESAI → DIARSIPKAN` dengan peta transisi.
- [x] Channel sesuai daftar tertutup bagian 13, bukan teks bebas.
- [x] `cac_per_channel` dihidupkan di `KatalogKpiPemasaran`; alasan kekosongannya dihapus.
- [x] `HitungMetrikKampanye` ikut menjumlahkan biaya per hari.
- [x] Konsol biaya dan target di halaman kampanye.
- [x] `KampanyeBiayaTest`, `HitungCacTest`.

Biaya dicatat per hari per kampanye, bukan satu angka total, supaya CAC dapat
dibaca pada rentang tanggal mana pun tanpa membagi rata biaya sebulan.

Biaya tercatat per `ChannelKampanye`, sedangkan pelanggan baru hanya tertaut ke
kampanye lewat `AttributionPemasaran.KampanyeIdPertama`. CAC per channel karena
itu hanya pasti untuk kampanye yang berjalan di satu channel. Kampanye
multi-channel tidak dibagi rata: biaya dan pelanggan barunya dilaporkan terpisah
sebagai angka yang tidak dapat dipecah, lengkap dengan daftar kampanyenya. Kartu
KPI `cac_per_channel` menampilkan CAC gabungan seluruh kampanye; pecahannya
dibaca di panel CAC.

Kampanye baru selalu lahir sebagai draf. Tanpa aturan itu peta transisi tidak
ada gunanya: siapa pun dapat membuat kampanye langsung berstatus Aktif dan
melewati jalur yang dijaga.

**Gate 38.08.** CAC per channel terbaca di dashboard dan angkanya sama dengan
biaya dibagi pelanggan baru yang dihitung ulang langsung dari tabelnya.

## 38.03 Demo Management

- [x] Tabel `DemoPemasaran`, `SesiDemo`, `EventDemo`.
- [x] Setelan: demo enabled, dataset, reset interval, visible modules, restricted features, CTA, max session.
- [x] Event demo sesuai bagian 11: started, feature opened, asset viewed, work order created, QR viewed, preventive viewed, completed, CTA clicked.
- [x] `DEMO_DIMULAI` dan `DEMO_SELESAI` ditulis ke `EventPemasaran`, sehingga tahap Demo di funnel berhenti bernilai nol.
- [x] Job terjadwal `ResetDatasetDemo`.
- [x] Batas sesi ditegakkan, bukan sekadar disetel.
- [x] `SesiDemoTest`, `ResetDemoTest`.

Reset menghapus data demo dan membuatnya ulang dari dataset; ia tidak boleh
dapat menyentuh tenant sungguhan, dan test yang membuktikannya wajib ada.

Penjaganya dibuat dari dua fakta yang saling bebas: kolom `Organisasi.Demo`
pada tenantnya sendiri, dan tautan `DemoPemasaran.OrganisasiDemoId`. Salah
tunjuk di satu tempat saja tidak cukup untuk menghapus apa pun. Sapuannya
dibatasi daftar tabel yang diakui datasetnya, dan tiap tabel itu wajib punya
kolom `OrganisasiId` supaya penghapusannya tidak dapat melintasi tenant.

"Max session" bagian 11 dibaca sebagai dua batas yang berbeda, sebab keduanya
nyata: `MaksDurasiMenit` membatasi umur satu sesi, `MaksSesiSerentak`
membatasi berapa sesi boleh hidup bersamaan di atas satu dataset yang sama.

Dataset demo adalah daftar tertutup (`RegistriDatasetDemo`) berisi pembuat yang
benar-benar mengisi tenant sandbox. FASE ini mengirim satu pembuat nyata,
`DatasetDemoManufaktur`: tiga lokasi, lima mesin, tiga perintah kerja, dan satu
rencana preventif. Modul yang belum punya pembuat tidak dapat dipilih, bukan
dipilih lalu menghasilkan sandbox kosong.

**Gate 38.03.** Satu sesi demo terbaca utuh dari mulai sampai selesai di funnel
growth, dan reset terjadwal tidak pernah menghapus data di luar dataset demo.

## 38.01 WhatsApp Automation

- [x] Tabel `TemplateWhatsAppPemasaran`, `PengirimanWhatsAppPemasaran`.
- [x] Kontrak `PenyediaWhatsApp` beserta penyedia palsu untuk test.
- [x] Status template mengikuti approval penyedia; template belum disetujui tidak dapat dikirim.
- [x] Opt-in dibaca dari `LayananKonsen` yang sama dengan email, bukan daftar kedua.
- [x] STOP dan daftar supresi dihormati, diperiksa lagi pada saat kirim.
- [x] Frequency cap per nomor per rentang waktu, dari setelan.
- [x] Menu dan respons configurable sesuai bagian 16, bukan ditulis di kode.
- [x] Aksi otomasi `KirimWhatsApp` masuk registri.
- [x] Alert `whatsapp_gagal_kirim` dihidupkan.
- [x] `WhatsAppConsentTest`, `WhatsAppIdempotencyTest`.

Pengiriman nyata menunggu akun bisnis dan template yang disetujui Meta. Yang
dibangun di fase ini adalah seluruh jalurnya dengan penyedia palsu, sama seperti
email di FASE 34; tanpa itu, kanal ini tidak dapat diuji sama sekali.

Agar opt-in benar-benar satu buku, `KonsenPemasaran` dan `DaftarSupresi`
digeneralkan: kolom `Email` menjadi `Kontak`, `EmailHash` menjadi `KontakHash`,
dan keduanya mendapat kolom `Kanal`. Kolom bernama `Email` yang berisi nomor
telepon akan jadi jebakan bagi pembaca berikutnya. Supresinya tetap per kanal:
berhenti dari WhatsApp tidak mencabut consent email, dan sebaliknya.

Nomor dinormalkan ke E.164 tanpa tanda plus sebelum disimpan atau dibandingkan.
Tanpa itu `0812-3456`, `+62 812 3456`, dan `628123456` menjadi tiga orang yang
berbeda, dan satu di antaranya tetap dikirimi setelah mengirim STOP.

Menu bagian 16 lahir sebagai tabel ketiga, `MenuWhatsAppPemasaran`, di luar dua
tabel yang disebut checklist. Menu adalah daftar berurutan yang harus dapat
berubah tanpa rilis, jadi ia baris data, bukan kode.

Penyedia bawaan (`PenyediaWhatsAppLog`) hanya menulis ke log dan sengaja tidak
pernah menyetujui template sendiri: menyetujui berarti berbohong atas nama
penyedia, dan template yang dikira disetujui akan gagal diam-diam kelak.
Persetujuan dicatat lewat jalur manual yang masuk audit sampai API-nya ada.

**Gate 38.01.** Nomor yang mengirim STOP tidak pernah menerima pesan berikutnya,
dan template tanpa persetujuan penyedia tidak dapat berangkat.

## 38.02 Social Media Scheduler

- [x] Tabel `KontenSosial`, `DistribusiKontenSosial`, `JadwalKontenSosial`.
- [x] Kontrak `PenyediaSosial`; adapter nyata menyusul, penyedia palsu untuk test.
- [x] State `DRAF → REVIEW → TERJADWAL → DIPROSES → TERBIT → GAGAL` dengan peta transisi.
- [x] Satu konten utama dengan banyak distribusi, tiap distribusi punya channel, caption, media, jadwal, CTA, dan UTM sendiri.
- [x] UTM distribusi tertaut ke kampanye, sehingga trafiknya terbaca di attribution.
- [x] Job `TerbitkanKontenSosial` idempoten.
- [x] `DistribusiSosialTest`, `JadwalSosialTest`.

Peta transisi dipasang pada distribusinya, bukan pada konten utamanya. Yang
berpindah dari terjadwal ke terbit atau gagal adalah satu posting di satu
channel; konten utama hanyalah wadah bersama. Memberi wadah itu status yang
sama hanya akan melahirkan angka yang tidak pernah berarti apa-apa.

Idempotensinya bukan kunci unik melainkan klaim atomik: satu pernyataan
`UPDATE ... WHERE Status = 'Terjadwal'`, dan hanya proses yang mendapat satu
baris terpengaruh yang boleh memanggil penyedia. Memeriksa lalu menulis akan
lolos begitu dua pekerja berjalan bersamaan.

`JadwalKontenSosial` menyimpan tiap rencana sebagai barisnya sendiri, dan
menjadwalkan ulang membatalkan rencana lama lebih dulu. Dua rencana menunggu
untuk satu distribusi berarti dua posting.

**Gate 38.02.** Satu artikel dapat dijadwalkan ke lebih dari satu channel, dan
menjalankan ulang penerbitannya tidak menghasilkan posting ganda.

## 38.09 Partner Program

- [x] Tabel `ProgramPartner`, `Partner`, `LeadPartner`, `AturanKomisiPartner`, `KomisiPartner`, `PayoutPartner`.
- [x] Jenis partner sesuai daftar tertutup bagian 21.
- [x] Host `partner.amanpoll.com` beserta rute dan autentikasinya.
- [x] Portal partner: lead, trial, paid customer, komisi, payout, materi pemasaran.
- [x] Host partner tidak dapat diindeks, sama seperti host dashboard.
- [x] Komisi lewat kontrak domain Langganan, bukan mutasi Billing langsung.
- [x] `PARTNER_MENGIRIM_LEAD` dan `KOMISI_PARTNER_DIBUAT` ditulis ke `EventPemasaran`.
- [x] `revenue_partner` dihidupkan di `KatalogKpiPemasaran`; alert `komisi_partner_tertunda` dihidupkan.
- [x] Partner hanya melihat lead miliknya sendiri, dan ada test yang membuktikannya.
- [x] `PartnerLeadTest`, `KomisiPartnerTest`, `IsolasiPortalPartnerTest`.

Butir terbesar di FASE 38: enam tabel, satu host baru, dan satu batas akses
baru. Isolasi antar partner setara isolasi antar tenant dan diuji seketat itu.

Gate ini punya dua bagian, dan keduanya dijaga oleh bentuk kodenya, bukan oleh
kedisiplinan pemanggilnya.

Bagian kedua lebih dulu, karena ia yang menentukan bentuk seluruh butir ini.
Muatan `PeristiwaLangganan` dapat dikarang siapa pun yang dapat menyiarkan
event, termasuk jumlah rupiahnya. Karena itu komisi tidak pernah lahir dari
muatan peristiwa. Yang dibawa peristiwa hanyalah `PembayaranId`; jumlah dan
keberhasilannya ditanyakan ulang ke domain Langganan lewat kontrak baru
`PembacaPembayaranLangganan`, yang hanya menjawab untuk pembayaran berstatus
Berhasil dan bertanggal bayar. Pembayaran gagal, pembayaran yang masih
menunggu, dan id yang tidak ada sama-sama menghasilkan nol komisi. Kolom
`PembayaranId` yang unik menahan kelahiran komisi kedua atas pembayaran yang
sama, sehingga webhook kembar tidak melipatgandakan utang.

Kontraknya sengaja kontrak baca, bukan `PemberiImbalanLangganan` yang sudah
ada. Kontrak itu memberi nilai kepada satu `OrganisasiId` dalam bentuk
perpanjangan langganan, sedangkan komisi partner adalah uang keluar kepada
pihak yang sering tidak punya organisasi sama sekali. Menumpangkannya ke sana
berarti memalsukan artinya. Yang dilakukan di sini adalah yang jujur: Billing
tidak pernah ditulis dari domain Pemasaran, dan pembayaran komisi dicatat
sebagai `PayoutPartner` berisi referensi transfer yang dimasukkan admin
platform. Transfernya sendiri terjadi di luar aplikasi, karena Billing memang
hanya mengenal uang yang masuk dan tidak punya kanal untuk mengeluarkannya.
Menyatakannya begitu lebih baik daripada membuat tombol yang seolah membayar.

Bagian pertama, isolasi antar partner, dijaga dengan tidak pernah menerima id
partner dari permintaan. Seluruh kueri di `PortalPartnerController` berangkat
dari partner yang sedang masuk, termasuk ringkasan angkanya, dan mengirim
`PartnerId` milik orang lain di badan permintaan tidak mengubah pemilik lead
yang tersimpan. Guard `partner` berdiri sendiri di `config/auth.php`: admin
platform bukan partner, dan partner tidak dapat membuka konsol platform.
Statusnya diperiksa tiap permintaan, bukan hanya saat masuk, sehingga partner
yang ditangguhkan kehilangan sesinya seketika alih-alih menunggu sesinya habis
sendiri.

Host portalnya lahir sebagai `routes/partner.php` yang di-`require` dari
`bootstrap/app.php`, bukan sebagai `app/Domain/*/routes.php`, karena
`DomainServiceProvider` memasang seluruh berkas rute domain di host dashboard.
Rutenya hanya terdaftar bila `AMANPOLL_DOMAIN_PARTNER` diisi dan berbeda dari
dua host lain. Noindex-nya datang gratis dari `TandaiHostTidakTerindeks` yang
sudah menandai setiap host selain host publik, dan `robots.txt`-nya memakai
`robotsTertutup` yang sama dengan host dashboard.

Satu alamat email hanya boleh diklaim satu partner, dijaga indeks unik pada
`LeadPartner.Email`. Pengirim pertama yang memilikinya; pengirim kedua ditolak
dengan pesan yang menyebut apakah alamat itu miliknya sendiri atau sudah
diklaim partner lain. Lead yang masuk ikut menjadi `Prospek` bersumber
`Partner` supaya tim penjualan mengerjakannya di CRM yang sama, tetapi ia tidak
dicatat sebagai `FORMULIR_DIKIRIM` — tidak ada formulir yang diisi, dan
menghitungnya sebagai formulir akan menggelembungkan konversi halaman. Untuk
itu `CatatProspek::jalankan()` mendapat parameter `dariFormulir`; pemanggil
lama tidak berubah perilakunya.

Aturan komisi dipisah dari programnya supaya satu partner dapat diberi angka
berbeda tanpa menyalin seluruh program. Aturan berisi `PartnerId` mengalahkan
aturan bawaan programnya. `MaksPembayaran` menutup komisi berulang tanpa akhir,
dan komisi yang dibatalkan tidak ikut memakan jatahnya. Komisi nominal tetap
tidak pernah melampaui pembayaran yang melahirkannya.

Dua slot yang selama ini kosong kini terisi. `revenue_partner` dihitung dari
`PembayaranLangganan` milik organisasi yang punya `LeadPartner` tidak ditolak —
dibaca dari pembayarannya, bukan dari komisinya, karena komisi masih dapat
dibatalkan sedangkan uang yang sudah masuk tetap masuk. Alert
`komisi_partner_tertunda` memakai ambang hari dari setelan, bukan angka di
kode. Penyaring `partner` di dashboard growth yang sejak FASE 37 tersimpan
tanpa menyaring apa pun kini benar-benar menyaring, lewat prospek yang
ditautkan lead kiriman partner itu.

Satu hal sengaja tidak dibuat: tautan pelacak klik per partner. Bagian 21
menyebut `referral code` sebagai data partner, dan kodenya memang diterbitkan
unik dan tampil di portalnya, tetapi lead masuk lewat portal, bukan lewat klik
yang dilacak. Membangun corong klik kedua di samping corong referral FASE 36
berarti dua jalur atribusi yang harus dijaga tetap sepakat, dan butir ini tidak
memintanya.

Sabotase dijalankan atas dua puluh delapan penjaga; enam lolos pada putaran
pertama dan seluruhnya ditutup. Empat adalah celah uji yang nyata: pembayaran
gagal dan pembayaran menunggu tidak pernah diuji, kata sandi wajib dan daftar
jenis tertutup tidak pernah diuji lewat konsolnya, dan `revenue_partner` tidak
pernah diuji terhadap lead yang ditolak. Satu lagi, pesan penolakan klaim
alamat, hanya terjaga oleh indeks unik sehingga pesannya sendiri tidak terikat.
Yang keenam mengungkap kelemahan rancangan, bukan kelemahan uji: kelayakan lead
diperiksa dua kali, di kueri pencariannya dan sekali lagi di penghitung
komisinya. Pemeriksaan kedua tidak dapat dijangkau uji mana pun karena yang
pertama sudah menyaringnya, jadi ia dihapus dan aturannya ditinggalkan di satu
tempat saja, pada enum `StatusLeadPartner`.

**Gate 38.09.** Satu partner tidak dapat melihat lead partner lain, dan komisi
hanya lahir dari pembayaran yang benar-benar terjadi.

## 38.06 Eksperimen A/B

- [x] Tabel `EksperimenPemasaran`, `VarianEksperimen`, `PartisipasiEksperimen`, `HasilEksperimen`.
- [x] Target uji sesuai bagian 22: headline, CTA, landing section, panjang form, pricing, onboarding copy, subjek email.
- [x] State `DRAF → AKTIF → DIJEDA → SELESAI`.
- [x] Penetapan varian per pengunjung bersifat tetap; pengunjung yang sama tidak berpindah varian.
- [x] Metric sesuai bagian 22, dibaca dari funnel yang sudah ada.
- [x] Minimum sample dari setelan; tanpa mencapainya pemenang tidak pernah dinyatakan.
- [x] `EksperimenPenetapanTest`, `MinimumSampelTest`.

Larangan auto-declare winner adalah inti butir ini. Yang diuji bukan bahwa
tombolnya ada, melainkan bahwa eksperimen di bawah sampel minimum menolak
menyatakan pemenang sekalipun selisihnya besar.

Ambang dibaca dari varian yang paling sedikit pesertanya, bukan dari totalnya.
Satu varian yang ramai tidak boleh menutupi varian yang masih sepi; selama
salah satunya belum cukup, tidak ada yang dapat dibandingkan.

Penetapan varian dijaga dua lapis. Lapis pertama indeks unik
`(EksperimenPemasaranId, PengenalPengunjung)`, sehingga pengunjung yang sama
tidak mungkin punya dua baris. Lapis kedua pemilihan pertamanya deterministik
dari hash pengunjung, bukan acak, sehingga dua permintaan berbarengan pun
memilih varian yang sama sebelum barisnya sempat tertulis. Kode eksperimen ikut
dihash agar eksperimen tidak saling berkorelasi: tanpa itu pengunjung yang
jatuh ke varian pertama di satu eksperimen jatuh ke varian pertama di semuanya.

Metrik dibaca dengan memanggil `PenyusunFunnelGrowth` yang sama, hanya dengan
subkueri peserta varian sebagai penyaring pengunjungnya. Penyebut tiap metrik
adalah peserta varian itu, yakni semua orang yang benar-benar melihatnya.

**Gate 38.06.** Pengunjung yang sama selalu melihat varian yang sama, dan
pemenang tidak dapat dinyatakan sebelum sampel minimum tercapai.

## 38.04 CMS Konten dan SEO Manager

- [x] Tabel `KontenPemasaran`, `VersiKontenPemasaran`, `KeywordSeo`, `ClusterSeo`, `KontenKeywordSeo`.
- [x] Jenis konten sesuai daftar bagian 9.
- [x] Metadata SEO: slug, title, meta description, canonical, Open Graph, schema type, noindex.
- [x] Intent `INFORMATIONAL`, `COMMERCIAL`, `TRANSACTIONAL`, `NAVIGATIONAL`.
- [x] Sitemap memuat konten terbit; yang noindex tidak ikut.
- [x] Redirect 301, 302, dan 410 diverifikasi ulang untuk jalur konten. Catatan
      butir ini keliru: `KodeRedirect::Hilang` beserta `abort(410)` sudah ada
      sejak peta redirect dibuat, lengkap dengan `RedirectPemasaranTest::
      test_redirect_hilang_menjawab_410`. Tidak ada kode 410 yang ditambahkan di
      sini, hanya dibuktikan lagi lewat `RedirectKontenTest`.
- [x] `ARTIKEL_DILIHAT` ditulis ke `EventPemasaran`.
- [x] Konten berversi seperti halaman pemasaran, dengan versi aktif yang terkunci.
- [x] `SitemapKontenTest`, `RedirectKontenTest`, `KonsolKontenTest`.

Jalur publik konten lahir dari jenisnya: `JenisKontenPemasaran::awalanJalur()`
menetapkan raknya, dan slug yang tersimpan adalah alamat lengkapnya. Rutenya dua
ruas (`/{rak}/{ruas}`) dengan daftar rak tertutup, bukan penampung `/{jalur}`
seperti halaman pemasaran, karena dua rute berpola sama akan saling menimpa di
tabel rute.

Satu jalur hanya boleh punya satu pemilik, dan rute konten dikenali sebelum
penampung halaman, jadi jalur kembar dijaga di dua lapis: pesan ramah di
`SimpanKontenPemasaranRequest` dan penjaga domain di `SimpanDrafKonten`.

Memindahkan slug konten yang pernah terbit membuat redirect 301 dari alamat
lamanya secara otomatis, dan menghuni kembali sebuah alamat mematikan redirect
yang berangkat dari sana. Tanpa yang kedua, peta redirect yang berjalan lebih
dulu akan menyembunyikan konten yang kembali ke alamat lamanya.

Status konten memakai `StatusHalamanPemasaran` yang sudah ada, bukan enum baru
yang nyaris sama. Judul, ringkasan, naskah, dan metadata SEO berversi; slug,
jenis, penulis, dan noindex melekat pada kontennya sehingga berlaku seketika.

**Gate 38.04.** Artikel terbit muncul di sitemap dengan metadata lengkap, dan
yang ditandai noindex tidak pernah muncul di sana.

## 38.05 Lead Magnet dan Tools Publik

- [x] Berkas unduhan tertaut ke formulir; unduhan hanya setelah formulir terkirim.
- [x] `TEMPLATE_DIUNDUH` ditulis ke `EventPemasaran`.
- [x] Kalkulator MTTR, MTBF, dan downtime sebagai halaman publik.
- [x] Generator QR aset sebagai halaman publik.
- [x] Tools publik tetap tunduk pada rate limit dan anti-spam yang sama dengan formulir.
- [x] Rumus kalkulator bersumber dari `KatalogKpi` FASE 21, bukan ditulis ulang di frontend.
- [x] `UnduhanLeadMagnetTest`, `KalkulatorPublikTest`, `KonsolBerkasLeadMagnetTest`,
      `RumusKeandalanDipakaiBersamaTest`.

Tidak ada tabel baru: form builder bagian 10 sudah lengkap sejak FASE 32, yang
kurang hanya berkas unduhan dan halaman toolsnya. Rumus MTTR dan MTBF sudah ada
di domain Pelaporan; menuliskannya ulang berarti dua rumus yang kelak berbeda.

Berkas lead magnet menjadi empat kolom di `FormulirPemasaran`, bukan tabel
sendiri, dan hidup di disk privat tanpa URL publik. Gerbangnya adalah tanda
tangan yang hanya dapat lahir dari satu `PengirimanFormulir` yang benar-benar
ada, berumur satu jam: tanpa mengisi formulir tidak ada yang dapat
ditandatangani, dan berkas yang dicabut menutup tautan yang sudah terbit.

Rumus keandalan dipindahkan ke `RumusKeandalan`, dan `QueryKeandalan` kini
membacanya dari sana. Kalkulator publik memanggil kelas yang sama, jadi
angkanya bukan mirip melainkan identik; `RumusKeandalanDipakaiBersamaTest`
membandingkan keduanya langsung atas data downtime sungguhan. Angka yang tidak
punya penyebut dinyatakan belum tersedia beserta alasannya, tidak dijawab nol.

Ketiga kalkulator berbagi satu rumus dan satu halaman; yang berbeda hanya
jalur, judul, dan angka yang disorot. Tiga jalur tetap ada karena bagian 10
menyebutnya sebagai tiga lead magnet, tetapi tidak ada tiga perhitungan.

Perhitungan dan pembuatan QR dilakukan di server, bukan di frontend: itulah
satu-satunya cara rumusnya benar-benar satu, dan itu pula yang membuat tools
tunduk pada `throttle:formulir` serta honeypot yang sama dengan formulir.
Honeypot dipindahkan ke `PerangkapSpam` supaya formulir dan tools memakai
perangkap yang sama persis.

`bacon/bacon-qr-code` ditambahkan atas persetujuan pemilik produk. Repo belum
punya encoder QR sama sekali; aset hanya menyimpan `KodeQr` berupa ULID yang
tidak pernah dirender. QR ditulis sebagai SVG yang hanya berisi `rect`, `g`,
dan `path`, jadi kode yang dimasukkan tidak pernah muncul sebagai markup.

Jalur `/tools` dipakai bersama oleh tool publik dan konten berjenis FreeTool.
Rute tool didaftarkan lebih dulu, jadi konten yang menempati jalur yang sama
akan tersembunyi; `SimpanDrafKonten` dan `SimpanKontenPemasaranRequest`
menolaknya di dua lapis.

**Gate 38.05.** Berkas lead magnet tidak dapat diunduh tanpa mengisi formulir,
dan angka kalkulator publik sama dengan angka KPI yang sama di dalam aplikasi.

## 38.10 Advanced Attribution

- [x] Model attribution di luar first dan last touch: linear, time decay, position based.
- [x] Model yang dipakai dashboard dapat dipilih lewat setelan.
- [x] Sentuhan disimpan lengkap, bukan hanya yang pertama dan terakhir.
- [x] Larangan merge identitas atas sinyal lemah tetap berlaku dan kini benar-benar diuji.
- [x] Revenue per channel dapat dibaca menurut model yang dipilih.
- [x] `ModelAttributionTest`, `BobotSentuhanTest`.

First dan last touch tetap menjadi bawaan. Model lain ditambahkan di sampingnya,
tidak menggantikannya, supaya angka lama tetap dapat dibandingkan.

Tidak ada tabel sentuhan baru, dan itu bukan jalan pintas: tiap kunjungan sudah
punya barisnya sendiri di `SesiPengunjung` beserta `UtmPemasaran`-nya sejak FASE
30, jadi sentuhannya memang sudah tersimpan lengkap. Yang kurang hanya cara
membacanya sebagai satu perjalanan, dan itulah `PembacaSentuhan`. Bagian 24
melarang membuat tabel baru bila fungsinya sudah ada.

Satu sentuhan bukan satu kunjungan. Tiap permintaan halaman melahirkan satu
baris sesi, jadi kunjungan berurutan dari sumber, medium, dan kampanye yang
sama disatukan menjadi satu sentuhan yang berlanjut. Tanpa itu satu orang yang
membuka sepuluh halaman akan tampak sebagai sepuluh sentuhan.

Bobot disimpan sebagai bilangan bulat berbasis sejuta dan sisa pembagiannya
dibagikan dengan metode sisa terbesar. Jumlahnya karena itu tepat satu menurut
konstruksinya, bukan menurut pembulatan pecahan. Konsekuensinya satu channel
dapat meleset paling banyak satu per sejuta dari angka idealnya, dan itu harga
yang dibayar supaya totalnya tidak pernah melebihi uang yang benar-benar masuk.

`attribution.jendela_hari` sudah ada di katalog sejak awal tetapi tidak pernah
dibaca siapa pun. Sekarang ia dipakai, jadi sentuhan yang lebih tua dari 90 hari
tidak lagi diperhitungkan. Ini mengubah angka revenue untuk pengunjung berjarak
panjang, dan disebutkan di sini karena perubahannya nyata, bukan sekadar
penambahan fitur.

Sumber dan medium satu kedatangan sebelumnya disimpulkan di tiga tempat
terpisah: `PerekamKunjungan`, `PenyusunUlangAttribution`, dan pembaca sentuhan
yang baru. Ketiganya kini memanggil `AsalKunjungan`, supaya sentuhan pertama
yang tercatat saat kunjungan dan yang dibaca ulang tidak pernah berselisih.

Pengunjung yang riwayat sesinya sudah tidak ada lagi tetap memakai sentuhan yang
pernah tercatat di `AttributionPemasaran`. Yang jatuh ke cadangan ini hanya
pengunjung tanpa satu pun baris sesi; yang sesinya ada tetapi seluruhnya di luar
jendela memang bukan sentuhan yang boleh diperhitungkan, dan uangnya dilaporkan
sebagai `Tanpa Sentuhan`, bukan dihilangkan.

`SimpanKonfigurasiPemasaranRequest` sebelumnya menerima nilai apa pun untuk kunci
apa pun. Setelan berdaftar tertutup kini divalidasi bentuknya, karena model asing
akan diam-diam jatuh ke bawaannya saat dibaca dan angka dashboard berubah tanpa
ada yang tahu sebabnya.

Enam pembaca lain tetap memakai first touch: kampanye prospek, filter dashboard,
CAC per kampanye, dan metrik kampanye harian. Itu disengaja — yang diminta butir
ini hanya revenue per channel, dan mengubah yang lain sekaligus akan menggeser
angka yang tidak diminta bergeser.

Butir larangan merge identitas ternyata bukan sekadar verifikasi. Sabotase yang
menambahkan pencocokan berdasarkan nama tidak menjatuhkan satu test pun: test
lama membandingkan dua orang yang namanya memang berbeda, jadi larangannya tidak
pernah benar-benar dijaga. Empat test baru kini menyatakannya langsung — nama
sama, telepon dan perusahaan sama, tanpa sinyal kuat sama sekali, dan pengenal
pengunjung berbeda — dan ketiga sabotase pencocokan lemah kini dijatuhkan.

**Gate 38.10.** Jumlah bobot seluruh sentuhan satu konversi selalu tepat satu,
pada model mana pun.

## 38.07 Pricing dan Offer Presentation

- [x] Urutan paket, highlight, badge, CTA, comparison, dan FAQ diatur dari konsol.
- [x] Harga dibaca dari domain Langganan; domain Pemasaran tidak pernah menyimpan angkanya.
- [x] Presentasi promo tidak mengubah transaksi Billing.
- [x] `HARGA_DILIHAT` dan `CTA_DIKLIK` ditulis ke `EventPemasaran`.
- [x] `PresentasiHargaTest` membuktikan harga yang tampil sama dengan harga paket.

Tidak ada tabel baru dan tidak ada halaman harga tersendiri. Blok `Harga`,
`Perbandingan`, dan `Faq` sudah ada di `JenisBlokHalaman` sejak FASE 32; yang
salah adalah blok harganya menerima angka yang diketik tangan sebagai teks
bebas. Itulah yang diperbaiki butir ini.

Blok harga kini hanya menyebut kode paket beserta urutan, sorotan, badge,
ringkasan, dan CTA-nya. Nama, harga, mata uang, dan daftar fiturnya disusun
`PenyusunPresentasiHarga` dari `PaketLangganan` dan `PaketFitur` saat halaman
tampil. Presentasinya ikut berversi bersama halamannya dan ikut terjaring audit
penerbitan halaman, jadi "change pricing presentation" bagian 30 tercatat tanpa
mekanisme audit kedua.

Penyusunannya sengaja berjalan di luar cache isi halaman. Isi halaman disimpan
lima menit; kalau harga ikut tersimpan di sana, halaman akan memasang harga lama
selama lima menit setelah paketnya berubah. Gate ini menuntut harga yang tampil
selalu harga paket, jadi angkanya diambil setiap kali halaman dirender.

Tabel perbandingan disusun dari `PaketFitur`, bukan diketik ulang. Satu-satunya
sumber untuk "paket ini punya fitur itu" tetap domain Langganan. Blok
perbandingan yang tidak menyebut kode paket dibiarkan memakai isinya sendiri,
supaya perbandingan dengan produk lain tetap mungkin.

Presentasi tidak pernah menyentuh Billing: seluruh jalurnya membaca, dan tidak
ada satu pun tulisan ke domain Langganan. Promo hanya catatan teks di blok
harga. Kupon, perpanjangan trial, dan imbalan lain tetap milik jalur yang sudah
ada di FASE 36 lewat `PemberiImbalanLangganan`.

`HARGA_DILIHAT` dicatat hanya pada halaman yang benar-benar memuat blok harga,
dan `CTA_DIKLIK` lewat endpoint publik yang tunduk pada honeypot dan rate limit
yang sama. Keduanya langsung menghidupkan dua hal yang selama ini menunggu:
bobot skor prospek di `KatalogPeristiwaSkor` dan pemicu otomasi
`HalamanHargaDilihat`.

**Gate 38.07.** Mengubah presentasi harga tidak pernah mengubah angka yang
ditagihkan, dan harga yang tampil selalu sama dengan harga paket di Langganan.

### Gate 38

Seluruh gate 38.01 sampai 38.10 terpenuhi. Tiap butir dikerjakan, diuji, dan
di-commit sendiri; gate ini hanya menyatakan bahwa kesepuluhnya sudah lulus.

Kesepuluhnya lulus. Dengan 38.09 sebagai yang terakhir, tidak ada lagi KPI atau
alert di katalog pemasaran yang menunggu sumbernya, dan seluruh kode peristiwa
di `KatalogPeristiwaPemasaran` punya produsennya kecuali `FORMULIR_DIMULAI` dan
`CHECKOUT_DIMULAI`, yang memang bukan milik FASE 38.

---

# FASE 39 — Mode Lapangan (Teknisi dan Pelapor)

Tampilan aplikasi HP untuk peran lapangan: PRD 8.20, DESIGN §36. Acuan visual yang mengikat ada di
`docs/source-of-truth/mockup-mode-lapangan/teknisi.png` dan `pelapor.png`; bangun sedekat mungkin dengannya.

Batasan yang tidak boleh dilanggar:
- **Tidak membuat halaman login baru.** Layar 01 "Masuk" di kedua papan diberi cap "tidak dibangun". Login tetap
  memakai halaman dan `LoginController` yang ada; yang berubah hanya tujuan pengalihan sesudah login.
- Tidak menyalin logika bisnis. Controller Mode Lapangan menyusun data layar dan memanggil Action milik domain
  pemiliknya (Pemeliharaan, Aset, Persediaan, Sinkronisasi).
- Infrastruktur offline FASE 20 (antrian, paket, konflik, hook `use-sinkronisasi-offline`) dipakai ulang, tidak ditulis ulang.
- Penentuan pengguna lapangan memakai penanda pada `Peran`, bukan kode peran harfiah.
- Dependensi baru butuh persetujuan pemilik produk. Font `@fontsource/plus-jakarta-sans` sudah disetujui (24 September 2026)
  dan hanya dipakai Mode Lapangan. Pustaka pemindai QR apa pun tetap butuh persetujuan (bawaannya BarcodeDetector peramban + isian kode). Ikon 3D Fluent Emoji (MIT)
  adalah berkas statis di `public/images/3d/`, bukan paket; salin hanya yang dipakai, bersama lisensinya.
- Rute di bawah `/lapangan`, didaftarkan di `app/Domain/Sinkronisasi/routes.php`, domain yang sudah memiliki ruang kerja
  teknisi offline. Membuat domain baru di `app/Domain` butuh persetujuan.
- Halaman di `resources/js/features/Lapangan/`, kerangka di `resources/js/layouts/KerangkaLapangan.tsx`.

Urutan pengerjaan di bawah ini mengikat: 39.01 dan 39.02 menutup celah akses, jadi keduanya dikerjakan lebih dulu.

## 39.01 Peran dan izin bawaan (prasyarat)

- [x] Cabut `Keluhan.Kelola` dari `PELAPOR` di `KatalogPeranAwal`: pelapor hanya melihat keluhan miliknya.
- [x] Cabut `PerintahKerja.Kelola` dan `Keluhan.Kelola` dari `TEKNISI`: teknisi hanya melihat tiket yang ditugaskan kepadanya.
- [x] Pastikan teknisi tetap bisa menerima, mengerjakan, dan menyelesaikan tiket yang ditugaskan tanpa `PerintahKerja.Kelola`.
  Periksa juga minta suku cadang dan pelaksanaan checklist/inspeksi. Bila ada aksi yang ternyata mensyaratkan Kelola,
  pisahkan izinnya; jangan mengembalikan Kelola.
- [x] Perbaiki `LayananDasbor::preset` agar peran teknisi tidak lagi mendapat Dasbor Supervisor.
- [x] Perintah artisan eksplisit untuk menerapkan katalog baru ke tenant lama, tercatat di audit. Jangan mengubah peran tenant diam-diam.

## 39.02 Penanda Tampilan Lapangan dan pengarahan

- [x] Kolom `TampilanLapangan` pada `Peran` (migrasi; enum kosong/`Teknisi`/`Pelapor`), bisa diubah di halaman Peran. Katalog: `TEKNISI` → `Teknisi`, `PELAPOR` → `Pelapor`.
- [x] Layanan penentu "pengguna lapangan murni" (seluruh perannya bertanda) dan mode-nya (Teknisi menang bila memegang keduanya), di-cache seperti `LingkupAkses`.
- [x] Setelah login berhasil, pengguna lapangan murni diarahkan ke `/lapangan`.
- [x] Middleware host dasbor: pengguna lapangan murni yang membuka halaman dasbor dialihkan ke `/lapangan`. Rute JSON
  bersama (notifikasi, cari, sinkronisasi, unduhan berkas) tetap bisa diakses.
- [x] Pengguna campuran bisa beralih Mode Lapangan ⇄ dasbor dari menu Akun; pilihan diingat per perangkat.
- [x] `/offline/teknisi` dialihkan ke `/lapangan`.

## 39.03 Kerangka dan fondasi visual

- [x] Token `lapangan-*` (DESIGN §36.4) di `app.css`; `WarnaPaletTerdefinisiTest` ikut memeriksa awalan ini.
- [x] `KerangkaLapangan`: hero atau appbar gradien, kartu apung, navigasi bawah dengan tombol tengah, bilah aksi, lembar bawah;
  lebar maksimum 480px di layar lebar.
- [x] Komponen tiket, rute jam, perhentian (stasiun), chip status, tab pil, isian bergaya tiket, banner, ilustrasi momen.
- [x] Aset ikon 3D di `public/images/3d/` beserta lisensinya, dengan pemetaan makna → ikon sesuai DESIGN §36.5.

## 39.04 Teknisi: beranda, notifikasi, tiket

- [x] Menyiapkan Mode Lapangan: pengunduhan paket offline pertama kali (papan layar 02).
- [x] Beranda: jadwal hari ini, grid menu, tiket "Kerjakan sekarang", banner (layar 03).
- [x] Notifikasi (layar 04).
- [x] Tiket Saya dan Detail tiket, termasuk Alihkan dan Terima & Mulai (layar 05–06).

## 39.05 Teknisi: mengerjakan tiket

- [x] Checklist dengan perhentian langkah dan timer (layar 07).
- [x] Diagnosis dan tindakan (layar 08).
- [x] Minta suku cadang lewat lembar bawah; hanya permintaan, bukan pengubahan stok (layar 09).
- [x] Foto sebelum/sesudah, tersimpan di perangkat saat offline (layar 10).
- [x] Ringkasan, tanda tangan, dan layar selesai (layar 11–12).

## 39.06 Teknisi: pindai aset

- [x] Kamera pindai (BarcodeDetector) dengan cadangan ketik kode aset; keadaan izin kamera ditolak (layar 13).
- [x] Aset ditemukan dengan aksi cepat sesuai izin (layar 14).
- [x] Riwayat aset (layar 15).

## 39.07 Pelapor: lapor kerusakan

- [x] Beranda pelapor (papan pelapor layar 02) dan notifikasi (layar 03).
- [x] Langkah 1: pilih alat atau pindai QR; lapor lokasi saja (layar 04).
- [x] Alat ditemukan beserta pencegahan laporan ganda: pantau laporan yang ada atau tetap lapor (layar 05).
- [x] Langkah 2: masalah, urgensi berbahasa awam, foto (layar 06).
- [x] Langkah 3: tinjau & kirim; laporan terkirim, juga saat offline (layar 07–08).

## 39.08 Pelapor: pantau dan konfirmasi

- [x] Laporan Saya dan Lacak laporan (layar 09–10).
- [x] Tambah keterangan (layar 11).
- [x] Konfirmasi selesai dengan penilaian, dan layar terima kasih (layar 12–13).
- [x] Aset di lokasi (layar 14).

## 39.09 Akun, offline, konflik

- [x] Akun dan sinkronisasi: antrian, konflik, data offline, keluar (papan teknisi layar 17, papan pelapor layar 15).
- [x] Beranda saat offline (layar 16) dan layar konflik (layar 18), memakai penyelesaian konflik FASE 20.
- [x] Keadaan kosong, memuat, galat, dan tanpa izin di setiap layar (DESIGN §36.9).

## 39.10 Keputusan pemilik produk (24 September 2026)

- [x] **Urgensi pelapor menjadi usulan, bukan prioritas.** Urgensi berbahasa awam disimpan terstruktur pada keluhan
  (kolom tersendiri, bukan teks di deskripsi). Koordinator melihatnya sebagai "Usulan pelapor" di halaman keluhan dasbor, dan
  pilihan prioritas pada formulir tinjau/ubah prioritas otomatis terisi dari usulan itu. Prioritas tetap hanya diubah oleh
  pemegang `Keluhan.Kelola`, sehingga pelapor tidak bisa memberi label "Berbahaya" demi SLA tercepat.
- [x] **Pelapor boleh memantau laporan rekan pada alat/lokasi yang sama, hanya garis waktu status.** Yang tampil hanya nomor,
  judul, alat/lokasi, status, dan jam setiap perubahan status. Nama pelapor, nama teknisi, keterangan, dan foto tidak tampil.
  Hanya untuk keluhan dalam lingkup unit/ruangan pengguna itu. Dipakai dari langkah "Alat ditemukan" (tombol "Pantau laporan
  itu") dan dari penanda "ada laporan terbuka" di daftar aset.
- [x] **Foto "Sesudah" dari teknisi tampil kepada pelapor saat konfirmasi.** Hanya lampiran berkategori Sesudah pada perintah
  kerja yang berasal dari keluhan milik pelapor itu sendiri. Lampiran lain pada perintah kerja tetap tertutup bagi pelapor.
- [x] **Tanda tangan penerima: opsional secara bawaan, bisa diwajibkan per organisasi.** Setelan konfigurasi organisasi
  "Wajibkan tanda tangan penerima saat teknisi menyelesaikan tiket" (bawaan mati), bisa diubah admin di halaman Konfigurasi.
  Saat menyala, server menolak penyelesaian ke `MenungguVerifikasi` tanpa lampiran tanda tangan, dan layar Ringkasan teknisi
  menandainya wajib. Alur offline harus tetap berjalan tanpa penolakan palsu (tanda tangan diunggah sebelum penyelesaian terkirim).

Dikerjakan dua agen paralel (sisi pelapor dan tanda tangan), lalu ditinjau koordinator dengan gate penuh (1842 test, PHPStan 165).

Yang dipilih:
- Urgensi disimpan di kolom `Keluhan.UsulanUrgensi`. Enumnya pindah ke domain Pemeliharaan (`UrgensiPelapor`), karena model Keluhan yang meng-cast kolom itu, dan Pemeliharaan tidak boleh bergantung pada Sinkronisasi. Formulir ubah prioritas terisi dari usulan selama prioritas keluhan itu belum pernah diubah (belum ada audit `UbahPrioritas`).
- Pemantauan laporan rekan memakai kemampuan policy baru `KeluhanPolicy::pantau`, yang memeriksa lingkup lokasi pengguna. Penyusun layarnya hanya membaca nomor, judul, alat, lokasi, status, dan jam riwayat. Test memastikan nama, catatan, dan foto tidak ada di props.
- Foto Sesudah dibuka lewat jalur lihat-saja di `RegistriEntitas` (`kemampuanLihatLampiran`), yang diperiksa per kategori lampiran. Jalur ini hanya membuka unduhan. Daftar, unggah, dan hapus lampiran perintah kerja tetap tertutup bagi pelapor, dan `BerkasPolicy::delete` tidak lagi menumpang `view`.
- Setelan tanda tangan adalah kunci konfigurasi boolean `Pemeliharaan.WajibTandaTanganPenerima`, sehingga tidak perlu migrasi dan halaman Konfigurasi menampilkannya otomatis. Penjaganya ada di Action `UbahStatusPerintahKerja`, jadi berlaku sama untuk jalur online, antrian offline, dan dasbor. Yang diwajibkan hanya pengguna yang ditugaskan pada tiket itu. Koordinator yang memindahkan status dari dasbor tidak diwajibkan, karena penerima menandatangani di hadapan teknisi.
- Saat antrian offline dikirim, draf foto dan tanda tangan tiket yang ada di antrian diunggah lebih dulu, baru mutasinya. Bila penyelesaian tetap ditolak, tiket menampilkan pita "Laporan selesai belum diterima" dengan jalan perbaikan ke Ringkasan.

Jebakan yang ditemukan:
- `BerkasPolicy::delete` dulu memanggil `view`. Tanpa dipisah, membuka foto Sesudah bagi pelapor ikut memberinya hak menghapus foto itu.
- Pengunggah draf foto semula hanya berjalan di layar kerja tiket. Penyelesaian yang dikirim dari layar lain saat sinyal kembali bisa tiba di server sebelum tanda tangannya.

### Gate 39

- Pengguna lapangan murni: login lewat halaman yang ada → beranda Mode Lapangan. Membuka URL dasbor mana pun dialihkan
  kembali ke Mode Lapangan (diuji di test).
- Teknisi hanya melihat tiket yang ditugaskan kepadanya; pelapor hanya melihat keluhannya sendiri (diuji di test, termasuk lingkup unit).
- Alur teknisi dari terima sampai selesai dan alur lapor pelapor berjalan offline → online tanpa transaksi ganda.
- Tampilan sesuai papan acuan dan checklist DESIGN §36.9, diperiksa di lebar 360px dan 390px. (Terpenuhi)

Dikerjakan empat agen dalam tiga tahap. Tahap pertama: akses dan fondasi visual. Tahap kedua: layar Teknisi dan layar Pelapor secara paralel. Tahap ketiga: tinjauan dan gate penuh oleh koordinator (1818 test, PHPStan 165).

Yang dipilih:
- Penanda peran berupa mode (`Teknisi`/`Pelapor`), bukan ya/tidak. Setelah izin kedua peran dirapikan, ya/tidak tidak bisa lagi menentukan beranda mana yang ditampilkan. Bila memegang keduanya, Teknisi menang.
- Rute Pelapor juga terbuka bagi Teknisi, karena teknisi melapor lewat aksi cepat.
- Pengalihan dikerjakan middleware `ArahkanPenggunaLapangan` di grup `web`, sehingga tidak ada rute dasbor yang lupa dijaga. Hanya kunjungan halaman GET yang dialihkan. Permintaan JSON dan tulis tetap lewat, jadi layar lapangan memanggil endpoint domain yang sama dengan dasbor.
- Konfirmasi pelapor tidak memerlukan migrasi, karena kolom `Rating`/`Ulasan` pada Keluhan sudah ada.
  - "Sudah beres" memindahkan status Selesai → Ditutup.
  - "Masih bermasalah" memindahkan Selesai → Diproses, dengan alasan yang tercatat di riwayat status.
- Membuat keluhan menjadi operasi offline baru (`Keluhan.Buat`). Kunci perangkat didaftarkan lewat `LayananIdempotensi`, jadi kiriman online yang balasannya hilang lalu diantre ulang tetap menjadi satu keluhan.
- Teknisi menyelesaikan tiket ke `MenungguVerifikasi`. Penutupan ke Selesai tetap milik koordinator.
- Suku cadang hanya direservasi. Jalur "Pakai" yang mengurangi stok tidak pernah ditawarkan di Mode Lapangan.
- Label QR diarahkan sesuai mode: Teknisi ke lembar aset ditemukan, Pelapor ke langkah lapor dengan aset terisi, pengguna meja ke halaman aset.
- Service worker menyimpan halaman `/lapangan/*` per organisasi dan pengguna. Kuncinya ikut disimpan di cache meta, karena worker bisa dimatikan peramban kapan saja dan kehilangan kuncinya. Layar Siapkan memuat semua layar lebih dulu supaya terbuka tanpa sinyal.

Jebakan yang ditemukan:
- Melampirkan foto dan komentar pada perintah kerja mensyaratkan `PerintahKerja.Kelola`, sehingga mencabut Kelola dari teknisi ikut memblokir langkah foto. Kini lampiran juga terbuka lewat policy atas barisnya: `operate` untuk perintah kerja, `view` untuk keluhan.
- IndexedDB mengembalikan antrian offline berurut kunci UUID acak, sehingga "terima → mulai → selesai" bisa tiba terbalik (bug FASE 20). Kini antrian diurutkan menurut `DibuatPada` sebelum dikirim.
- Pola kategori "lift" ikut mencocokkan "Forklift" dan memberinya ikon lift.
- `/aset/pindai/*` harus dibebaskan dari middleware pengalih. Tanpa itu, pengguna lapangan dilempar ke `/lapangan` sebelum resolver QR sempat berjalan.

Empat keputusan produk yang semula terbuka sudah diputuskan pemilik produk pada 24 September 2026. Semuanya sudah dikerjakan di 39.10.

Batas yang disadari:
- Antrian offline belum melewati perubahan berikutnya ketika perubahan sebelumnya berkonflik; penutupnya perlu perubahan antrian di server.
- Foto yang diambil offline disimpan sebagai draf lokal dan diunggah saat online, bukan lewat antrian.
- Diagnosis yang diisi offline diantre sebagai catatan. Analisis kegagalan terstrukturnya dikirim saat ada sinyal.


---

# FASE 40 — Unit Pengelola

Beberapa bagian pemeliharaan dalam satu organisasi (PRD 8.21). Disetujui pemilik produk pada 24 September 2026.

Batasan yang tidak boleh dilanggar:
- Unit pengelola adalah `UnitOrganisasi` bertanda `MengelolaAset`, bukan tabel atau domain baru.
- Tidak ada mekanisme lingkup kedua. Kolom `UnitPengelolaId` masuk ke peta `kolomLingkup` bertipe `unit`, sehingga `ScopeLingkup` dan `LingkupAkses` yang ada bekerja tanpa diubah. Penambahan ini hanya memperluas apa yang terlihat.
- Organisasi yang tidak memakai unit pengelola (semua kolom kosong) berperilaku persis seperti sebelumnya. Test yang ada harus tetap hijau tanpa diubah maknanya.
- Penurunan unit pengelola dikerjakan Action domain pemiliknya, bukan controller, supaya dasbor, Mode Lapangan, dan antrian offline mendapat hasil yang sama.
- Tidak ada pengisian data lama saat migrasi; hanya lewat perintah artisan eksplisit (40.06).

Urutan: 40.01 dikerjakan lebih dulu karena seluruh bagian lain memakai kolom, aturan validasi, dan layanannya. 40.02–40.06 dikerjakan paralel sesudahnya.

## 40.01 Fondasi

- [x] Migrasi: `UnitOrganisasi.MengelolaAset` (boolean, bawaan false) dan `UnitPengelolaId` (nullable, FK ke `UnitOrganisasi`, berindeks bersama `OrganisasiId`) pada `Aset`, `KategoriKeluhan`, `Keluhan`, `PerintahKerja`, `Gudang`, `RencanaPemeliharaan`, `RencanaKalibrasi`.
- [x] Model: fillable, relasi `unitPengelola()`, dan `kolomLingkup` Aset, Keluhan, PerintahKerja, Gudang ditambah `UnitPengelolaId => unit`.
- [x] Aturan validasi bersama untuk isian unit pengelola: unit organisasi yang sama, aktif, dan bertanda `MengelolaAset`.
- [x] Layanan pemeriksa "lingkup pengguna mencakup baris ini" untuk model berlingkup, dipakai penugasan dan notifikasi.
- [x] Layanan penurun unit pengelola: untuk keluhan (kategori naik ke induk → aset) dan perintah kerja (isian → keluhan → aset → rencana).
- [x] Opsi unit pengelola untuk formulir (satu sumber di backend), tanda Mengelola Aset di formulir dan daftar Unit Organisasi, dan penolakan mencabut tanda selama unit masih dipakai.

## 40.02 Aset dan persediaan

- [x] Isian, kolom daftar, faset saring, detail, dan ubah massal Unit Pengelola pada aset; kolom unit pengelola di ekspor aset. (Impor aset belum ada di produk, jadi kolom impornya menunggu fitur itu; PRD 8.21 sudah menyebutnya.)
- [x] Unit pengelola pada gudang (formulir, daftar, detail).
- [x] Stok, mutasi, reservasi, dan pemakaian suku cadang mengikuti lingkup gudang, baik di daftar maupun di validasi kiriman (gudang yang tidak terlihat ditolak).

## 40.03 Keluhan

- [x] Unit pengelola pada kategori keluhan (formulir dan daftar), dengan peringatan bagi kategori tanpa unit pengelola di organisasi yang memakai fitur ini.
- [x] Penurunan unit pengelola saat keluhan dibuat dari dasbor, Mode Lapangan, dan antrian offline.
- [x] Alihkan keluhan ke unit pengelola lain (izin `Keluhan.Kelola`, alasan wajib, audit, riwayat).
- [x] Saring daftar keluhan menurut unit pengelola dan kategori; unit pengelola tampil di detail.
- [x] Notifikasi routing kategori dan eskalasi SLA hanya kepada penerima yang lingkupnya mencakup keluhan; cadangan ke pemegang `Keluhan.Kelola` berlingkup unit pengelola bila kategori tidak menunjuk peran.

## 40.04 Perintah kerja, preventif, dan kalibrasi

- [x] Penurunan unit pengelola (dan unit organisasi yang kosong) saat perintah kerja dibuat dari formulir, keluhan, preventif, kalibrasi, dan tindak lanjut inspeksi.
- [x] Isian, kolom, dan saring unit pengelola pada perintah kerja; unit pengelola pada rencana pemeliharaan dan rencana kalibrasi.
- [x] Pilihan teknisi hanya berisi pengguna yang lingkupnya mencakup tiket; server menolak penugasan kepada pengguna yang tidak bisa melihat tiketnya.

## 40.05 Laporan dan dasbor

- [x] Dimensi Unit Pengelola pada filter metrik, laporan, laporan tersimpan, ekspor, dan dasbor.

## 40.06 Lingkup pengguna, data lama, dan panduan

- [x] Lingkup efektif pengguna tampil di halaman Pengguna; peringatan saat penetapan peran tanpa lingkup akan membuka seluruh organisasi.
- [x] Perintah artisan pengisi unit pengelola yang kosong (pratinjau, per organisasi, idempoten, diaudit).
- [x] Halaman panduan `/dokumentasi` untuk menyiapkan beberapa unit pengelola (contoh IPSRS dan IT), dan data demo dengan dua unit pengelola.
- [x] Test alur ujung-ke-ujung dua bagian: keluhan printer dari ICU masuk antrian IT dan tidak terlihat koordinator IPSRS; teknisi IT ditugaskan; stok gudang IT tidak terlihat IPSRS; laporan per unit pengelola.

## 40.07 Impor aset

Menutup celah 40.02 (kolom unit pengelola di impor). Aturan di PRD 8.4 "Impor Aset".

- [x] Templat CSV dan XLSX, unggah, pratinjau validasi per baris, dan konfirmasi (semua atau tidak sama sekali, satu transaksi).
- [x] Rujukan lewat kode (termasuk unit pengelola), kode aset kosong memakai mesin kode, kode kembar dan kode yang sudah ada ditolak, lingkup pengguna dihormati.
- [x] Aset dibuat lewat Action pembuat aset yang sama dengan formulir; audit impor; panduan di `/dokumentasi`.

Pemeriksa impor memanggil aturan `SimpanAsetRequest` per baris, bukan salinannya, jadi aturan formulir dan impor tidak bisa berbeda. Setiap aset dibuat lewat `BuatAset` yang sama dengan formulir di dalam satu transaksi. Berkas dibaca OpenSpout, yang sudah terpasang.

Yang dipilih:
- Rujukan diisi kode. Merek tidak punya kolom, karena aset tidak menyimpan merek dan merek mengikuti kode model.
- Baris yang berkode sendiri dibuat lebih dulu. Tanpa itu, baris berkode kosong bisa mendapat kode otomatis yang sama dengan kode tertulis di baris sesudahnya.
- Kolom unit pengelola di templat hanya muncul bila organisasi memakai fitur itu, tetapi saat membaca kolomnya dikenali di mana pun.

Jebakan yang ditemukan:
- Aturan `unique` kode aset di formulir melewatkan aset yang diarsipkan, padahal indeks `UqAsetKode` menghitungnya, sehingga memakai kode lama berujung galat 500. Aset yang diarsipkan kini ikut dihitung.
- Formulir aset tidak membatasi lingkup, jadi staf berlingkup bisa membuat aset yang lalu tidak terlihat olehnya. Kini formulir dan impor memakai pemeriksaan yang sama (`PemeriksaLingkupBaris`).
- Ikon 3D Mode Lapangan ada di `public/aset/3d`. Folder `public/aset` membayangi rute `/aset` di server yang mengutamakan direktori nyata (`artisan serve`, dan Apache dengan `RewriteCond !-d` bawaan Laravel). Service worker juga menganggap semua `/aset/...` aset statis, sehingga kunjungan Inertia ke halaman aset dasbor tersimpan di cache. Ikon dipindah ke `public/images/3d`, pola `/aset/` dicabut, dan versi cache service worker dinaikkan agar salinan lama terhapus.


### Gate 40

- Dua unit pengelola dalam satu organisasi: antrian keluhan, perintah kerja, pilihan teknisi, gudang, dan stok terpisah (diuji di test).
- Organisasi tanpa unit pengelola tidak berubah perilakunya (seluruh test lama hijau).
- Unit pengelola sama di dasbor, Mode Lapangan, dan antrian offline. (Terpenuhi)


Dikerjakan enam agen dalam dua tahap: fondasi lebih dulu (satu agen), lalu aset & persediaan, keluhan, perintah kerja, laporan, serta lingkup pengguna & panduan secara paralel. Tahap akhir: tinjauan dan gate penuh oleh koordinator (1983 test, PHPStan 164).

Yang dipilih:
- Unit pengelola adalah `UnitOrganisasi` bertanda `MengelolaAset`. Tidak ada tabel, domain, atau mekanisme lingkup baru: kolom `UnitPengelolaId` cukup masuk `kolomLingkup` bertipe `unit`, jadi pengguna berlingkup unit IT melihat baris kelolaan IT di ruangan mana pun, dan tidak ada baris yang sebelumnya terlihat menjadi tersembunyi.
- `PemeriksaLingkupBaris` menjawab pertanyaan kebalikan ScopeLingkup ("dari sekian pengguna, siapa yang bisa melihat baris ini") dengan semantik yang sama persis. Dipakai pilihan teknisi, penolakan penugasan, penerima notifikasi routing dan eskalasi, pengalihan, dan pantau pelapor.
- Penurunan unit pengelola ada di Action pemilik (`BuatKeluhan`, `BuatPerintahKerja`), jadi dasbor, Mode Lapangan, antrian offline, API, preventif, dan tindak lanjut inspeksi mendapat hasil yang sama. Nilai unit pengelola yang dikirim pembuat keluhan diabaikan; keluhan selalu diturunkan dari kategori lalu aset.
- Stok, mutasi, reservasi, dan pemakaian disaring lewat subkueri gudang yang terlihat (`LingkupGudang`), bukan global scope. Global scope di tabel stok akan membuat `PostingMutasiStok` membuat baris stok ganda alih-alih menolak. Kiriman ke gudang di luar lingkup ditolak aturan `GudangTerlihat`.
- Notifikasi routing tanpa peran penanggung jawab jatuh ke pemegang `Keluhan.Kelola` yang berlingkup unit pengelola itu, bukan ke semua admin tanpa batas. Admin tanpa batas hanya menerima bila tidak ada seorang pun yang berlingkup.
- Penetapan peran tanpa lingkup kepada pengguna yang sebelumnya berlingkup meminta konfirmasi eksplisit, tetapi tidak diblokir karena itu sah.
- Data lama diisi lewat `pemeliharaan:isi-unit-pengelola` (pratinjau, per organisasi, idempoten, diaudit), bukan saat migrasi.

Jebakan yang ditemukan:
- `UnitOrganisasi` sendiri ber-ScopeLingkup: pengguna berlingkup ruangan melihat nol unit. Opsi formulir, aturan validasi, dan relasi `unitPengelola()` dibaca lepas dari ScopeLingkup (tenancy tetap berlaku), supaya koordinator bisa mengalihkan ke unit di luar lingkupnya.
- Eskalasi SLA berjalan dari cron tanpa konteks organisasi, dan di sana `LingkupAkses` menganggap semua orang tanpa batas. Lingkup kini dihitung di organisasi pemilik baris.
- KPI stok di dasbor menghitung semua gudang bila tidak ada filter, sehingga staf IT melihat nilai stok IPSRS. Kini selalu mengikuti gudang yang terlihat.
- Komponen DataTable menyembunyikan kolom ber-`accessorKey` yang juga punya faset sebagai "kolom bayangan"; kolom Status di Unit Organisasi dan Lokasi tidak pernah tampil di desktop.
- Validasi rak pada mutasi stok menerima rak dari gudang mana pun. Kini rak harus milik gudang mutasi itu.


---

# FASE 41 — Login tanpa kode organisasi

Aturan di PRD 8.1. Disetujui pemilik produk pada 24 September 2026. Halaman login yang ada tetap dipakai; hanya isian kode organisasinya dihapus.

- [ ] Login dengan email dan kata sandi: satu akun cocok langsung masuk, beberapa akun cocok ke layar Pilih organisasi, tidak ada yang cocok memberi pesan umum. Pembatasan percobaan, catatan akses, "ingat saya", dan pengarahan Mode Lapangan tetap berlaku.
- [ ] Layar Pilih organisasi: hanya organisasi akun yang kata sandinya cocok, disimpan sementara di sesi, server menolak akun di luar daftar.
- [ ] Lupa kata sandi dengan email saja: tautan untuk setiap akun aktif ber-email itu, jawaban layar sama apa pun hasilnya.
- [ ] Teks yang masih menyebut kode organisasi untuk masuk (pesan pendaftaran trial, panduan) diperbarui.

---

# 29. Urutan Ringkas yang Tidak Boleh Dibalik Sembarangan

```text
00 Validasi Project
↓
01 Shared Foundation
↓
02 Multi-Organisasi
↓
03 Auth + RBAC + Security
↓
04 Organisasi + Unit + Lokasi + Konfigurasi
↓
05 Audit + Berkas + Tag + KolomKustom + Komentar
↓
06 Approval + Notifikasi Dasar
↓
07 Penyedia
↓
08 Asset Registry
↓
09 Siklus Aset
↓
10 Gudang + Suku Cadang + Stok
↓
11 SLA + Keluhan
↓
12 Perintah Kerja Korektif
↓
13 Checklist + Preventive + Inspeksi
↓
14 Kalibrasi
↓
15 Anggaran + Perencanaan
↓
16 Procurement
↓
17 Kontrak
↓
18 Kepatuhan
↓
19 Integrasi + Webhook + Outbox + Idempotensi
↓
20 Offline PWA
↓
21 Dashboard + Laporan
↓
22 SaaS + Langganan
↓
23 UI/UX Completion
↓
24 Security Hardening
↓
24.5 Pemisahan Host
↓
25 Performance + Reliability
↓
26 Testing Lengkap
↓
27 Deployment Niagahoster
↓
28 UAT + Release
↓
29 Fondasi Pemasaran
↓
30 Pengunjung + UTM + Attribution
↓
31 Prospek + CRM
↓
32 Halaman Publik + Formulir
↓
33 Trial Event + Aktivasi
↓
34 Consent + Email Pemasaran
↓
35 Otomasi Pemasaran
↓
36 Referral Dasar
↓
37 Dashboard Growth
↓
38 Pemasaran Lanjutan
↓
39 Mode Lapangan (Teknisi + Pelapor)
↓
40 Unit Pengelola
↓
41 Login tanpa kode organisasi
```

Alasan urutan tersebut: setiap fase memakai fondasi dari fase sebelumnya. Dashboard berada dekat akhir karena dashboard harus membaca data transaksi yang sudah benar, bukan menjadi halaman demo yang lebih dulu dibuat.

Pemisahan host berada di 24.5 karena ia mengubah rute autentikasi: dikerjakan sebelum Testing Lengkap dan Deployment, bukan sesudahnya. Pemasaran berada setelah rilis karena seluruh prasyaratnya — IAM, Notifikasi, Integrasi, Langganan, UI Core, Hardening — baru lengkap di titik itu, dan attribution lintas host menuntut 24.5 sudah lulus.

Mode Lapangan (39) dikerjakan setelah seluruh modul operasional, offline (20), dan UI Core (23) stabil, karena ia
hanya wajah baru di atas aksi yang sudah ada. Di dalam fase itu, perbaikan izin peran bawaan (39.01) dan pengarahan
(39.02) wajib selesai sebelum layar dibangun.

---

# 30. Definition of Done Task

Sebuah checkbox fitur hanya boleh menjadi `[x]` bila:

- Kode selesai.
- Clean code.
- Nama business function Bahasa Indonesia.
- Komentar singkat satu baris bila diperlukan.
- Authorization selesai.
- Validation selesai.
- Transaction benar.
- Audit benar bila diperlukan.
- UI responsive.
- Error/empty/loading state ada.
- Test relevan hijau.
- PHPStan tidak bertambah error.
- TypeScript tidak bertambah error.
- Tidak ada placeholder.
- Tidak ada hardcode tenant.
- Tidak ada query N+1 yang diketahui.
