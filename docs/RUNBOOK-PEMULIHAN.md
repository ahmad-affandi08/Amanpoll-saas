# Runbook Pemulihan Amanpoll

Dokumen ini dipakai saat data hilang atau rusak. Ia ditulis untuk dibaca pada
hari yang buruk, jadi urutannya adalah urutan tindakan, bukan urutan penjelasan.

## 1. Apa yang dicadangkan

Perintah `cadangan:jalankan` berjalan tiap hari pukul 01:45 dan menghasilkan dua
berkas di `storage/app/private/cadangan/`:

| Berkas | Isi |
| --- | --- |
| `basisdata-YYYYMMDD-HHMMSS.sql.gz` | seluruh basis data, termasuk routine dan trigger |
| `berkas-YYYYMMDD-HHMMSS.tar.gz` | `storage/app/private` dan `storage/app/public` |

Dump basis data diambil dengan `--single-transaction`, sehingga konsisten tanpa
mengunci tabel yang sedang dipakai.

Cadangan yang lebih tua dari `AMANPOLL_CADANGAN_RETENSI_HARI` (bawaan 14 hari)
dihapus pada jalan berikutnya.

**Cadangan ini tinggal di server yang sama dengan datanya.** Ia menolong untuk
kesalahan manusia dan kerusakan data, tetapi tidak untuk kehilangan server.
Salinan luar diambil terpisah lewat hPanel Niagahoster, dan itu tidak diatur
oleh aplikasi ini.

## 2. Memeriksa cadangan yang ada

```bash
php artisan cadangan:daftar
```

Kolom umur dan ukuran keduanya penting. Cadangan berukuran jauh lebih kecil dari
biasanya adalah tanda dump terpotong, sekalipun perintahnya melaporkan sukses.

## 3. Memulihkan basis data

> Pemulihan **menimpa seluruh isi** basis data tujuan. Tidak ada undo.

Sebelum memulihkan, cadangkan dulu keadaan sekarang — sekalipun keadaan sekarang
rusak, ia satu-satunya salinan dari apa pun yang terjadi setelah cadangan
terakhir:

```bash
php artisan cadangan:jalankan --tanpa-berkas
```

Lalu pulihkan:

```bash
# Cadangan basis data terbaru, ke basis data koneksi aktif
php artisan cadangan:pulihkan

# Berkas tertentu
php artisan cadangan:pulihkan storage/app/private/cadangan/basisdata-20260101-014500.sql.gz

# Ke basis data lain, untuk memeriksa isi cadangan tanpa menyentuh yang hidup
php artisan cadangan:pulihkan --ke=amanpoll_periksa
```

Di produksi perintah ini menuntut nama basis data diketik ulang. Itu disengaja.

Setelah pemulihan:

```bash
php artisan migrate        # bila cadangan lebih tua dari migrasi terakhir
php artisan optimize:clear
```

## 4. Memulihkan berkas unggahan

Arsip berkas dipulihkan dengan `tar`, bukan lewat artisan, karena ia menimpa
direktori yang sedang dipakai proses web:

```bash
cd storage/app
tar -xzf private/cadangan/berkas-20260101-014500.tar.gz
```

Periksa kepemilikan berkas setelahnya; `tar` mengembalikan mode, tetapi pemilik
mengikuti pengguna yang menjalankannya.

## 5. Bila pemulihan gagal

`cadangan:pulihkan` berhenti dan menyebutkan alasannya. Tiga yang paling sering:

- **Biner `mysql` atau `mysqldump` tidak ditemukan.** Setel
  `AMANPOLL_CADANGAN_MYSQL` dan `AMANPOLL_CADANGAN_MYSQLDUMP` ke jalur lengkap.
  Pada shared hosting keduanya sering tidak ada di `PATH`.
- **Berkas kosong.** Cadangan itu tidak pernah berhasil. Pakai yang sebelumnya,
  dan cari penyebabnya di log — kegagalan pencadangan selalu ditulis ke sana.
- **Akses ditolak.** Kredensial di `.env` tidak punya hak menulis ke basis data
  tujuan. Pada shared hosting basis data harus sudah ada; aplikasi tidak
  membuatnya.

## 6. Yang tidak dijanjikan dokumen ini

- Tidak ada replikasi dan tidak ada point-in-time recovery. Jendela kehilangan
  data adalah jarak ke cadangan harian terakhir, sampai 24 jam.
- Tidak ada pemulihan otomatis. Setiap pemulihan dimulai oleh manusia yang
  membaca dokumen ini.
- Cadangan tidak dienkripsi. Ia berisi seluruh data tenant, jadi
  `storage/app/private` tidak boleh dapat diakses dari web, dan salinan yang
  diunduh ke laptop diperlakukan sebagai data produksi.

## 7. Memastikan pemulihan benar-benar bekerja

Pemulihan diuji otomatis di `tests/Feature/Core/PemulihanCadanganTest.php`. Test
itu mencadangkan basis data, memulihkannya ke basis data lain, lalu membandingkan
isinya — termasuk membuktikan bahwa baris yang lahir *setelah* pencadangan tidak
ikut terbawa. Sebuah cadangan yang tidak pernah dipulihkan bukan cadangan; ia
hanya berkas.
