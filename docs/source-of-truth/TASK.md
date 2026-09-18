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

- [ ] Inisialisasi repository Git Amanpoll.
- [ ] Tetapkan branch `main` sebagai protected branch bila platform mendukung.
- [ ] Buat `.env.example` tanpa secret.
- [ ] Pastikan `.env`, storage private, credential, dan hasil build lokal tidak ter-commit secara tidak sengaja.
- [ ] Pastikan PHP 8.4+ lokal.
- [ ] Pastikan Node.js 20+.
- [ ] Pastikan Composer 2+.
- [ ] Pastikan MySQL 8+.
- [ ] Jalankan `composer validate`.
- [ ] Jalankan `npm install`.
- [ ] Jalankan build Vite.
- [ ] Jalankan test Laravel default.
- [ ] Jalankan PHPStan/Larastan baseline tanpa menutupi error baru.

## 00.02 Validasi Schema

- [ ] Import schema Amanpoll ke database development kosong.
- [ ] Pastikan seluruh foreign key berhasil dibuat.
- [ ] Pastikan charset `utf8mb4`.
- [ ] Pastikan timezone aplikasi UTC untuk penyimpanan waktu.
- [ ] Cocokkan tabel dengan domain.
- [ ] Audit index untuk foreign key dan query utama.
- [ ] Pastikan tabel tenant memiliki `OrganisasiId` sesuai kebutuhan.
- [ ] Pastikan tabel histori/transaksi tidak menggunakan hard delete tanpa alasan.
- [ ] Dokumentasikan perubahan schema sebelum mulai coding fitur.

## 00.03 Validasi Generator

- [ ] Jalankan `index.js` pada project kosong.
- [ ] Pastikan tidak ada syntax error PHP.
- [ ] Pastikan tidak ada syntax error TypeScript.
- [ ] Pastikan file generated tidak memiliki namespace salah.
- [ ] Pastikan Eloquent model mengarah ke table PascalCase yang benar.
- [ ] Pastikan `HasUlids` hanya digunakan pada entitas yang sesuai.
- [ ] Pastikan timestamp mapping menggunakan `DibuatPada`, `DiperbaruiPada`, `DihapusPada`.
- [ ] Hapus scaffold dummy yang tidak akan digunakan.
- [ ] Jangan menerima generated repository/action kosong sebagai implementasi selesai.

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

- [ ] Business class menggunakan Bahasa Indonesia.
- [ ] Business function menggunakan Bahasa Indonesia.
- [ ] Variable bisnis menggunakan Bahasa Indonesia.
- [ ] Status menggunakan Enum, bukan magic string tersebar.
- [ ] Komentar maksimal satu baris dan menjelaskan alasan.
- [ ] Controller tidak memuat business logic kompleks.
- [ ] Action hanya memiliki satu use case utama.
- [ ] Query kompleks dipisah dari command.
- [ ] Repository interface hanya dibuat bila memberi boundary yang nyata.
- [ ] Hindari class `Helper` generik.

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

- [ ] `AturanBisnisDilanggar`
- [ ] `AksesDitolak`
- [ ] `DataTidakDitemukan`
- [ ] `KonflikData`
- [ ] `VersiDataBerubah`
- [ ] Mapping exception ke response web/API konsisten.
- [ ] Production response tidak menampilkan stack trace.

## 01.03 Transaction Helper

- [ ] Tetapkan policy kapan `DB::transaction()` wajib.
- [ ] Gunakan transaction pada multi-write.
- [ ] External HTTP call tidak dilakukan di tengah transaction jika dapat dihindari.
- [ ] Event eksternal gunakan outbox.

## 01.04 Time dan Timezone

- [ ] Simpan waktu UTC.
- [ ] Tampilkan berdasarkan `ZonaWaktu` organisasi/lokasi.
- [ ] Buat service konversi waktu terpusat.
- [ ] Jangan memanggil timezone hardcoded di feature.
- [ ] Test edge case pergantian tanggal lokal.

## 01.05 Uang dan Angka

- [ ] Gunakan `DECIMAL` sesuai schema.
- [ ] Jangan gunakan float untuk nilai uang.
- [ ] Buat formatter mata uang di frontend.
- [ ] Kalkulasi total selalu diverifikasi server.
- [ ] Tentukan aturan pembulatan.

### Gate 01

Shared convention terdokumentasi dan minimal satu test membuktikan exception mapping, timezone, serta transaction behavior.

---

# FASE 02 — Multi-Organisasi

Tujuan: membangun boundary keamanan sebelum data bisnis.

## 02.01 Konteks Organisasi

- [ ] Buat `KonteksOrganisasi`.
- [ ] Resolve organisasi dari session.
- [ ] Resolve organisasi dari API key.
- [ ] Resolve organisasi untuk queue job.
- [ ] Fail closed bila konteks tenant tidak tersedia pada operasi tenant.
- [ ] Jangan mengambil `OrganisasiId` mentah dari request untuk menentukan tenant.

## 02.02 Scope Data

- [ ] Global scope atau tenant repository yang konsisten.
- [ ] Create otomatis mengisi `OrganisasiId`.
- [ ] Update memverifikasi organisasi.
- [ ] Delete memverifikasi organisasi.
- [ ] Route model binding tenant-aware.
- [ ] Relation lintas tenant ditolak.

## 02.03 Test Isolasi

- [ ] Organisasi A tidak dapat membaca data B.
- [ ] Organisasi A tidak dapat update data B.
- [ ] Organisasi A tidak dapat delete data B.
- [ ] Organisasi A tidak dapat attach relation ke data B.
- [ ] API key A tidak dapat mengakses B.
- [ ] Background job tidak kehilangan scope tenant.

### Gate 02

Tidak ada domain bisnis berikutnya sebelum tenant isolation test hijau.

---

# FASE 03 — Authentication, Session, RBAC, dan Security

## 03.01 Login

- [ ] Login menggunakan kode organisasi + email + password.
- [ ] Validasi status organisasi.
- [ ] Validasi status pengguna.
- [ ] Regenerate session setelah login.
- [ ] Logout menghapus session.
- [ ] Rate limit login.
- [ ] Generic error untuk credential salah.

## 03.02 Pengguna

- [ ] Daftar pengguna.
- [ ] Buat pengguna.
- [ ] Ubah pengguna.
- [ ] Aktif/nonaktif.
- [ ] Reset password flow.
- [ ] Profil pengguna.
- [ ] Perangkat pengguna.

## 03.03 RBAC

- [ ] CRUD Peran.
- [ ] CRUD Izin hanya sesuai policy platform.
- [ ] Assign PenggunaPeran.
- [ ] Assign PeranIzin.
- [ ] Middleware izin.
- [ ] Policy per entity.
- [ ] Frontend directive/helper untuk visibility action.
- [ ] Backend tetap menjadi sumber authorization.

## 03.04 API Key

- [ ] Generate secret sekali.
- [ ] Simpan hash.
- [ ] Prefix untuk lookup.
- [ ] Scope/permission.
- [ ] Expiry.
- [ ] Revoke.
- [ ] Optional IP allowlist.
- [ ] Audit create/revoke.

### Gate 03

Auth web dan API tenant-aware, RBAC aktif, test unauthorized dan cross-tenant lulus.

---

# FASE 04 — Struktur Organisasi dan Konfigurasi

## 04.01 Organisasi

- [ ] Detail organisasi.
- [ ] Edit profil.
- [ ] Logo.
- [ ] Zona waktu.
- [ ] Status sesuai policy platform.

## 04.02 UnitOrganisasi

- [ ] CRUD.
- [ ] Hierarki parent-child.
- [ ] Cegah circular hierarchy.
- [ ] Filter unit aktif.

## 04.03 KategoriLokasi dan Lokasi

- [ ] CRUD kategori.
- [ ] CRUD lokasi.
- [ ] Hierarki lokasi.
- [ ] Cegah circular hierarchy.
- [ ] Hubungkan unit.
- [ ] Search lokasi.
- [ ] Status aktif/nonaktif.

## 04.04 KonfigurasiOrganisasi

- [ ] Key-value config yang tervalidasi.
- [ ] Namespace config per fitur.
- [ ] Default config.
- [ ] Cache config dengan invalidation jelas.

## 04.05 NomorDokumen

- [ ] Format prefix.
- [ ] Sequence.
- [ ] Reset period bila diperlukan.
- [ ] Lock/concurrency safety.
- [ ] Preview nomor.
- [ ] Test request paralel.

## 04.06 HariLibur

- [ ] CRUD.
- [ ] Digunakan oleh service kalender kerja.
- [ ] Scope organisasi.

### Gate 04

Admin organisasi dapat menyiapkan struktur dasar sampai lokasi tanpa SQL/manual setup.

---

# FASE 05 — Audit, Berkas, Tag, Kolom Kustom, Komentar

Ini dikerjakan sebelum aset karena akan digunakan hampir semua domain.

## 05.01 CatatanAudit

- [ ] Service audit terpusat.
- [ ] Actor.
- [ ] Organisasi.
- [ ] Entitas.
- [ ] Aksi.
- [ ] Before/after.
- [ ] Request/correlation metadata yang aman.
- [ ] Filter audit.
- [ ] Policy read.

## 05.02 CatatanAkses

- [ ] Catat security-sensitive access bila diperlukan.
- [ ] Hindari logging berlebihan pada setiap GET biasa.
- [ ] Retention policy.

## 05.03 Berkas

- [ ] Upload.
- [ ] MIME validation.
- [ ] Size limit.
- [ ] Storage abstraction.
- [ ] Download authorized.
- [ ] Delete sesuai policy.
- [ ] Filename aman.
- [ ] Local storage production awal.
- [ ] Siapkan driver S3-compatible.

## 05.04 LampiranEntitas

- [ ] Attach.
- [ ] Detach.
- [ ] Authorization berdasarkan entitas induk.
- [ ] Urutan/jenis lampiran bila tersedia.

## 05.05 Tag

- [ ] CRUD tag.
- [ ] Assign/unassign ke entitas.
- [ ] Search/filter tag.

## 05.06 KolomKustom

- [ ] CRUD definisi.
- [ ] Tipe input.
- [ ] Required.
- [ ] Opsi.
- [ ] Validation.
- [ ] Nilai per entitas.
- [ ] Rendering form dinamis.

## 05.07 Komentar

- [ ] Tambah komentar.
- [ ] Edit sesuai aturan.
- [ ] Hapus/soft delete sesuai aturan.
- [ ] Audit.

### Gate 05

Aset nanti dapat langsung memakai file, tag, custom field, komentar, dan audit tanpa refactor.

---

# FASE 06 — Approval Engine dan Notifikasi Dasar

Approval dibangun sebelum transaksi yang membutuhkannya.

## 06.01 AlurPersetujuan

- [ ] CRUD alur.
- [ ] Jenis entitas.
- [ ] Kondisi aktivasi.
- [ ] Status aktif.
- [ ] Validasi tidak ada tahap kosong.

## 06.02 TahapPersetujuan

- [ ] Urutan.
- [ ] Approver user/role/unit.
- [ ] Jumlah persetujuan bila dibutuhkan.
- [ ] Larangan self-approval configurable.
- [ ] Rejection behavior.

## 06.03 PermintaanPersetujuan

- [ ] Buat permintaan.
- [ ] Snapshot konteks penting.
- [ ] Status.
- [ ] Current stage.
- [ ] Cancel.
- [ ] Reject.
- [ ] Complete.

## 06.04 KeputusanPersetujuan

- [ ] Approve.
- [ ] Reject.
- [ ] Catatan.
- [ ] Timestamp.
- [ ] Append-oriented.
- [ ] Audit.

## 06.05 Notifikasi Dasar

- [ ] Templat notifikasi.
- [ ] In-app notification.
- [ ] Preferensi.
- [ ] Email adapter optional.
- [ ] Queue database.
- [ ] Failure handling.

### Gate 06

Buat satu use case test dummy persetujuan end-to-end sebelum approval dipakai domain lain.

---

# FASE 07 — Penyedia

## 07.01 KategoriPenyedia

- [ ] CRUD.
- [ ] Status.
- [ ] Validasi penggunaan.

## 07.02 Penyedia

- [ ] CRUD.
- [ ] Identitas.
- [ ] Alamat.
- [ ] Kontak.
- [ ] Status.
- [ ] Lampiran.
- [ ] Tag.
- [ ] Kolom kustom.

## 07.03 PenyediaKategori

- [ ] Assign multi kategori.
- [ ] Remove.
- [ ] Filter.

## 07.04 KontakPenyedia

- [ ] CRUD.
- [ ] Kontak utama.
- [ ] Validasi.

## 07.05 PenilaianPenyedia

- [ ] Form penilaian.
- [ ] Histori.
- [ ] Rekap.

### Gate 07

Penyedia dapat digunakan oleh aset, procurement, kontrak, dan kalibrasi.

---

# FASE 08 — Master Aset

## 08.01 KategoriAset

- [ ] CRUD.
- [ ] Hierarki.
- [ ] Cegah circular.
- [ ] Default property bila relevan.

## 08.02 Merek

- [ ] CRUD.
- [ ] Search.
- [ ] Duplicate prevention yang wajar.

## 08.03 ModelAset

- [ ] CRUD.
- [ ] Hubungkan merek.
- [ ] Kategori.
- [ ] Metadata teknis.

## 08.04 Asset Registry

- [ ] Daftar aset.
- [ ] Search server-side.
- [ ] Filter.
- [ ] Sort.
- [ ] Pagination.
- [ ] Buat aset.
- [ ] Ubah aset.
- [ ] Detail aset.
- [ ] Soft delete/archive.
- [ ] Generate identifier/QR.
- [ ] Scan resolver.
- [ ] Lampiran.
- [ ] Tag.
- [ ] Field kustom.

## 08.05 RiwayatLokasiAset

- [ ] Set lokasi awal.
- [ ] Perubahan lokasi membuat histori.
- [ ] Cegah edit histori sembarang.
- [ ] Current location konsisten dengan history.

## 08.06 Penanggung Jawab

- [ ] Assign.
- [ ] Ganti.
- [ ] Histori.
- [ ] Validasi user/unit tenant.

## 08.07 RelasiAset

- [ ] Parent-child.
- [ ] Related asset.
- [ ] Cegah self-reference.
- [ ] Cegah cycle jika relation bersifat hierarchy.

## 08.08 GaransiAset

- [ ] CRUD.
- [ ] Penyedia.
- [ ] Periode.
- [ ] Dokumen.
- [ ] Reminder.

## 08.09 NilaiAset

- [ ] Harga perolehan.
- [ ] Nilai buku bila digunakan.
- [ ] Histori nilai.
- [ ] Formatter uang.

## 08.10 Meter

- [ ] Definisi meter per aset.
- [ ] Unit.
- [ ] Pembacaan.
- [ ] Validasi pembacaan mundur bila meter kumulatif.
- [ ] Histori.

### Gate 08

Detail aset menampilkan identitas dan histori inti secara benar sebelum lifecycle/maintenance ditambahkan.

---

# FASE 09 — Siklus Aset

## 09.01 PermintaanMutasiAset

- [ ] Buat draft.
- [ ] Tambah detail aset.
- [ ] Submit.
- [ ] Hubungkan approval.
- [ ] Approve/reject.
- [ ] Cancel.
- [ ] Audit.

## 09.02 Eksekusi Mutasi

- [ ] Validasi aset.
- [ ] Validasi lokasi tujuan.
- [ ] Update current location hanya setelah syarat terpenuhi.
- [ ] Tulis RiwayatLokasiAset.
- [ ] Transaction.
- [ ] Idempotency internal.

## 09.03 SerahTerimaAset

- [ ] Buat dokumen.
- [ ] Detail aset.
- [ ] Pihak asal.
- [ ] Pihak tujuan.
- [ ] Kondisi.
- [ ] Terima.
- [ ] Lampiran.
- [ ] Audit.

## 09.04 PenghapusanAset

- [ ] Draft.
- [ ] Detail.
- [ ] Alasan.
- [ ] Approval.
- [ ] Eksekusi.
- [ ] Asset status.
- [ ] Histori.
- [ ] Audit.
- [ ] Larang hard-delete aset historis.

### Gate 09

Lifecycle aset dapat ditelusuri dari registrasi sampai mutasi/serah terima/penghapusan.

---

# FASE 10 — Persediaan dan Suku Cadang

Dikerjakan sebelum PerintahKerja penuh agar pemakaian suku cadang tidak ditambal belakangan.

## 10.01 Gudang

- [ ] CRUD gudang.
- [ ] Lokasi gudang.
- [ ] Status.
- [ ] Scope unit/lokasi.

## 10.02 Master SukuCadang

- [ ] Kategori.
- [ ] Suku cadang.
- [ ] Unit.
- [ ] SKU/kode.
- [ ] Min stock.
- [ ] Harga.
- [ ] Status.

## 10.03 Kompatibilitas

- [ ] Suku cadang ↔ model/aset.
- [ ] Filter compatible part.

## 10.04 StokSukuCadang

- [ ] Saldo per lokasi.
- [ ] Stok fisik.
- [ ] Stok reserved.
- [ ] Stok tersedia.
- [ ] Lock saat mutasi.

## 10.05 MutasiStok

- [ ] Penerimaan.
- [ ] Pengeluaran.
- [ ] Transfer.
- [ ] Adjustment.
- [ ] Return.
- [ ] Detail.
- [ ] Nomor dokumen.
- [ ] Transaction.
- [ ] Audit.

## 10.06 Reservasi

- [ ] Reserve.
- [ ] Release.
- [ ] Consume.
- [ ] Expiry bila digunakan.
- [ ] Cegah over-reservation.

## 10.07 Stock Alert

- [ ] Minimum stock.
- [ ] Notifikasi.
- [ ] Dashboard widget.

### Gate 10

Tidak ada endpoint yang mengubah `StokSukuCadang` langsung tanpa transaksi mutasi yang sah.

---

# FASE 11 — SLA dan Keluhan

## 11.01 TingkatLayanan

- [ ] CRUD.
- [ ] Aturan response.
- [ ] Aturan resolution.
- [ ] Kalender kerja.
- [ ] Hari libur.
- [ ] Prioritas/kategori.

## 11.02 Service Kalkulasi SLA

- [ ] Hitung deadline response.
- [ ] Hitung deadline resolution.
- [ ] Respect jam kerja.
- [ ] Respect hari libur.
- [ ] Unit test skenario lintas hari.

## 11.03 KategoriKeluhan

- [ ] CRUD.
- [ ] Default priority.
- [ ] Routing rule bila dibutuhkan.

## 11.04 Keluhan

- [ ] Buat keluhan.
- [ ] Detail.
- [ ] Asset optional sesuai jenis complaint.
- [ ] Lokasi.
- [ ] Pelapor.
- [ ] Lampiran.
- [ ] Triage.
- [ ] Ubah prioritas sesuai izin.
- [ ] Histori status.
- [ ] Tutup.
- [ ] Batalkan/tolak sesuai policy.

## 11.05 EskalasiTingkatLayanan

- [ ] Due soon.
- [ ] Breach.
- [ ] Prevent duplicate escalation.
- [ ] Queue.
- [ ] Notification.

### Gate 11

Keluhan memiliki deadline SLA yang dapat diuji dan riwayat status lengkap.

---

# FASE 12 — Perintah Kerja Korektif

## 12.01 PerintahKerja Core

- [ ] Buat dari keluhan.
- [ ] Buat manual sesuai izin.
- [ ] Nomor dokumen.
- [ ] Jenis.
- [ ] Prioritas.
- [ ] Asset.
- [ ] Lokasi.
- [ ] Histori status.

## 12.02 State Machine

- [ ] Definisikan Enum status.
- [ ] Definisikan transition yang diperbolehkan.
- [ ] Tolak transition ilegal.
- [ ] Semua transition masuk histori.
- [ ] Audit transition kritis.

## 12.03 Penugasan

- [ ] Assign teknisi.
- [ ] Reassign.
- [ ] Multiple assignee bila schema mendukung.
- [ ] Accepted/rejected assignment.
- [ ] Notification.
- [ ] Workload indicator.

## 12.04 WaktuKerja

- [ ] Mulai.
- [ ] Pause.
- [ ] Resume.
- [ ] Selesai.
- [ ] Cegah session ganda yang tidak sah.
- [ ] Hitung durasi server-side.

## 12.05 WaktuHentiAset

- [ ] Mulai downtime.
- [ ] Selesai downtime.
- [ ] Alasan.
- [ ] Hindari overlap yang tidak valid.
- [ ] Rekap.

## 12.06 PemakaianSukuCadang

- [ ] Reserve dari pekerjaan.
- [ ] Consume.
- [ ] Return unused.
- [ ] Mutasi stok otomatis.
- [ ] Biaya sparepart.
- [ ] Transaction lintas work order + stock.

## 12.07 BiayaPerintahKerja

- [ ] Tenaga kerja bila digunakan.
- [ ] Sparepart.
- [ ] Vendor.
- [ ] Lain-lain.
- [ ] Total dihitung server-side.

## 12.08 Kegagalan

- [ ] Kode kegagalan.
- [ ] Analisis.
- [ ] Root cause.
- [ ] Tindakan perbaikan.

## 12.09 Penyelesaian

- [ ] Catatan hasil.
- [ ] Foto.
- [ ] Verifikasi.
- [ ] Close.
- [ ] Reopen sesuai izin dan audit.

### Gate 12

Flow `Keluhan → PerintahKerja → Teknisi → Sparepart → Selesai → Ditutup` harus lulus integration test end-to-end.

---

# FASE 13 — Checklist, Preventive, dan Inspeksi

## 13.01 TemplatDaftarPeriksa

- [ ] CRUD.
- [ ] Butir.
- [ ] Urutan.
- [ ] Jenis jawaban.
- [ ] Required.
- [ ] Min/max.
- [ ] Version strategy.

## 13.02 PelaksanaanDaftarPeriksa

- [ ] Snapshot template.
- [ ] Jawaban.
- [ ] Foto/catatan.
- [ ] Validasi required.
- [ ] Finalisasi.
- [ ] Lock final result sesuai aturan.

## 13.03 RencanaPemeliharaan

- [ ] Calendar-based.
- [ ] Meter-based.
- [ ] Asset assignment.
- [ ] Interval.
- [ ] Next due.
- [ ] Aktif/nonaktif.

## 13.04 Generator Jadwal

- [ ] Scheduler command.
- [ ] Idempotent.
- [ ] Tidak membuat duplicate.
- [ ] Membuat JadwalPemeliharaan.
- [ ] Membuat PerintahKerja jika waktunya.
- [ ] Test cron rerun.

## 13.05 Inspeksi

- [ ] Templat.
- [ ] Pelaksanaan.
- [ ] Findings.
- [ ] Pass/fail.
- [ ] Generate Keluhan/PerintahKerja dari temuan.
- [ ] Lampiran.

### Gate 13

Menjalankan scheduler dua kali tidak boleh menggandakan preventive work order yang sama.

---

# FASE 14 — Kalibrasi

## 14.01 JenisKalibrasi

- [ ] CRUD.
- [ ] Unit/metode metadata.

## 14.02 RencanaKalibrasi

- [ ] Asset.
- [ ] Interval.
- [ ] Penyedia optional.
- [ ] Reminder window.
- [ ] Next due.

## 14.03 PelaksanaanKalibrasi

- [ ] Jadwal.
- [ ] Pelaksana.
- [ ] Penyedia.
- [ ] Hasil.
- [ ] Sertifikat.
- [ ] Finalisasi.
- [ ] Next due generation.

## 14.04 TitikUkur

- [ ] Definisi titik.
- [ ] Expected.
- [ ] Tolerance.
- [ ] Actual result.
- [ ] Pass/fail.

## 14.05 Reminder

- [ ] Due soon.
- [ ] Overdue.
- [ ] Prevent duplicate notifications.
- [ ] Dashboard.

### Gate 14

Riwayat kalibrasi lengkap, sertifikat authorized, next due konsisten.

---

# FASE 15 — Anggaran dan Perencanaan

## 15.01 Anggaran

- [ ] Periode.
- [ ] Total.
- [ ] Status.
- [ ] Approval bila dibutuhkan.

## 15.02 PosAnggaran

- [ ] CRUD.
- [ ] Parent bila digunakan.
- [ ] Nilai.
- [ ] Scope.

## 15.03 TransaksiAnggaran

- [ ] Komitmen.
- [ ] Realisasi.
- [ ] Pelepasan komitmen.
- [ ] Adjustment sesuai izin.
- [ ] Sisa dihitung dari ledger/transaksi.
- [ ] Cegah race condition.

## 15.04 UsulanAset

- [ ] Draft.
- [ ] Submit.
- [ ] Penilaian.
- [ ] Prioritas.
- [ ] Approval.
- [ ] Histori.

## 15.05 RencanaPengadaan

- [ ] Buat dari usulan.
- [ ] Detail.
- [ ] Estimasi.
- [ ] Pos anggaran.
- [ ] Status.

### Gate 15

Sisa anggaran dapat direkonsiliasi dari transaksi, bukan angka edit manual.

---

# FASE 16 — Procurement

## 16.01 PermintaanPembelian

- [ ] Draft.
- [ ] Detail item.
- [ ] Total.
- [ ] Submit.
- [ ] Approval.
- [ ] Budget validation.

## 16.02 PermintaanPenawaran

- [ ] Buat dari request.
- [ ] Pilih penyedia.
- [ ] Deadline.
- [ ] Status.

## 16.03 PenawaranPenyedia

- [ ] Header.
- [ ] Detail.
- [ ] Harga.
- [ ] Pajak/biaya bila schema mendukung.
- [ ] Lampiran.
- [ ] Evaluasi.

## 16.04 PesananPembelian

- [ ] Generate dari hasil.
- [ ] Nomor.
- [ ] Detail.
- [ ] Total.
- [ ] Approval.
- [ ] Kirim/status.

## 16.05 PenerimaanPembelian

- [ ] Partial receipt.
- [ ] Full receipt.
- [ ] Cegah over-receipt.
- [ ] Kondisi.
- [ ] Dokumen.
- [ ] Integrasi register aset untuk item aset.
- [ ] Integrasi stok untuk item suku cadang.

## 16.06 TagihanPenyedia

- [ ] Invoice.
- [ ] Matching ke PO/receipt.
- [ ] Status.
- [ ] Lampiran.

## 16.07 PembayaranPenyedia

- [ ] Payment record.
- [ ] Partial/full.
- [ ] Reference.
- [ ] Audit.

### Gate 16

Flow procurement end-to-end lulus test dan tidak menghasilkan mismatch total/quantity.

---

# FASE 17 — Kontrak

## 17.01 Kontrak

- [ ] CRUD.
- [ ] Penyedia.
- [ ] Tanggal.
- [ ] Nilai.
- [ ] Status.
- [ ] Dokumen.

## 17.02 KontrakAset

- [ ] Attach aset.
- [ ] Validasi tenant.
- [ ] Periode coverage.

## 17.03 LayananKontrak

- [ ] Jenis layanan.
- [ ] SLA.
- [ ] Limit bila ada.
- [ ] Hubungan ke PerintahKerja vendor.

## 17.04 Reminder

- [ ] H-90/H-60/H-30 configurable.
- [ ] Expired.
- [ ] Notification.
- [ ] Dashboard.

### Gate 17

Pekerjaan vendor dapat ditelusuri ke penyedia dan kontrak aktif.

---

# FASE 18 — Kepatuhan dan Sertifikasi

## 18.01 StandarKepatuhan

- [ ] CRUD.
- [ ] Scope.
- [ ] Version metadata.
- [ ] Status.

## 18.02 PersyaratanKepatuhan

- [ ] Requirement.
- [ ] Evidence type.
- [ ] Frequency bila ada.

## 18.03 KepatuhanAset

- [ ] Assign standard.
- [ ] Status.
- [ ] Evidence.
- [ ] Review.
- [ ] Expiry.

## 18.04 SertifikasiAset

- [ ] Nomor.
- [ ] Penerbit.
- [ ] Periode.
- [ ] File.
- [ ] Status.
- [ ] Reminder.

### Gate 18

Tidak ada standard regulator spesifik yang di-hardcode sebagai core Amanpoll.

---

# FASE 19 — Integrasi, Webhook, Outbox, Idempotensi

## 19.01 IntegrasiEksternal

- [ ] CRUD konfigurasi.
- [ ] Credential encryption.
- [ ] Test connection.
- [ ] Status.
- [ ] Audit.

## 19.02 PemetaanDataEksternal

- [ ] Internal ↔ external mapping.
- [ ] Conflict state.
- [ ] Manual resolve.

## 19.03 SinkronisasiEksternal

- [ ] Pull/push abstraction.
- [ ] Queue.
- [ ] Status.
- [ ] Retry.
- [ ] Error details aman.

## 19.04 PanggilanBalikWeb

- [ ] Endpoint config.
- [ ] Secret.
- [ ] Event subscription.
- [ ] Disable.

## 19.05 PengirimanPanggilanBalikWeb

- [ ] Queue.
- [ ] Signature.
- [ ] Retry/backoff.
- [ ] Delivery log.
- [ ] Final failed state.

## 19.06 KotakKeluarPeristiwa

- [ ] Write dalam transaction bisnis.
- [ ] Worker publish.
- [ ] Mark processed.
- [ ] Retry.
- [ ] Lock concurrency.

## 19.07 KunciIdempotensi

- [ ] Middleware/service.
- [ ] Scope organisasi + endpoint + key.
- [ ] Request fingerprint.
- [ ] Cached response bila aman.
- [ ] Conflict bila key dipakai payload berbeda.
- [ ] TTL/cleanup.

### Gate 19

Satu contoh endpoint kritis dan satu event eksternal harus terbukti idempotent.

---

# FASE 20 — Offline PWA

Jangan mulai sebelum flow online stabil.

## 20.01 Installability

- [ ] Manifest.
- [ ] Icons.
- [ ] Service worker.
- [ ] Offline fallback page.
- [ ] Update strategy.

## 20.02 Cache Strategy

- [ ] App shell.
- [ ] Jangan cache response sensitif secara sembarang.
- [ ] Tenant/user cache key.
- [ ] Clear local data saat logout.

## 20.03 AntrianSinkronisasi

- [ ] Local mutation ID.
- [ ] Queue.
- [ ] Retry.
- [ ] Status.

## 20.04 PenandaSinkronisasi

- [ ] Last sync.
- [ ] Entity version.
- [ ] Conflict detection.

## 20.05 Offline Teknisi

- [ ] Assignment list.
- [ ] Asset summary.
- [ ] Checklist.
- [ ] Draft pekerjaan.
- [ ] Draft catatan.
- [ ] Queue perubahan.
- [ ] UX indicator offline/unsynced.

## 20.06 Conflict Resolution

- [ ] Server version check.
- [ ] Tidak overwrite diam-diam.
- [ ] UI conflict untuk kasus yang perlu user.
- [ ] Audit resolution.

### Gate 20

Simulasi offline → input → reconnect → sync tidak menggandakan transaksi.

---

# FASE 21 — Dashboard dan Laporan

Dikerjakan setelah sumber transaksi stabil agar dashboard tidak dibangun di atas data palsu.

## 21.01 Query Metrics

- [ ] Asset counts.
- [ ] Asset condition.
- [ ] Complaint.
- [ ] Work order.
- [ ] SLA.
- [ ] Downtime.
- [ ] MTTR.
- [ ] MTBF.
- [ ] Cost.
- [ ] Stock.
- [ ] Calibration.
- [ ] Preventive.
- [ ] Procurement.
- [ ] Budget.
- [ ] Contract.
- [ ] Compliance.

## 21.02 Dashboard

- [ ] Role-aware.
- [ ] Date filter.
- [ ] Unit/location filter.
- [ ] Responsive.
- [ ] Empty states.
- [ ] No fake chart.

## 21.03 LaporanTersimpan

- [ ] Save filter.
- [ ] Ownership.
- [ ] Shared scope sesuai izin.
- [ ] Delete.

## 21.04 DasborTersimpan

- [ ] Layout.
- [ ] Komponen.
- [ ] Preference.
- [ ] Validation.

## 21.05 Export

- [ ] CSV/XLSX/PDF hanya bila benar-benar diperlukan.
- [ ] Queue untuk export besar.
- [ ] Notification saat selesai.
- [ ] Authorization saat download.

### Gate 21

Setiap KPI utama memiliki definisi formula yang terdokumentasi dan query test.

---

# FASE 22 — SaaS dan Langganan

## 22.01 FiturPaket

- [ ] Master feature.
- [ ] Key stabil.
- [ ] Deskripsi.

## 22.02 PaketLangganan

- [ ] CRUD platform.
- [ ] Harga.
- [ ] Period.
- [ ] Status.

## 22.03 PaketFitur

- [ ] Entitlement.
- [ ] Limit.
- [ ] Validation.

## 22.04 Langganan

- [ ] Start.
- [ ] Trial.
- [ ] Active.
- [ ] Grace.
- [ ] Expired.
- [ ] Cancel.

## 22.05 Entitlement Middleware

- [ ] Backend check.
- [ ] UI check.
- [ ] Limit check.
- [ ] Clear error.

## 22.06 Tagihan dan Pembayaran

- [ ] Invoice.
- [ ] Payment.
- [ ] Provider abstraction.
- [ ] Webhook idempotency.
- [ ] Reconciliation.

### Gate 22

Tenant expired/limited tidak dapat bypass restriction melalui API langsung.

---

# FASE 23 — UI/UX Completion

Implementasi visual mengikuti `DESIGN.md`.

## 23.01 Shell

- [ ] Sidebar desktop.
- [ ] Sidebar collapsed.
- [ ] Mobile drawer.
- [ ] Topbar.
- [ ] Breadcrumb.
- [ ] Page header.
- [ ] Notification center.
- [ ] User menu.

## 23.02 Standard Components

- [ ] Button.
- [ ] Input.
- [ ] Select.
- [ ] Combobox.
- [ ] Date picker.
- [ ] Textarea.
- [ ] Checkbox.
- [ ] Radio.
- [ ] Switch.
- [ ] Badge.
- [ ] Alert.
- [ ] Dialog.
- [ ] Drawer/Sheet.
- [ ] Tabs.
- [ ] DataTable.
- [ ] Pagination.
- [ ] EmptyState.
- [ ] ErrorState.
- [ ] Skeleton.
- [ ] FileUploader.
- [ ] SearchFilterBar.
- [ ] StatusTimeline.
- [ ] StatCard.
- [ ] ActivityFeed.

## 23.03 Responsive Audit

Test minimal:

- [ ] 360x800.
- [ ] 390x844.
- [ ] 768x1024.
- [ ] 1024x768.
- [ ] 1280x800.
- [ ] 1440x900.
- [ ] 1920x1080.

### Gate 23

Tidak ada halaman utama yang memerlukan desktop untuk menyelesaikan pekerjaan teknisi dasar.

---

# FASE 24 — Security Hardening

- [ ] CSRF audit.
- [ ] XSS audit.
- [ ] Authorization audit.
- [ ] Mass assignment audit.
- [ ] File upload audit.
- [ ] API rate limit.
- [ ] Login rate limit.
- [ ] Session cookie secure.
- [ ] Credential encryption.
- [ ] API key hashing.
- [ ] Cross-tenant penetration test internal.
- [ ] IDOR test.
- [ ] Export authorization test.
- [ ] Audit log tamper resistance sesuai kemampuan schema.
- [ ] Dependency vulnerability check.
- [ ] Production debug off.

### Gate 24

Tidak ada known critical/high issue yang belum memiliki keputusan mitigasi.

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

## 27.02 Build

- [ ] `composer install --no-dev --optimize-autoloader`.
- [ ] Build frontend sebelum upload bila Node tidak tersedia/diinginkan di server.
- [ ] Upload `public/build`.
- [ ] `php artisan optimize`.
- [ ] `php artisan storage:link` bila strategi file public memerlukannya.

## 27.03 Cron

- [ ] Scheduler.
- [ ] Queue worker pendek.
- [ ] Queue command tidak overlap berbahaya.
- [ ] Test log cron.
- [ ] Test reminder.
- [ ] Test outbox.

## 27.04 Database Production

- [ ] Buat database dari hPanel.
- [ ] Gunakan schema hosting tanpa `CREATE DATABASE`.
- [ ] Backup sebelum deployment schema change.
- [ ] Migration/SQL change terdokumentasi.
- [ ] Tidak menjalankan destructive change tanpa backup.

### Gate 27

Smoke test production lulus setelah deployment.

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
25 Performance + Reliability
↓
26 Testing Lengkap
↓
27 Deployment Niagahoster
↓
28 UAT + Release
```

Alasan urutan tersebut: setiap fase memakai fondasi dari fase sebelumnya. Dashboard berada dekat akhir karena dashboard harus membaca data transaksi yang sudah benar, bukan menjadi halaman demo yang lebih dulu dibuat.

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
