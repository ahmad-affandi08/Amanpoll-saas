---
paths:
  - 'app/Domain/**/Http/Requests/**'
---

# Requests

## Kolom Kode nullable pada data induk berkode otomatis
Bila modelnya memakai `PunyaKodeOtomatis`, aturan `Kode` di FormRequest adalah `nullable`, bukan `required` — kolomnya memang boleh dikosongkan supaya sistem yang mengisinya.

Action yang memeriksa keunikan kode harus tahan nilai kosong:
```php
$ada = filled($data['Kode'] ?? null) && Model::query()->where('Kode', $data['Kode'])->exists();
```
dan meneruskan `'Kode' => $data['Kode'] ?? null`.

Pengecualian tetap `required`: `Izin`, `FiturPaket`, `FiturPlatform`, `TahapPipeline` — kodenya dicari harfiah oleh kode program.
