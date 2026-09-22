---
paths:
  - 'resources/js/features/**/pages/*.tsx'
---

# Pages

## Halaman DataTable memakai mode server
Halaman daftar menerima `Paginasi<T>` dan `FilterDaftar`, lalu:
```tsx
<DataTable columns={columns} data={x.data} server={{ meta: x.meta, filter }} ... />
```

Empat hal yang bila dilewat gagal tanpa gejala:

1. **Kolom turunan relasi diberi `enableSorting: false`.** Server hanya dapat mengurutkan kunci yang ada di daftar izin controller; membiarkan tombol urutnya berarti menawarkan tombol yang tidak melakukan apa-apa.
2. **Keadaan kosong besar hanya muncul bila memang belum ada isinya.** Ia menyembunyikan kotak cari, jadi pengguna yang kuerinya tidak cocok akan terkunci tanpa cara mengetik ulang. Pakai `x.meta.total === 0 && !adaPenyaringAktif(filter)`.
3. **Nilai faset adalah isi kolomnya, bukan labelnya.** Kolom boolean memakai `'1'`/`'0'`, bukan `'Aktif'`/`'Nonaktif'`.
4. **Penyaring di luar toolbar ikut membawa filter lain.** `router.get(rute, { ...filter, milikku: nilai, page: undefined })` — kalau tidak, memilih satu penyaring membuang pencarian dan urutan yang sedang berlaku.
