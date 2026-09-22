---
paths:
  - 'resources/js/features/Dokumentasi/**'
---

# Dokumentasi

## Menambah halaman dokumentasi pengguna
Daftar halaman hidup di dua tempat dan keduanya harus sama persis, termasuk urutannya:
- `DokumentasiController::HALAMAN` menentukan alamat mana yang sah.
- `daftar-halaman.ts` menentukan judul, ringkasan, dan urutan sidebar.

Menambah di salah satu saja menghasilkan tautan sidebar yang berujung 404 atau alamat sah yang merender halaman kosong. Dijaga `DokumentasiTest::test_daftar_slug_php_sama_dengan_daftar_slug_react`.

Langkahnya: tambah slug di kedua daftar, buat `components/isi/<Nama>.tsx` yang mengekspor `daftarIsi` dan komponennya, lalu daftarkan di peta `isiPer` pada `pages/Index.tsx`.

Isi halaman ditulis dengan komponen di `components/Prosa.tsx`, bukan kelas Tailwind langsung. Urutan halaman adalah urutan pengerjaan, bukan abjad.
