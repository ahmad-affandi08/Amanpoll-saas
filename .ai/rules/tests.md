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
- Assertion yang dibandingkan dengan hitungan tabel yang tak pernah disemai hanya membandingkan 0 dengan 0. Semai dulu apa yang dijadikan pembanding, atau angkanya tidak menjaga apa pun.

## Jangan menaruh assertion sesudah expectException()
`expectException()` tidak menghentikan apa pun. Begitu kode yang diuji melempar, lemparannya melompat keluar dari method test, sehingga setiap baris sesudahnya tidak pernah dieksekusi — dan testnya tetap hijau.

Pola ini pernah menghampakan dua penjaga di repo ini: satu seharusnya memeriksa bahwa baris mass assignment lintas organisasi tidak tertulis, satu lagi bahwa peristiwa pemasaran masih ada setelah penghapusan ditolak. Ditolak di muka tidak sama dengan tidak tertulis; yang kedua itulah yang sebenarnya dijaga, dan justru itu yang tidak pernah jalan.

Bila masih ada yang perlu diperiksa sesudah lemparan, tangkap sendiri:

```php
try {
    $aksi();
    $this->fail('Seharusnya ditolak.');
} catch (AturanBisnisDilanggar) {
    // diharapkan
}

$this->assertFalse($barisnyaTertulis);
```

`tests/Architecture/AssertionSetelahExpectExceptionTest` menolak pola lamanya masuk lagi.

Selain itu:
- Konteks organisasi ditetapkan eksplisit: `app(KonteksOrganisasi::class)->tetapkan($organisasi->Id)`.
- `php artisan make:test --phpunit {Nama}` tanpa direktori suite di namanya.
- Jalankan himpunan tersempit yang mencakup perubahan; jalankan seluruh suite sebelum commit.
