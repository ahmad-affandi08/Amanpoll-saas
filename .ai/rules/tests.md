---
paths:
  - 'tests/**'
---

# Tests

## Test dibuktikan dengan sabotase, bukan diasumsikan menjaga
Setelah menulis penjaga, rusak dengan sengaja hal yang dijaganya lalu pastikan testnya gagal. Sabotase yang lolos berarti coverage-nya bolong, bukan berarti kodenya aman.

Pelajaran nyata dari repo ini:
- Mencabut `lockForUpdate()` lolos seluruh test satu proses; butuh test multiproses untuk menangkapnya.
- Mencabut pemutus seri paginasi lolos test perilaku karena MySQL kebetulan stabil pada data kecil; yang benar-benar menjaga adalah pemeriksaan bahwa kueri-nya membawa `order by ... Id`.

Selain itu:
- Konteks organisasi ditetapkan eksplisit: `app(KonteksOrganisasi::class)->tetapkan($organisasi->Id)`.
- `php artisan make:test --phpunit {Nama}` tanpa direktori suite di namanya.
- Jalankan himpunan tersempit yang mencakup perubahan; jalankan seluruh suite sebelum commit.
