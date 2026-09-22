---
paths:
  - 'app/Domain/**/Http/Controllers/**'
---

# Controllers

## Daftar tabel dipaginasi server lewat DaftarTersaring
Seluruh 26 halaman `DataTable` berjalan di server. Controller index yang memberi data ke `DataTable` memakai `App\Shared\Infrastructure\Persistence\DaftarTersaring`:

```php
$daftar = DaftarTersaring::untuk($request, Model::query()->with([...]))
    ->cari(['Kode', 'Nama'])
    ->urut(['Nama', 'Status'], bawaan: 'Nama')
    ->faset(['Status']);

return Inertia::render('X/Index', [
    'x' => XResource::collection($daftar->halaman()),
    'filter' => $daftar->filterBerlaku(),
]);
```

- Nama kolom tidak pernah datang dari request; permintaan hanya menyebut kunci yang disebutkan controller. Jangan meneruskan input ke `orderBy`/`where` langsung.
- `cari()` dan `urut()` hanya menerima kolom nyata. Kolom turunan relasi butuh `leftJoin` lebih dulu (lihat `StokSukuCadangController`) atau tidak diurutkan sama sekali.
- Penyaring yang tidak dapat dinyatakan sebagai satu kolom memakai `saring($kunci, $closure)`, mis. relasi banyak-ke-banyak.
- Daftar yang barisnya disusun sendiri controller (bukan lewat Resource) memakai `halamanTerpeta()`; yang perlu memakai barisnya dulu untuk agregat memakai `halaman()` lalu `DaftarTersaring::paginasi()`.
- **Angka ringkasan dihitung di basis data, bukan dari koleksi di tangan.** Menghitungnya dari satu halaman membuat angkanya menyusut tiap pengguna berpindah halaman.
- **Daftar sumber pemilih tidak ikut dipaginasi.** Kalau daftar yang sama juga mengisi dropdown (pemilih induk, cabang JSON `TagController`), kirim prop terpisah tanpa paginasi — kalau tidak, pilihan ke-26 hilang tanpa pesan galat.
