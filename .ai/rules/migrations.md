---
paths:
  - 'database/migrations/**'
---

# Migrations

## Indeks mengikuti kueri yang benar-benar dijalankan
Ukur dulu, jangan menebak. `EXPLAIN` di atas tabel kosong tidak berarti apa-apa karena optimizer memilih dari statistik — isi tabelnya beberapa ribu baris lebih dulu.

- Daftar tenant menyaring `OrganisasiId` lalu mengurutkan kolom lain, jadi indeksnya `(OrganisasiId, <kolom urut>)` dengan urutan itu. Indeks yang menyelipkan kolom lain di antaranya tidak menutup pengurutan.
- Tambah indeks hanya bila filesort-nya benar-benar berbiaya. Tabel berisi puluhan baris tidak perlu indeks; indeks menganggur tetap menambah ongkos tulis.
- Pencarian `LIKE '%kata%'` tidak dapat dibantu indeks apa pun. Jangan menambah indeks untuk itu.
