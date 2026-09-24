# Runbook Pemulihan Amanpoll

Dokumen ini dipakai saat data hilang atau rusak. Ia ditulis untuk dibaca pada
hari yang buruk, jadi urutannya adalah urutan tindakan, bukan urutan penjelasan.

## 1. Apa yang dicadangkan

Perintah `cadangan:jalankan` berjalan tiap hari pukul 01:45. Ia membuat cadangan
di folder kerja lokal `storage/app/private/cadangan/`, menyalinnya ke **disk
luar** (`AMANPOLL_CADANGAN_DISK_LUAR`, bawaan produksi `cadangan_luar`: S3 atau
Cloudflare R2), lalu memangkas yang lewat retensi.

| Berkas | Isi |
| --- | --- |
| `basisdata-YYYYMMDD-HHMMSS.sql.gz` | seluruh basis data, termasuk routine dan trigger; tiap hari |
| `berkas-YYYYMMDD-HHMMSS.tar` | arsip **penuh** `storage/app/private` dan `storage/app/public`; seminggu sekali |
| `berkas-selisih-YYYYMMDD-HHMMSS.tar` | hanya berkas yang berubah sejak arsip penuh terakhir; hari-hari di antaranya |
| `berkas-YYYYMMDD-HHMMSS.tar.gz` | arsip penuh lama (sebelum FASE 45); tetap dikenali |

Dump basis data diambil dengan `--single-transaction`, sehingga konsisten tanpa
mengunci tabel yang sedang dipakai.

Arsip berkas adalah tar polos tanpa gzip: unggahan tenant kebanyakan foto dan
PDF yang sudah terkompresi. Selisih bersifat **kumulatif** terhadap arsip penuh
terakhir (bukan terhadap selisih kemarin), jadi pemulihan selalu cukup dua
arsip: yang penuh, lalu selisih terbaru sesudahnya. Jadwal arsip penuh diatur
`AMANPOLL_CADANGAN_BERKAS_PENUH_TIAP_HARI` (bawaan 7; isi 1 untuk arsip penuh
tiap hari). Selisih mengenali berkas dari waktu ubahnya (mtime); berkas yang
dihapus setelah arsip penuh akan muncul lagi bila dipulihkan.

Tiap berkas yang diunggah diverifikasi di tujuan — ukurannya dan SHA-256-nya
dibaca ulang — dan disertai `<nama>.sha256` berformat `sha256sum`. Salinan yang
tidak cocok dihapus dari tujuan dan perintah keluar dengan kode gagal. Cadangan
lokal yang belum sampai di luar dicoba lagi pada jalan berikutnya.

Retensi:

| Tempat | Lama | Variabel |
| --- | --- | --- |
| Disk luar | 30 hari | `AMANPOLL_CADANGAN_RETENSI_LUAR_HARI` |
| Lokal, sudah utuh di disk luar | 2 hari | `AMANPOLL_CADANGAN_RETENSI_LOKAL_HARI` |
| Lokal, belum sampai di luar / tanpa disk luar | 14 hari | `AMANPOLL_CADANGAN_RETENSI_HARI` |

Pemangkasan tidak pernah menghapus cadangan basis data terbaru, arsip penuh
terbaru beserta selisih sesudahnya, maupun arsip penuh yang menjadi dasar
selisih yang masih disimpan.

**Tanpa disk luar, cadangan tinggal di server yang sama dengan datanya.** Ia
menolong untuk kesalahan manusia dan kerusakan data, tetapi tidak untuk
kehilangan server. Setiap jalan lalu mencatat peringatan di log dan keluaran
perintah; jangan biarkan keadaan itu di produksi.

## 2. Memeriksa cadangan yang ada

```bash
php artisan cadangan:daftar          # folder lokal
php artisan cadangan:daftar --luar   # disk luar
```

Kolom umur dan ukuran keduanya penting. Cadangan berukuran jauh lebih kecil dari
biasanya adalah tanda dump terpotong, sekalipun perintahnya melaporkan sukses.
Arsip selisih memang kecil; yang dibandingkan adalah arsip penuh dengan arsip
penuh sebelumnya.

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

# Dari disk luar, walau ada salinan lokal (mis. salinan lokal dicurigai rusak)
php artisan cadangan:pulihkan --dari-luar
php artisan cadangan:pulihkan --dari-luar basisdata-20260101-014500.sql.gz
```

Bila tidak ada cadangan lokal — server baru setelah server lama hilang —
`cadangan:pulihkan` mengambil yang terbaru dari disk luar dengan sendirinya;
nama berkas yang tidak ada di lokal juga diambil dari sana. Unduhan diperiksa
terhadap ukuran dan `.sha256`-nya sebelum dipulihkan. Server baru butuh `.env`
dengan kredensial `AMANPOLL_CADANGAN_LUAR_*` yang sama; simpan salinannya di
luar server (pengelola kata sandi).

Di produksi perintah ini menuntut nama basis data diketik ulang. Itu disengaja.

Setelah pemulihan:

```bash
php artisan migrate        # bila cadangan lebih tua dari migrasi terakhir
php artisan optimize:clear
```

## 4. Memulihkan berkas unggahan

Arsip berkas dipulihkan dengan `tar`, bukan lewat artisan, karena ia menimpa
direktori yang sedang dipakai proses web. Urutannya: arsip penuh terbaru, lalu
arsip selisih terbaru **sesudah** arsip penuh itu (lihat `cadangan:daftar`).

Bila arsipnya hanya ada di disk luar, ambil dulu:

```bash
php artisan cadangan:ambil berkas-20260104-014500.tar berkas-selisih-20260108-014500.tar
```

Lalu:

```bash
cd storage/app
tar -xf private/cadangan/berkas-20260104-014500.tar
tar -xf private/cadangan/berkas-selisih-20260108-014500.tar
# Arsip lama sebelum FASE 45 tergzip: tar -xzf private/cadangan/berkas-….tar.gz
```

Periksa kepemilikan berkas setelahnya; `tar` mengembalikan mode, tetapi pemilik
mengikuti pengguna yang menjalankannya.

Tanpa aplikasi sama sekali, berkas di bucket dapat diunduh dengan alat S3 apa
pun (`rclone`, `aws s3 cp`) lalu diperiksa dengan `sha256sum -c <nama>.sha256`.

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
- **Unduhan dari disk luar tidak cocok.** Ukuran atau SHA-256 unduhan berbeda
  dari salinan luarnya; berkas sementaranya dibuang. Ulangi, dan bila tetap
  gagal pakai cadangan sebelumnya.
- **Disk luar tidak dapat dibaca.** Periksa `AMANPOLL_CADANGAN_LUAR_*`
  (endpoint, bucket, kunci) dan jalankan `php artisan config:clear`.

## 6. Yang tidak dijanjikan dokumen ini

- Tidak ada replikasi dan tidak ada point-in-time recovery. Jendela kehilangan
  data adalah jarak ke cadangan harian terakhir, sampai 24 jam.
- Tidak ada pemulihan otomatis. Setiap pemulihan dimulai oleh manusia yang
  membaca dokumen ini.
- Cadangan tidak dienkripsi oleh aplikasi. Ia berisi seluruh data tenant, jadi
  `storage/app/private` tidak boleh dapat diakses dari web, bucket disk luar
  harus privat dengan token yang hanya berhak atas bucket itu, dan salinan yang
  diunduh ke laptop diperlakukan sebagai data produksi.

## 7. Memastikan pemulihan benar-benar bekerja

Pemulihan diuji otomatis di `tests/Feature/Core/PemulihanCadanganTest.php`. Test
itu mencadangkan basis data, memulihkannya ke basis data lain, lalu membandingkan
isinya — termasuk membuktikan bahwa baris yang lahir *setelah* pencadangan tidak
ikut terbawa. Sebuah cadangan yang tidak pernah dipulihkan bukan cadangan; ia
hanya berkas.

Salinan luar diuji di `tests/Feature/Core/SalinanLuarCadanganTest.php`:
unggahan dan verifikasinya, pemangkasan lokal dan luar, gagal unggah yang tidak
menghapus salinan lokal, peringatan tanpa disk luar, dan pemulihan dari disk
luar.
