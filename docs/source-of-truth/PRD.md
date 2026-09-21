# PRD — Amanpoll

| Atribut | Nilai |
|---|---|
| Produk | Amanpoll |
| Dokumen | Product Requirements Document |
| Versi | 1.0.0 |
| Status | Baseline Implementasi |
| Tanggal | 18 September 2026 |
| Target | CMMS, Asset Management, Maintenance, Procurement, Compliance, dan Operational Asset Platform |
| Backend | Laravel 13 · PHP 8.4+ · MySQL 8 |
| Frontend | Inertia.js · React 19 · TypeScript · shadcn/ui · Tailwind CSS 4 |
| Hosting Produksi Awal | Niagahoster Web Hosting Business |
| Bahasa UI | Bahasa Indonesia |
| Tema | Light only, tanpa dark mode |

---

## 1. Ringkasan Produk

Amanpoll adalah platform pengelolaan aset dan operasional pemeliharaan yang dapat digunakan lintas industri. Sistem tidak boleh memiliki ketergantungan domain terhadap rumah sakit, manufaktur, hotel, kampus, gedung, F&B, properti, workshop, perusahaan jasa, atau industri tertentu. Kebutuhan khusus industri ditempatkan sebagai konfigurasi, standar kepatuhan, integrasi eksternal, kolom kustom, templat pemeriksaan, dan aturan proses.

Amanpoll mengelola seluruh siklus hidup aset dari organisasi, lokasi, registrasi aset, mutasi, serah terima, pemeliharaan, inspeksi, kalibrasi, gudang dan suku cadang, pengadaan, kontrak, kepatuhan, persetujuan, integrasi, audit, pelaporan, sampai langganan SaaS.

Amanpoll bukan aplikasi CRUD aset. Sistem harus menjadi sumber data operasional untuk menjawab pertanyaan seperti:

- Aset apa yang dimiliki organisasi dan berada di mana.
- Siapa penanggung jawab aset saat ini.
- Aset mana yang rusak, tidak aktif, terlambat dipelihara, atau akan jatuh tempo.
- Keluhan mana yang melewati SLA.
- Teknisi mengerjakan apa, berapa lama, dan dengan biaya berapa.
- Suku cadang apa yang digunakan dan bagaimana dampaknya terhadap stok.
- Berapa biaya siklus hidup sebuah aset.
- Aset mana yang layak diperbaiki, diganti, direlokasi, atau dihapus.
- Pengadaan apa yang sedang direncanakan dan berapa sisa anggarannya.
- Kontrak dan sertifikasi apa yang akan berakhir.
- Aktivitas siapa yang mengubah data penting.
- Data mana yang gagal tersinkronisasi dengan sistem eksternal.

---

## 2. Tujuan Produk

### 2.1 Tujuan Utama

1. Menjadi platform CMMS dan Asset Management yang dapat dipakai lintas industri.
2. Menyediakan satu sumber data aset dari pengadaan sampai penghapusan.
3. Mengurangi pekerjaan manual melalui penjadwalan, notifikasi, eskalasi, dan workflow.
4. Menjaga histori transaksi dan perubahan penting agar dapat diaudit.
5. Menyediakan pengalaman teknisi yang cepat pada desktop, tablet, dan ponsel.
6. Memungkinkan organisasi mengatur struktur, role, SLA, checklist, approval, dan integrasinya sendiri.
7. Mendukung multi-organisasi tanpa kebocoran data antar tenant.
8. Dapat berjalan di Niagahoster Web Hosting Business tanpa bergantung pada daemon permanen.
9. Memiliki struktur kode yang dapat ditingkatkan ke VPS/dedicated infrastructure tanpa membongkar domain bisnis.

### 2.2 Sasaran Kualitas

- Waktu halaman operasional normal: target < 2,5 detik pada koneksi wajar.
- Request API umum: target p95 < 500 ms di luar proses file/report berat.
- Query daftar wajib pagination dan tidak boleh melakukan full-load dataset besar.
- Tidak ada query bisnis lintas `OrganisasiId` tanpa otorisasi eksplisit.
- Tidak ada perubahan status kritis tanpa histori.
- Tidak ada transaksi stok tanpa ledger/mutasi yang dapat ditelusuri.
- Tidak ada pekerjaan async penting yang hanya bergantung pada request browser.
- Semua halaman operasional harus usable mulai lebar 360 px.

---

## 3. Prinsip Produk

### 3.1 General Purpose

Core Amanpoll tidak boleh menggunakan istilah atau field yang hanya benar untuk satu industri. Contoh:

- `Lokasi`, bukan `RuangRawat`.
- `Aset`, bukan `AlatKesehatan`.
- `UnitOrganisasi`, bukan `InstalasiRumahSakit`.
- `Penyedia`, bukan `VendorKalibrasiRumahSakit`.

Kebutuhan khusus industri diakomodasi melalui:

- `DefinisiKolomKustom`
- `NilaiKolomKustom`
- `StandarKepatuhan`
- `PersyaratanKepatuhan`
- `IntegrasiEksternal`
- `PemetaanDataEksternal`
- Templat checklist
- Templat inspeksi
- Workflow persetujuan

### 3.2 Audit First

Operasi kritis harus menghasilkan rekam jejak yang jelas:

- Siapa.
- Kapan.
- Organisasi.
- Entitas.
- Aksi.
- Nilai sebelum.
- Nilai sesudah.
- Alasan bila diwajibkan.
- Sumber akses bila relevan.

### 3.3 Configuration Over Hardcode

SLA, nomor dokumen, workflow persetujuan, kategori, checklist, notifikasi, preferensi, dan konfigurasi organisasi tidak boleh di-hardcode jika dapat dijadikan data.

### 3.4 Progressive Complexity

UI default harus sederhana. Field lanjutan ditampilkan sesuai konteks, izin, atau ekspansi. Kompleksitas database tidak boleh dipindahkan mentah-mentah ke pengguna.

---

## 4. Pengguna dan Peran

### 4.1 Super Admin Platform

Mengelola Amanpoll sebagai platform:

- Paket langganan.
- Fitur paket.
- Organisasi.
- Status langganan.
- Diagnostik platform.
- Integrasi platform.
- Audit tingkat platform yang diizinkan.

Super Admin tidak otomatis boleh membaca data operasional tenant. Akses dukungan harus eksplisit dan dapat diaudit.

### 4.2 Admin Organisasi

Mengelola:

- Profil organisasi.
- Unit organisasi.
- Lokasi.
- Pengguna.
- Peran dan izin.
- Konfigurasi.
- Penomoran dokumen.
- Hari libur.
- Integrasi.
- Preferensi sistem.

### 4.3 Manajer / Kepala Unit

Fokus pada:

- Dashboard.
- Persetujuan.
- SLA.
- Beban kerja.
- Anggaran.
- Pengadaan.
- Biaya.
- Kepatuhan.
- Laporan.

### 4.4 Supervisor / Koordinator

Mengelola pekerjaan operasional:

- Triage keluhan.
- Penugasan teknisi.
- Monitoring perintah kerja.
- Jadwal.
- Eskalasi.
- Verifikasi hasil.
- Inspeksi.
- Preventive maintenance.

### 4.5 Teknisi

Fokus pada mobile-first workflow:

- Scan QR/barcode.
- Melihat detail aset.
- Menerima penugasan.
- Memulai dan menghentikan pekerjaan.
- Mengisi checklist.
- Mencatat diagnosis.
- Mencatat tindakan.
- Menggunakan suku cadang.
- Mengunggah foto/dokumen.
- Meminta bantuan/eskalasi.
- Menyelesaikan pekerjaan.

### 4.6 Pelapor / Pengguna Unit

- Melihat aset pada scope yang diizinkan.
- Melaporkan kerusakan/keluhan.
- Melihat status keluhan miliknya.
- Memberikan verifikasi atau umpan balik bila diwajibkan.

### 4.7 Gudang

- Master suku cadang.
- Stok.
- Penerimaan/pengeluaran.
- Reservasi.
- Mutasi antar lokasi gudang.
- Penyesuaian sesuai izin.

### 4.8 Pengadaan / Keuangan

- Anggaran.
- Usulan.
- Perencanaan.
- Permintaan pembelian.
- RFQ.
- Penawaran.
- PO.
- Penerimaan.
- Tagihan.
- Pembayaran.

### 4.9 Auditor

Akses read-only ke data yang diizinkan:

- Histori.
- Audit.
- Kepatuhan.
- Transaksi.
- Laporan.

---

## 5. Arsitektur Aplikasi

### 5.1 Gaya Arsitektur

Amanpoll menggunakan **Modular Monolith + DDD pragmatis**.

Batas domain harus jelas. Domain tidak boleh saling mengakses tabel domain lain secara sembarang. Interaksi lintas domain dilakukan melalui application service, contract/repository, event, atau service yang jelas.

Struktur konseptual:

```text
app/
├── Core/
├── Domain/
│   ├── Platform/
│   ├── Kolaborasi/
│   ├── Penyedia/
│   ├── Aset/
│   ├── SiklusAset/
│   ├── Pemeliharaan/
│   ├── PreventifInspeksi/
│   ├── Kalibrasi/
│   ├── Persediaan/
│   ├── PerencanaanPengadaan/
│   ├── Kontrak/
│   ├── Kepatuhan/
│   ├── Persetujuan/
│   ├── Notifikasi/
│   ├── IntegrasiAudit/
│   ├── Sinkronisasi/
│   ├── Pelaporan/
│   └── Langganan/
├── Http/
├── Jobs/
├── Providers/
└── Shared/
```

### 5.2 Layer per Domain

```text
Domain/NamaDomain/
├── Application/
│   ├── Actions/
│   ├── Commands/
│   ├── DTO/
│   ├── Queries/
│   └── Services/
├── Domain/
│   ├── Events/
│   ├── Exceptions/
│   ├── Policies/
│   ├── Repositories/
│   ├── Rules/
│   └── ValueObjects/
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/
│   │   ├── QueryBuilders/
│   │   └── Repositories/
│   └── Services/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Notifications/
└── Support/
```

### 5.3 Aturan Dependensi

- `Domain` tidak bergantung pada Inertia atau detail UI.
- Controller tipis; tidak berisi business rule kompleks.
- Request hanya validasi input dan authorization sederhana.
- Business rule ditempatkan pada Action/Service/Rule/ValueObject.
- Query kompleks ditempatkan pada QueryBuilder/Query Service.
- Model Eloquent tidak dijadikan tempat seluruh logika aplikasi.
- Operasi lintas beberapa tabel menggunakan transaction.
- Event eksternal tidak dikirim sebelum transaksi database berhasil.
- Proses berat masuk queue.
- Query tenant selalu dibatasi `OrganisasiId`.

---

## 6. Standar Kode

### 6.1 Bahasa

Seluruh nama business code menggunakan Bahasa Indonesia.

Contoh yang benar:

```php
public function buatPerintahKerja(array $data): PerintahKerja
public function hitungSisaAnggaran(string $posAnggaranId): int
public function pindahkanAset(Aset $aset, Lokasi $tujuan): void
public function validasiKetersediaanStok(SukuCadang $sukuCadang, int $jumlah): void
```

Contoh yang tidak digunakan untuk business method:

```php
createWorkOrder()
calculateBudget()
moveAsset()
checkStock()
```

Pengecualian hanya untuk method kontrak framework/library yang memang wajib menggunakan nama tertentu, seperti:

```php
handle()
rules()
authorize()
boot()
up()
down()
```

### 6.2 Penamaan

- Class: PascalCase Bahasa Indonesia.
- Function/method: camelCase Bahasa Indonesia.
- Variable: camelCase Bahasa Indonesia.
- Table dan column: mengikuti schema PascalCase yang telah ditetapkan.
- Enum: PascalCase Bahasa Indonesia.
- Event: bentuk kejadian, contoh `PerintahKerjaDibuat`.
- Action: bentuk tindakan, contoh `BuatPerintahKerja`.
- Query: bentuk kebutuhan data, contoh `CariAset`.
- Job: bentuk kerja async, contoh `KirimNotifikasiJatuhTempo`.
- Policy: mengikuti entitas, contoh `AsetPolicy`.

### 6.3 Komentar

Komentar hanya dibuat bila menjelaskan alasan, batasan, atau keputusan yang tidak terlihat dari kode.

Maksimal satu baris dan langsung ke inti.

```php
// Cegah stok menjadi negatif tanpa izin override.
```

Hindari:

```php
// Fungsi ini digunakan untuk melakukan proses pengecekan stok
// kemudian akan melihat apakah stok tersedia atau tidak
// lalu mengembalikan hasil...
```

Jangan memberi komentar untuk kode yang sudah jelas.

### 6.4 Clean Code

- Satu function memiliki satu tujuan utama.
- Hindari function sangat panjang; pecah berdasarkan responsibility.
- Hindari boolean parameter yang ambigu.
- Gunakan Value Object untuk konsep penting bila memberi manfaat nyata.
- Hindari magic string untuk status.
- Gunakan Enum untuk status tetap.
- Hindari N+1 query.
- Gunakan eager loading secara eksplisit.
- Pagination wajib untuk daftar besar.
- Validasi server adalah sumber kebenaran.
- Frontend validation hanya membantu UX.
- Gunakan database transaction untuk operasi atomik.
- Jangan swallow exception.
- Error domain memiliki exception yang spesifik.
- Jangan log password, token, secret, atau data sensitif.
- Hindari service generik bernama `Helper` bila tanggung jawab dapat diberi nama yang jelas.

---

## 7. Multi-Organisasi dan Keamanan Data

### 7.1 Isolasi Tenant

Setiap request bisnis harus memiliki konteks organisasi yang valid.

Sumber konteks dapat berasal dari:

- Session user.
- API key.
- Token integrasi.
- Job payload yang tervalidasi.

Ketentuan:

1. `OrganisasiId` tidak boleh dipercaya dari input client bila konteks sudah diketahui.
2. Query global lintas organisasi hanya tersedia untuk operasi platform khusus.
3. Semua create otomatis mengisi `OrganisasiId`.
4. Semua update/delete memastikan entitas berada di organisasi aktif.
5. Route model binding tidak boleh membuka akses lintas organisasi.
6. Test kebocoran tenant wajib tersedia.

### 7.2 RBAC

Izin bersifat granular.

Format rekomendasi:

```text
Aset.Lihat
Aset.Buat
Aset.Ubah
Aset.Hapus
Aset.Mutasi
PerintahKerja.Lihat
PerintahKerja.Buat
PerintahKerja.Tugaskan
PerintahKerja.Selesaikan
Persediaan.Sesuaikan
Pengadaan.Setujui
```

UI boleh menyembunyikan aksi, tetapi backend tetap harus memvalidasi izin.

### 7.3 Audit

Wajib diaudit:

- Login penting dan perubahan keamanan.
- Create/update/delete data master kritis.
- Perubahan status.
- Persetujuan/penolakan.
- Penyesuaian stok.
- Penghapusan aset.
- Perubahan role/izin.
- Perubahan integrasi/API key.
- Perubahan konfigurasi organisasi.
- Perubahan pembayaran/langganan.

---

## 8. Modul Fungsional

## 8.1 Platform, Tenancy, IAM

### Kebutuhan

- Organisasi.
- Unit organisasi bertingkat.
- Kategori lokasi.
- Lokasi bertingkat.
- Pengguna.
- Peran.
- Izin.
- Pengguna-peran.
- Peran-izin.
- Perangkat pengguna.
- API key.
- Konfigurasi organisasi.
- Penomoran dokumen.
- Hari libur.

### Aturan

- Email unik minimal dalam scope organisasi.
- User nonaktif tidak dapat login.
- Peran bawaan tidak dapat dihapus bila masih digunakan.
- Penomoran dokumen harus aman terhadap concurrency.
- Hari libur digunakan pada kalkulasi kalender kerja jika aturan SLA mengaktifkannya.
- API key hanya menampilkan secret penuh sekali saat dibuat.

---

## 8.2 Berkas, Tag, Field Kustom, Komentar

### Kebutuhan

- Upload berkas.
- Metadata file.
- Lampiran generik ke entitas.
- Tag generik.
- Field kustom.
- Nilai field kustom.
- Komentar entitas.

### Aturan

- File divalidasi MIME, ukuran, dan hak akses.
- Storage driver dapat `local` untuk hosting awal dan dapat dipindah ke S3-compatible.
- Lampiran tidak boleh membuka URL private tanpa authorization.
- Field kustom memiliki tipe data dan validation metadata.
- Komentar dapat dihapus sesuai policy tanpa menghilangkan audit bila dibutuhkan.

---

## 8.3 Penyedia

### Kebutuhan

- Kategori penyedia.
- Profil penyedia.
- Kontak.
- Multi-kategori.
- Penilaian kinerja.

### Aturan

- Penyedia digunakan bersama oleh pengadaan, kontrak, kalibrasi, dan pekerjaan eksternal.
- Penilaian tidak boleh mengubah histori transaksi lama.
- Status penyedia menentukan apakah dapat dipilih untuk transaksi baru.

---

## 8.4 Asset Registry

### Kebutuhan

- Kategori aset bertingkat.
- Merek.
- Model aset.
- Aset.
- Relasi aset.
- Lokasi.
- Penanggung jawab.
- Garansi.
- Nilai aset.
- Meter.
- Pembacaan meter.
- QR/barcode.
- Lampiran.
- Tag.
- Field kustom.

### Detail Aset

Halaman detail aset harus menjadi pusat informasi:

- Identitas.
- Kategori, merek, model.
- Nomor seri.
- QR/barcode.
- Status/kondisi.
- Lokasi saat ini.
- Penanggung jawab.
- Nilai.
- Garansi.
- Meter.
- Histori lokasi.
- Histori penanggung jawab.
- Keluhan.
- Perintah kerja.
- Preventive.
- Inspeksi.
- Kalibrasi.
- Sparepart terkait.
- Kontrak.
- Sertifikasi.
- Lampiran.
- Audit yang diizinkan.

### Aturan

- Perubahan lokasi tidak menghapus histori.
- Perubahan penanggung jawab tidak menghapus histori.
- Identifier unik mengikuti scope organisasi.
- Aset yang sudah memiliki transaksi historis tidak dihapus fisik.
- Aset dapat memiliki parent/child relation.
- Status aset dan kondisi aset diperlakukan sebagai konsep terpisah bila dibutuhkan.

---

## 8.5 Siklus Aset

### Mutasi

Flow:

```text
Draf → Diajukan → Menunggu Persetujuan → Disetujui → Diserahterimakan → Diterima → Selesai
```

Jalur alternatif:

```text
Diajukan → Ditolak
Draf/Diajukan → Dibatalkan
```

### Serah Terima

Harus menyimpan:

- Pihak asal.
- Pihak tujuan.
- Waktu.
- Daftar aset.
- Kondisi saat serah terima.
- Dokumen/foto bila ada.
- Penerima.
- Catatan.

### Penghapusan

Flow minimal:

```text
Draf → Diajukan → Persetujuan → Disetujui → Dieksekusi
```

Harus mendukung:

- Alasan.
- Kondisi.
- Nilai.
- Dokumen pendukung.
- Persetujuan.
- Audit.

---

## 8.6 Gudang, Suku Cadang, dan Stok

### Kebutuhan

- Gudang.
- Lokasi gudang.
- Kategori suku cadang.
- Suku cadang.
- Kompatibilitas suku cadang dengan aset/model.
- Kelompok.
- Stok per lokasi.
- Mutasi stok.
- Detail mutasi.
- Reservasi.
- Pemakaian suku cadang.

### Aturan

- Stok tidak diubah dengan update angka langsung dari UI.
- Semua perubahan stok harus memiliki transaksi mutasi.
- Mutasi stok harus atomik.
- Concurrency harus dikendalikan.
- Stok negatif ditolak kecuali organisasi mengaktifkan override dan user memiliki izin.
- Pemakaian pada perintah kerja harus menambah biaya pekerjaan bila harga tersedia.
- Reservasi harus memengaruhi stok tersedia, bukan stok fisik.
- Penyesuaian stok harus membutuhkan alasan.

---

## 8.7 SLA dan Keluhan

### Kebutuhan

- Tingkat layanan.
- Aturan SLA.
- Kategori keluhan.
- Keluhan.
- Riwayat status.
- Prioritas.
- Lampiran.
- Pelapor.
- Aset/lokasi terkait.
- Eskalasi.

### SLA

Harus dapat memperhitungkan:

- Prioritas.
- Kategori.
- Organisasi/unit/lokasi.
- Jam kerja.
- Hari libur.
- Target response.
- Target resolution.
- Eskalasi.

### Keluhan

Flow minimum:

```text
Baru → Ditinjau → Diterima → Diproses → Selesai → Ditutup
```

Jalur alternatif:

```text
Baru/Ditinjau → Ditolak
Baru/Ditinjau → Dibatalkan
```

Keluhan dapat menghasilkan satu atau lebih PerintahKerja sesuai rule bisnis.

---

## 8.8 Perintah Kerja

PerintahKerja adalah transaksi operasional utama.

### Jenis

- Korektif.
- Preventif.
- Inspeksi.
- Kalibrasi.
- Pekerjaan umum.
- Pekerjaan dari vendor bila diperlukan.

### Flow

```text
Draf
↓
Terjadwal / Ditugaskan
↓
Diterima
↓
Dikerjakan
├─ MenungguSukuCadang
├─ MenungguPenyedia
├─ Dijeda
↓
MenungguVerifikasi
↓
Selesai
↓
Ditutup
```

Jalur alternatif:

```text
Draf/Terjadwal/Ditugaskan → Dibatalkan
```

### Data Operasional

- Nomor.
- Jenis.
- Sumber.
- Prioritas.
- Keluhan sumber.
- Aset.
- Lokasi.
- Teknisi.
- Jadwal.
- Waktu mulai/selesai.
- Waktu kerja.
- Downtime.
- Diagnosis.
- Tindakan.
- Kode kegagalan.
- Root cause.
- Checklist.
- Sparepart.
- Biaya.
- Lampiran.
- Verifikasi.
- Histori status.

### KPI

Sistem harus dapat menghitung:

- Response time.
- Resolution time.
- Downtime.
- MTTR.
- MTBF berdasarkan data yang memenuhi syarat.
- SLA compliance.
- Planned vs unplanned maintenance.
- Biaya per pekerjaan.
- Biaya per aset.
- Produktivitas teknisi tanpa menggunakan metrik yang menyesatkan.

---

## 8.9 Checklist, Preventive, dan Inspeksi

### Checklist

Templat harus:

- Versionable secara logis.
- Memiliki urutan.
- Memiliki tipe jawaban.
- Mendukung required.
- Mendukung range/min/max bila relevan.
- Mendukung foto/catatan bila dibutuhkan.

### Preventive

Rencana dapat berbasis:

- Kalender.
- Meter.
- Kombinasi kalender dan meter.
- Interval tertentu.

Scheduler harus dapat menghasilkan jadwal dan PerintahKerja tanpa duplikasi.

### Inspeksi

- Menggunakan templat.
- Menyimpan hasil aktual.
- Temuan dapat membuat keluhan atau PerintahKerja.
- Hasil tidak boleh berubah diam-diam setelah finalisasi.

---

## 8.10 Kalibrasi

### Kebutuhan

- Jenis kalibrasi.
- Rencana.
- Jadwal.
- Pelaksanaan.
- Titik ukur.
- Hasil titik ukur.
- Penyedia.
- Sertifikat.
- Status hasil.
- Tanggal berlaku.
- Next due date.

### Aturan

- Kalibrasi yang selesai dapat menghitung jadwal berikutnya.
- Sertifikat harus dapat dilampirkan.
- Hasil final yang sudah disahkan harus memiliki audit pada koreksi.
- Reminder jatuh tempo dapat dikonfigurasi.
- Aset dengan kalibrasi expired dapat ditandai pada dashboard tanpa otomatis mengubah status aset kecuali rule organisasi menentukan demikian.

---

## 8.11 Perencanaan, Anggaran, dan Pengadaan

### Anggaran

- Anggaran periode.
- Pos anggaran.
- Transaksi penggunaan/komitmen.
- Nilai tersedia.
- Validasi overspend sesuai kebijakan.

### Usulan Aset

- Pengajuan kebutuhan.
- Alasan.
- Unit/lokasi.
- Estimasi.
- Penilaian.
- Prioritas.
- Persetujuan.

### Rencana Pengadaan

Mengompilasi usulan menjadi rencana yang dapat dilanjutkan ke pembelian.

### Procurement Flow

```text
Usulan
↓
Penilaian
↓
RencanaPengadaan
↓
PermintaanPembelian
↓
Persetujuan
↓
PermintaanPenawaran
↓
PenawaranPenyedia
↓
Evaluasi
↓
PesananPembelian
↓
Penerimaan
↓
TagihanPenyedia
↓
Pembayaran
```

### Aturan

- Total dihitung server-side.
- Perubahan kuantitas/harga selalu dihitung ulang.
- Penerimaan parsial harus didukung.
- PO tidak boleh menerima kuantitas melebihi batas tanpa rule yang jelas.
- Penerimaan aset dapat menjadi sumber registrasi aset.
- Semua nilai uang menggunakan decimal yang konsisten, bukan float.

---

## 8.12 Kontrak dan Layanan Penyedia

### Kebutuhan

- Kontrak.
- Aset dalam kontrak.
- Layanan kontrak.
- Nilai.
- Periode.
- SLA kontrak.
- Dokumen.
- Reminder expiry.

### Aturan

- Kontrak expired tidak boleh dipilih untuk transaksi baru jika policy melarang.
- Histori kontrak tetap dapat dibaca.
- Pekerjaan vendor dapat dikaitkan dengan kontrak.

---

## 8.13 Kepatuhan dan Sertifikasi

### Kebutuhan

- Standar kepatuhan.
- Persyaratan.
- Status kepatuhan aset.
- Sertifikasi.
- Masa berlaku.
- Lampiran bukti.
- Integrasi standar eksternal.

### Aturan

Sistem tidak meng-hardcode satu regulator. Standar dapat berasal dari:

- Internal perusahaan.
- Pemerintah.
- Industri.
- Quality management.
- Safety.
- Calibration standard.
- Regulator spesifik industri.

---

## 8.14 Approval Engine

Approval bersifat generik dan dapat digunakan oleh:

- Mutasi aset.
- Penghapusan.
- Permintaan pembelian.
- Pengadaan.
- Biaya perbaikan.
- Penyesuaian stok.
- Usulan.
- Transaksi lain.

### Kebutuhan

- Alur persetujuan.
- Tahap.
- Urutan.
- Approver berdasarkan role/user/unit bila dikonfigurasi.
- Permintaan persetujuan.
- Keputusan.
- Catatan.
- Histori.
- Pembatalan.
- Expiry/escalation jika diperlukan.

### Aturan

- Tidak boleh self-approval bila konfigurasi melarang.
- Keputusan bersifat append-oriented.
- Entitas sumber hanya berubah ke status final setelah keputusan memenuhi rule.
- Approval tidak boleh bergantung pada nama tabel yang di-hardcode di banyak tempat.

---

## 8.15 Notifikasi dan Eskalasi

Channel awal:

- In-app.
- Email bila konfigurasi tersedia.

Channel masa depan dapat ditambahkan tanpa mengubah domain utama.

Event notifikasi:

- Penugasan.
- Keluhan baru.
- SLA mendekati batas.
- SLA terlewati.
- Jadwal preventive.
- Kalibrasi jatuh tempo.
- Kontrak berakhir.
- Sertifikasi berakhir.
- Persetujuan menunggu.
- Stok minimum.
- Pengadaan berubah status.

Preferensi pengguna harus dihormati kecuali notifikasi sistem yang wajib.

---

## 8.16 Integrasi, Webhook, Outbox, dan Idempotensi

### API

Base path:

```text
/api/v1
```

### Prinsip

- Versioned.
- Authenticated.
- Tenant-aware.
- Rate limited.
- Idempotent untuk operasi kritis.
- Error response konsisten.
- Correlation ID bila diperlukan.
- Tidak membocorkan exception internal.

### Integrasi Eksternal

Harus mendukung:

- Konfigurasi koneksi.
- Mapping.
- Sinkronisasi.
- Status.
- Retry.
- Error log yang aman.
- Manual retry berdasarkan izin.

### Webhook

- Signature.
- Retry.
- Status delivery.
- Payload log terbatas.
- Secret tidak ditampilkan kembali.
- Delivery tidak dilakukan di transaksi utama.

### Outbox

Event yang harus dikirim keluar ditulis ke database dalam transaction yang sama dengan perubahan bisnis, kemudian diproses asynchronous.

---

## 8.17 Offline Sync / PWA

Tujuan utama adalah mendukung teknisi pada area koneksi tidak stabil.

Scope offline awal:

- Data penugasan milik teknisi.
- Ringkasan aset terkait.
- Checklist yang diperlukan.
- Draft catatan pekerjaan.
- Draft foto metadata sebelum upload.
- Queue perubahan.

Ketentuan:

- Setiap mutasi offline memiliki identifier.
- Sync harus idempotent.
- Conflict tidak ditimpa diam-diam.
- Server tetap sumber kebenaran.
- Data sensitif yang disimpan lokal diminimalkan.
- Logout membersihkan data lokal yang seharusnya tidak bertahan.

---

## 8.18 Laporan dan Dashboard

### Dashboard Operasional

- Keluhan terbuka.
- Perintah kerja per status.
- Pekerjaan overdue.
- SLA.
- Jadwal hari ini.
- Aset bermasalah.
- Stok minimum.
- Kalibrasi jatuh tempo.
- Preventive jatuh tempo.

### Dashboard Manajemen

- Nilai aset.
- Distribusi kondisi.
- Biaya pemeliharaan.
- Downtime.
- MTTR/MTBF.
- Kepatuhan.
- Kinerja SLA.
- Pengadaan.
- Anggaran.
- Kontrak jatuh tempo.
- Tren failure.
- Vendor performance.

### Laporan

- Filter tersimpan.
- Export sesuai izin.
- Query berat diproses async.
- Hasil laporan tidak boleh menampilkan tenant lain.
- Angka laporan harus dapat ditelusuri ke sumber transaksi.

---

## 8.19 SaaS dan Langganan

### Kebutuhan

- Paket.
- Fitur paket.
- Relasi paket-fitur.
- Langganan organisasi.
- Tagihan langganan.
- Pembayaran langganan.

### Aturan

- Entitlement diperiksa di backend.
- Expired subscription mengikuti grace policy.
- Plan limit tidak hanya disembunyikan di UI.
- Perubahan paket tidak menghapus data tenant.
- Billing provider dibuat melalui abstraction agar dapat diganti.

---

## 9. Search, Filter, dan Data Table

Semua modul daftar utama wajib memiliki pola konsisten:

- Search.
- Filter.
- Sort.
- Pagination.
- Reset filter.
- Saved filter hanya jika bernilai.
- Empty state.
- Loading state.
- Error state.
- Permission-aware actions.

Search server-side digunakan untuk dataset besar.

Query string menyimpan state filter yang layak agar halaman dapat dibagikan/refreshed.

---

## 10. QR dan Identifikasi Aset

QR tidak menyimpan data sensitif secara langsung.

Flow:

```text
Scan QR
↓
Resolve token/kode aset
↓
Authorization
↓
Detail aset atau quick action
```

Quick action sesuai izin:

- Lihat aset.
- Buat keluhan.
- Mulai pekerjaan yang ditugaskan.
- Inspeksi.
- Mutasi/serah terima bila workflow mengizinkan.

---

## 11. File dan Media

- File name asli disimpan sebagai metadata.
- Storage name tidak harus sama dengan nama asli.
- Validasi MIME dilakukan server-side.
- Batas ukuran dikonfigurasi.
- File private diakses melalui endpoint/temporary URL yang terotorisasi.
- Thumbnail boleh dibuat async.
- Upload gagal tidak boleh meninggalkan transaksi bisnis setengah selesai.
- Lampiran dapat digunakan lintas modul melalui relasi generik.

---

## 12. Queue, Cron, dan Hosting Niagahoster

Produksi awal tidak boleh bergantung pada proses daemon permanen.

Default:

```env
QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
```

Cron menangani:

- Laravel scheduler.
- Queue worker pendek dengan `--stop-when-empty`.
- Reminder.
- SLA escalation.
- Jadwal preventive.
- Kalibrasi due.
- Contract/certificate expiry.
- Outbox.
- Webhook retry.
- Report generation.
- Cleanup.

Ketika pindah ke VPS, queue dapat dipindah ke Redis/Horizon tanpa mengubah business contract.

---

## 13. Non-Functional Requirements

### 13.1 Security

- CSRF untuk web.
- XSS-safe rendering.
- SQL injection dicegah melalui query binding/ORM.
- Rate limit login/API.
- Password hashing Laravel.
- Session regeneration setelah login.
- API secret di-hash bila memungkinkan.
- Sensitive config hanya di environment.
- Authorization di backend.
- Audit security-sensitive actions.
- Secure cookie pada HTTPS.
- File authorization.
- Mass assignment terkendali.

### 13.2 Performance

- Index mengikuti query nyata.
- Pagination.
- Eager loading terkontrol.
- Tidak ada N+1.
- Caching hanya untuk data yang aman dan punya invalidation jelas.
- Report berat async.
- Export besar tidak dilakukan dalam request biasa.
- Image upload dapat dikompresi di client tanpa mengubah bukti asli bila original diwajibkan.

### 13.3 Reliability

- Transaction untuk operasi multi-write.
- Idempotency pada endpoint sensitif.
- Queue retry terkendali.
- Dead/final-failed state dapat ditinjau.
- Webhook retry.
- Backup database di luar aplikasi.
- Restore procedure terdokumentasi.

### 13.4 Accessibility

- Keyboard navigation.
- Focus state jelas.
- Contrast memadai.
- Label form eksplisit.
- Error tidak hanya dibedakan dengan warna.
- Touch target minimal 44x44 px untuk aksi utama mobile.

---

## 14. Aturan Frontend

- React menggunakan TypeScript.
- Component presentational tidak melakukan request tersembunyi.
- Form menggunakan pola konsisten: `useForm` Inertia, error ditampilkan dari `form.errors` milik server.
- Zod hanya dipakai bila klien harus memvalidasi tanpa server (mis. draft offline). Backend tetap sumber kebenaran, jadi aturan Zod tidak boleh menduplikasi `FormRequest` secara manual.
- State global hanya untuk state yang benar-benar lintas halaman.
- Hindari prop drilling berlebihan.
- Jangan membuat komponen generik sebelum ada minimal dua kebutuhan nyata.
- Data table memiliki standard component, tetapi kolom bisnis tetap didefinisikan per feature.
- Semua label UI Bahasa Indonesia.
- Tidak ada dark mode.

### 14.1 Struktur Feature

Satu folder per feature di `resources/js/features/NamaFitur/`. Berkas dibuat saat ada isinya, bukan sebagai kerangka kosong:

| Berkas | Dibuat bila | Isi |
|---|---|---|
| `pages/` | selalu | Halaman Inertia; nama halaman = `NamaFitur/Index`, `NamaFitur/Show`. |
| `api.ts` | feature punya endpoint sendiri | Satu objek `ruteNamaFitur` berisi seluruh URL feature. URL tidak boleh ditulis langsung di halaman. |
| `types.ts` | feature pemilik entitas | Tipe payload yang dikirim Resource backend. Satu entitas hanya boleh dideklarasikan di satu feature; feature lain mengimpornya. |
| `status.ts` | ada peta status/varian badge | Konstanta pemetaan status ke varian tampilan. |
| `components/` | ada komponen khusus feature | Komponen yang hanya dipakai feature ini. |
| `hooks/` | ada hook khusus feature | Hook yang hanya dipakai feature ini. |

Aturan tambahan:

- Tidak ada file barrel `index.ts` per feature; impor memakai jalur eksplisit (`@/features/NamaFitur/types`).
- Request non-Inertia memakai klien bersama `@/lib/http`, bukan instance HTTP per feature.
- Tipe `Props` halaman dan tipe tampilan yang hanya dipakai satu halaman tetap ditulis di halaman itu.

---

## 15. Out of Scope v1.0

Tidak menjadi requirement wajib tahap pertama:

- Native Android/iOS terpisah.
- AI predictive maintenance.
- Computer vision.
- IoT streaming realtime skala besar.
- Digital twin 3D.
- Marketplace vendor.
- Payroll teknisi.
- Full accounting/general ledger.
- Public anonymous asset database.

Fondasi integrasi tidak boleh menutup kemungkinan fitur tersebut pada versi berikutnya.

---

## 16. Acceptance Criteria Produk v1.0

Amanpoll v1.0 dinyatakan siap bila:

1. Multi-organisasi terisolasi dan lolos tenant isolation test.
2. Auth, RBAC, policy, audit, dan konfigurasi organisasi berjalan.
3. Asset registry dan histori lifecycle berfungsi.
4. Gudang/suku cadang menggunakan ledger mutasi stok.
5. Keluhan, SLA, dan PerintahKerja dapat berjalan end-to-end.
6. Preventive dapat menghasilkan jadwal/pekerjaan tanpa duplikasi.
7. Inspeksi dan kalibrasi menyimpan histori yang dapat diaudit.
8. Approval engine digunakan oleh transaksi yang membutuhkan approval.
9. Pengadaan berjalan dari usulan sampai penerimaan/tagihan sesuai scope.
10. Kontrak dan kepatuhan memiliki reminder jatuh tempo.
11. Notification, outbox, webhook, dan retry memiliki monitoring minimal.
12. Dashboard dan laporan menggunakan data transaksi sebenarnya.
13. PWA/responsive workflow teknisi usable pada layar 360 px.
14. Tidak ada dark mode.
15. Tidak ada N+1 pada halaman utama yang telah ditentukan.
16. Critical flow memiliki feature test.
17. Production build berjalan di Niagahoster Business.
18. Scheduler dan queue dapat berjalan melalui cron.
19. Backup/restore dan deployment procedure terdokumentasi.
20. Tidak ada TODO dummy, mock data produksi, atau route placeholder pada release.

---

## 17. Definition of Done per Fitur

Sebuah fitur baru tidak dianggap selesai hanya karena halaman tampil.

Minimal harus memiliki:

- Requirement terdefinisi.
- Authorization.
- Validation.
- Business rule.
- Transaction bila dibutuhkan.
- Audit bila kritis.
- Error handling.
- Loading/empty/error state.
- Responsive UI.
- Feature test backend.
- Test tenant isolation jika menyentuh data tenant.
- Dokumentasi singkat bila memiliki proses non-obvious.
- Tidak ada warning TypeScript/PHPStan yang diperkenalkan oleh perubahan tersebut.
