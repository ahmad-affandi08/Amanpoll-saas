# Laporan Audit: Analisis Folder dan Kode Unused (FASE 01 - FASE 10)

> **Catatan status — dokumen ini rekaman masa lalu, bukan keadaan sekarang.**
>
> Angka di bawah menggambarkan kondisi saat audit FASE 01–10 dijalankan.
> Sebagian besar temuannya sudah ditindaklanjuti. Keadaan yang terhitung
> sekarang:
>
> | Temuan | Saat audit | Sekarang |
> | --- | --- | --- |
> | Berkas repository | 112 mati dari 124 | 24 berkas tersisa seluruhnya |
> | Berkas DTO | 53 mati dari 68 | 2 berkas tersisa |
> | Folder `app/Http/Controllers/Domain/` nyasar | ada | sudah tidak ada |
> | Folder hanya berisi `.gitkeep` | 117 | 0 — sudah dibersihkan |
>
> Folder scaffold kosong sempat bertambah menjadi 295 sebelum akhirnya
> dihapus seluruhnya (335 folder di `app/` dan `tests/`, beserta 159 berkas
> `.gitkeep` yang tersisa). Sisa `app/Core` yang benar-benar terpakai ada
> sepuluh; berkas, notifikasi, persetujuan, integrasi, dan sinkronisasi
> ternyata tinggal di `app/Domain`, bukan di `app/Core` seperti yang dulu
> tertulis di ARSITEKTUR.md.
>
> Pelajarannya tetap berlaku: Amanpoll memakai Action tunggal di atas
> Eloquent, dan lapisan repository sengaja ditinggalkan.

| Metadata | Nilai |
| :--- | :--- |
| **Project** | Amanpoll SaaS |
| **Dokumen Acuan** | `docs/source-of-truth/TASK.md` (Gate 00.03) |
| **Cakupan Audit** | FASE 01 s.d. FASE 10 (9 Domain Selesai) |
| **Status Temuan** | Terverifikasi via Static Audit |

---

## 1. Ringkasan Eksekutif

Pada awal pengembangan proyek (FASE 00), generator scaffolding membuat struktur standar **28 subfolder identik** di setiap domain. 

Namun seiring implementasi bisnis dari **FASE 01 hingga FASE 10**, arsitektur aplikasi secara pragmatis berevolusi menggunakan pola **Single Action + Eloquent ORM**, sehingga banyak folder arsitektur bawaan dan file scaffold tidak pernah digunakan sama sekali.

### Metrik Temuan Utama:
* **Folder Kosong (Hanya `.gitkeep`)**: **117 folder** kosong tersebar di 9 domain.
* **Repository Unused (Dead Code)**: **112 file PHP** (56 pasang Interface & Implementasi Eloquent) tidak pernah dipanggil. Hanya **12 repository** yang aktif.
* **DTO Unused (Dead Code)**: **53 file PHP** DTO tidak pernah digunakan dari total 68 DTO yang ada.
* **Folder Nyasar (Accidental Directory)**: 1 folder di `app/Http/Controllers/Domain/`.

---

## 2. Kategori 1: Folder yang 100% Kosong (Hanya Berisi `.gitkeep`)

Setiap domain memiliki template 28 folder. Dari hasil audit, terdapat **13 folder** yang konsisten kosong di hampir semua domain yang sudah diselesaikan:

| Nama Subfolder | Status di 9 Domain | Mengapa Tidak Digunakan? |
| :--- | :---: | :--- |
| `Application/Commands/` | **Kosong di 9 domain** | Seluruh logika penulisan/perintah ditangani oleh `Application/Actions/`. |
| `Application/Queries/` | **Kosong di 9 domain** | Pembacaan dan filter data langsung dilakukan di Controller via Eloquent Builder. |
| `Domain/Policies/` | **Kosong di 9 domain** | **Redundan**. Seluruh file Policy otorisasi nyata disimpan di `Http/Policies/`. |
| `Domain/Events/` | **Kosong di 9 domain** | Belum ada Event domain khusus yang dipancarkan. |
| `Domain/Exceptions/` | **Kosong di 9 domain** | Exception domain sudah disatukan secara terpusat di `App\Shared\Domain\Exceptions\`. |
| `Domain/Rules/` | **Kosong di 9 domain** | Validasi aturan bisnis dibuat langsung di dalam `Http/Requests/`. |
| `Domain/ValueObjects/` | **Kosong di 9 domain** | Menggunakan tipe data primitif PHP & fitur casting bawaan Eloquent. |
| `Infrastructure/Persistence/QueryBuilders/` | **Kosong di 9 domain** | Scope query didefinisikan langsung pada Model. |
| `Infrastructure/Services/` | **Kosong di 9 domain** | Service diletakkan di `Application/Services/`. |
| `Jobs/` | **Kosong di 8 domain** | Hanya digunakan di domain `Notifikasi` (`KirimNotifikasiJob.php`). |
| `Listeners/` | **Kosong di 9 domain** | Satu-satunya listener yang ada diletakkan di `Infrastructure/Listeners/`. |
| `Notifications/` | **Kosong di 7 domain** | Hanya digunakan di `Platform` (`ResetKataSandiNotification`) dan `Notifikasi`. |
| `Support/` | **Kosong di 9 domain** | Tidak ada file pendukung/helper spesifik domain. |

---

## 3. Kategori 2: Analisis Repository (112 File Mati vs 12 File Aktif)

Semua repository di-binding di `app/Providers/RepositoryServiceProvider.php`. Namun saat dieksekusi di controller maupun action, hampir seluruhnya memanggil Model Eloquent secara langsung.

### A. Domain dengan Repository 100% TIDAK TERPAKAI (Dead Code):
1. **Domain `Aset` (FASE 08)**: 11 interface + 11 implementasi = **22 file mati**
   - `AsetRepository`, `GaransiAsetRepository`, `KategoriAsetRepository`, `MerekRepository`, `MeterAsetRepository`, `ModelAsetRepository`, `NilaiAsetRepository`, `PembacaanMeterAsetRepository`, `RelasiAsetRepository`, `RiwayatLokasiAsetRepository`, `RiwayatPenanggungJawabAsetRepository`.
2. **Domain `Persediaan` (FASE 10)**: 11 interface + 11 implementasi = **22 file mati**
   - `GudangRepository`, `LokasiGudangRepository`, `KategoriSukuCadangRepository`, `SukuCadangRepository`, `KompatibilitasSukuCadangRepository`, `KelompokSukuCadangRepository`, `StokSukuCadangRepository`, `MutasiStokRepository`, `DetailMutasiStokRepository`, `PemakaianSukuCadangRepository`, `ReservasiSukuCadangRepository`.
3. **Domain `SiklusAset` (FASE 09)**: 6 interface + 6 implementasi = **12 file mati**
   - `PermintaanMutasiAsetRepository`, `DetailMutasiAsetRepository`, `SerahTerimaAsetRepository`, `DetailSerahTerimaAsetRepository`, `PengajuanPenghapusanAsetRepository`, `DetailPenghapusanAsetRepository`.
4. **Domain `Penyedia` (FASE 07)**: 5 interface + 5 implementasi = **10 file mati**
   - `KategoriPenyediaRepository`, `PenyediaRepository`, `PenyediaKategoriRepository`, `KontakPenyediaRepository`, `PenilaianPenyediaRepository`.
5. **Domain `Persetujuan` (FASE 06)**: 4 interface + 4 implementasi = **8 file mati**
   - `AlurPersetujuanRepository`, `TahapPersetujuanRepository`, `PermintaanPersetujuanRepository`, `KeputusanPersetujuanRepository`.
6. **Domain `Platform` (FASE 02-04)**: 12 interface + 12 implementasi = **24 file mati**
   - Tidak terpakai: `OrganisasiRepository`, `UnitOrganisasiRepository`, `KategoriLokasiRepository`, `LokasiRepository`, `IzinRepository`, `PenggunaPeranRepository`, `PeranIzinRepository`, `PerangkatPenggunaRepository`, `KunciApiRepository`, `KonfigurasiOrganisasiRepository`, `NomorDokumenRepository`, `HariLiburRepository`.
   - **Terpakai (2)**: `PenggunaRepository`, `PeranRepository`.
7. **Domain `IntegrasiAudit` (FASE 05)**: 4 interface + 4 implementasi = **8 file mati**
   - Tidak terpakai: `KotakKeluarPeristiwaRepository`, `KunciIdempotensiRepository`, `PanggilanBalikWebRepository`, `PengirimanPanggilanBalikWebRepository`.
   - **Terpakai (2)**: `CatatanAuditRepository`, `CatatanAksesRepository`.
8. **Domain `Notifikasi` (FASE 06)**: 3 interface + 3 implementasi = **6 file mati**
   - Tidak terpakai: `TemplatNotifikasiRepository`, `PreferensiNotifikasiRepository`, `EskalasiTingkatLayananRepository`.
   - **Terpakai (1)**: `NotifikasiRepository`.

### B. Domain yang Menggunakan Repository:
* **Domain `Kolaborasi` (FASE 05)**: 7 repository aktif digunakan di Action (`BerkasRepository`, `LampiranEntitasRepository`, `TagRepository`, `EntitasTagRepository`, `DefinisiKolomKustomRepository`, `NilaiKolomKustomRepository`, `KomentarEntitasRepository`).

---

## 4. Kategori 3: Analisis Data Transfer Objects (DTO)

Dari **68 file DTO** di folder `Application/DTO/`, sebanyak **53 file (78%) tidak pernah dipakai**.

### Penyebab:
Controller memvalidasi input via FormRequest, kemudian langsung mengoper `$request->validated()` (berupa array terstruktur) ke dalam method Action `jalankan(array $data)`. Pola ini membuat class DTO terlewati (*bypassed*).

---

## 5. Kategori 4: Folder Nyasar / Salah Tempat

Terdapat folder kosong sisa eksekusi artisan/script yang tidak sengaja terbuat di dalam `app/Http/Controllers/`:
```text
app/Http/Controllers/Domain/Pemeliharaan/Http/Controllers/
```
Folder ini kosong dan harus dihapus agar struktur namespace Laravel tetap bersih.

---

## 6. Rekomendasi Tindakan (Action Plan)

Sesuai aturan `TASK.md` (Gate 00.03: *"Hapus scaffold dummy yang tidak akan digunakan"*):

### Opsi A (Pembersihan Parsial / Aman - Direkomendasikan Sekarang):
1. **Hapus folder nyasar**: Hapus direktori `app/Http/Controllers/Domain/`.
2. **Hapus folder-folder kosong `.gitkeep`**:
   - Hapus `Application/Commands/` dan `Application/Queries/` di semua domain.
   - Hapus `Domain/Policies/` (karena duplikat dengan `Http/Policies/`).
   - Hapus `Domain/Events/`, `Domain/Rules/`, `Domain/ValueObjects/`, `Infrastructure/Persistence/QueryBuilders/`, `Infrastructure/Services/`, dan `Support/`.
3. **Hasil**: Repositori menjadi bersih, ringkas, dan tidak membingungkan developer saat navigasi tree folder.

### Opsi B (Pembersihan Total / Arsitektur Bersih):
1. Jalankan Opsi A.
2. Hapus **56 pasang Repository mati** dan unbind dari `RepositoryServiceProvider.php`.
3. Hapus **53 DTO mati**.
4. Standarkan arsitektur proyek menjadi **Action + Eloquent Pattern** resmi, yang lebih cepat dikembangkan dan lebih ramah Laravel.
