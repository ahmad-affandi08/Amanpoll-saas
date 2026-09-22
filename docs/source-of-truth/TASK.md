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

- [ ] Slow query review.
- [ ] EXPLAIN query utama.
- [ ] Index update berdasarkan query nyata.
- [ ] N+1 detection.
- [ ] Pagination semua daftar besar.

## 25.02 Queue

- [ ] Retry policy.
- [ ] Failed job handling.
- [ ] Idempotent jobs.
- [ ] Cron overlap prevention.
- [ ] Queue batch size sesuai shared hosting.

## 25.03 Scheduler

- [ ] `withoutOverlapping()` pada task relevan.
- [ ] Preventive.
- [ ] SLA.
- [ ] Reminder.
- [ ] Outbox.
- [ ] Webhook retry.
- [ ] Cleanup.

## 25.04 Backup

- [ ] Database backup.
- [ ] File backup.
- [ ] Retention.
- [ ] Restore test.
- [ ] Dokumentasi recovery.

### Gate 25

Restore test dilakukan, bukan hanya backup job tersedia.

---

# FASE 26 — Testing Lengkap

## 26.01 Unit Test

Prioritas:

- [ ] Kalkulasi SLA.
- [ ] Kalkulasi anggaran.
- [ ] Stock rule.
- [ ] State transition.
- [ ] Penomoran.
- [ ] Timezone.
- [ ] Idempotency.
- [ ] Approval rule.

## 26.02 Feature Test

- [ ] Auth.
- [ ] Tenant.
- [ ] RBAC.
- [ ] Aset.
- [ ] Mutasi.
- [ ] Stock.
- [ ] Keluhan.
- [ ] PerintahKerja.
- [ ] Preventive.
- [ ] Kalibrasi.
- [ ] Procurement.
- [ ] Approval.
- [ ] Integration.
- [ ] Subscription.

## 26.03 End-to-End Critical Paths

- [ ] Setup tenant → user → location → aset.
- [ ] Keluhan → perintah kerja → sparepart → close.
- [ ] Preventive → schedule → work order → checklist → close.
- [ ] Kalibrasi → hasil → sertifikat → next due.
- [ ] Usulan → procurement → receipt → asset/stock.
- [ ] Mutasi → approval → handover → history.
- [ ] Webhook retry.
- [ ] Offline sync idempotent.

### Gate 26

Tidak ada critical path release yang hanya diuji manual.

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
CTA_DIKLIK, FORMULIR_DIMULAI, HARGA_DILIHAT     38.07 dan blok halaman
DEMO_DIMULAI, DEMO_SELESAI                      38.03 — sudah punya produsen
ARTIKEL_DILIHAT                                 38.04 — sudah punya produsen
TEMPLATE_DIUNDUH                                38.05 — sudah punya produsen
PARTNER_MENGIRIM_LEAD, KOMISI_PARTNER_DIBUAT    38.09
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

- [ ] Tabel `ProgramPartner`, `Partner`, `LeadPartner`, `AturanKomisiPartner`, `KomisiPartner`, `PayoutPartner`.
- [ ] Jenis partner sesuai daftar tertutup bagian 21.
- [ ] Host `partner.amanpoll.com` beserta rute dan autentikasinya.
- [ ] Portal partner: lead, trial, paid customer, komisi, payout, materi pemasaran.
- [ ] Host partner tidak dapat diindeks, sama seperti host dashboard.
- [ ] Komisi lewat kontrak domain Langganan, bukan mutasi Billing langsung.
- [ ] `PARTNER_MENGIRIM_LEAD` dan `KOMISI_PARTNER_DIBUAT` ditulis ke `EventPemasaran`.
- [ ] `revenue_partner` dihidupkan di `KatalogKpiPemasaran`; alert `komisi_partner_tertunda` dihidupkan.
- [ ] Partner hanya melihat lead miliknya sendiri, dan ada test yang membuktikannya.
- [ ] `PartnerLeadTest`, `KomisiPartnerTest`, `IsolasiPortalPartnerTest`.

Butir terbesar di FASE 38: enam tabel, satu host baru, dan satu batas akses
baru. Isolasi antar partner setara isolasi antar tenant dan diuji seketat itu.

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

- [ ] Model attribution di luar first dan last touch: linear, time decay, position based.
- [ ] Model yang dipakai dashboard dapat dipilih lewat setelan.
- [ ] Sentuhan disimpan lengkap, bukan hanya yang pertama dan terakhir.
- [ ] Larangan merge identitas atas sinyal lemah tetap berlaku dan tetap diuji.
- [ ] Revenue per channel dapat dibaca menurut model yang dipilih.
- [ ] `ModelAttributionTest`, `BobotSentuhanTest`.

First dan last touch tetap menjadi bawaan. Model lain ditambahkan di sampingnya,
tidak menggantikannya, supaya angka lama tetap dapat dibandingkan.

**Gate 38.10.** Jumlah bobot seluruh sentuhan satu konversi selalu tepat satu,
pada model mana pun.

## 38.07 Pricing dan Offer Presentation

- [ ] Urutan paket, highlight, badge, CTA, comparison, dan FAQ diatur dari konsol.
- [ ] Harga dibaca dari domain Langganan; domain Pemasaran tidak pernah menyimpan angkanya.
- [ ] Presentasi promo tidak mengubah transaksi Billing.
- [ ] `HARGA_DILIHAT` dan `CTA_DIKLIK` ditulis ke `EventPemasaran`.
- [ ] `PresentasiHargaTest` membuktikan harga yang tampil sama dengan harga paket.

**Gate 38.07.** Mengubah presentasi harga tidak pernah mengubah angka yang
ditagihkan, dan harga yang tampil selalu sama dengan harga paket di Langganan.

### Gate 38

Seluruh gate 38.01 sampai 38.10 terpenuhi. Tiap butir dikerjakan, diuji, dan
di-commit sendiri; gate ini hanya menyatakan bahwa kesepuluhnya sudah lulus.

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
```

Alasan urutan tersebut: setiap fase memakai fondasi dari fase sebelumnya. Dashboard berada dekat akhir karena dashboard harus membaca data transaksi yang sudah benar, bukan menjadi halaman demo yang lebih dulu dibuat.

Pemisahan host berada di 24.5 karena ia mengubah rute autentikasi: dikerjakan sebelum Testing Lengkap dan Deployment, bukan sesudahnya. Pemasaran berada setelah rilis karena seluruh prasyaratnya — IAM, Notifikasi, Integrasi, Langganan, UI Core, Hardening — baru lengkap di titik itu, dan attribution lintas host menuntut 24.5 sudah lulus.

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
