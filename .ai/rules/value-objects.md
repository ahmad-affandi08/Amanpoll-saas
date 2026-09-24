---
paths:
  - app/Domain/Platform/Domain/ValueObjects/KatalogPeranAwal.php
---

# Value Objects

## Peran lapangan tidak memegang izin Kelola
PELAPOR tidak memegang Keluhan.Kelola (membuat keluhan tidak butuh izin; ia hanya melihat keluhannya). TEKNISI tidak memegang PerintahKerja.Kelola maupun Keluhan.Kelola: teknisi yang ditugaskan sudah boleh melihat & mengerjakan tiketnya lewat PerintahKerjaPolicy. Bila suatu aksi lapangan ternyata mensyaratkan Kelola, pisahkan izinnya, jangan kembalikan Kelola. Katalog hanya dipakai saat pemasangan; tenant lama diubah lewat perintah artisan eksplisit yang teraudit.
