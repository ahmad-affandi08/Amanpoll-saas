---
paths:
  - 'app/Domain/Sinkronisasi/**'
---

# Sinkronisasi

## Backend Mode Lapangan: rute /lapangan tanpa salinan logika bisnis
Rute Mode Lapangan di bawah /lapangan, didaftarkan di app/Domain/Sinkronisasi/routes.php (domain baru di app/Domain butuh persetujuan). Controller hanya menyusun data layar dan memanggil Action domain pemiliknya (Pemeliharaan, Aset, Persediaan); otorisasi memakai policy yang sama dengan dasbor. Pengguna lapangan ditentukan dari penanda TampilanLapangan pada Peran (enum kosong/Teknisi/Pelapor; lapangan murni = seluruh perannya bertanda; Teknisi menang bila keduanya), bukan kode peran harfiah; pengalihan dasbor → /lapangan lewat middleware server. /offline/teknisi dialihkan ke /lapangan. Teknisi hanya meminta suku cadang, tidak pernah mengubah stok.
