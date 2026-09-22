---
paths:
  - 'app/Domain/**/Infrastructure/Persistence/Models/**'
---

# Models

## Scope organisasi dan kode otomatis pada model
**Tenancy.** `ScopeOrganisasi` menyaring dengan `$model->qualifyColumn('OrganisasiId')`, jadi kolomnya selalu berkualifikasi dan aman dipakai bersama join. Jangan melewati scope ini. Di test, konteks harus ditetapkan: `app(KonteksOrganisasi::class)->tetapkan($organisasi->Id)`.

**Kode otomatis.** Model data induk yang punya kolom `Kode` memakai trait `App\Core\Penomoran\PunyaKodeOtomatis` dan mengisi `awalanKode()`. Kode terbit sendiri saat kosong (`GDG-0001`), dan kode yang sudah ada tidak pernah berubah.
- Timpa `kolomKode()` bila nama kolomnya bukan `Kode` (mis. `KodeAset`, `KodeModel`).
- Timpa `lingkupKode()` bila nomor urutnya per induk, bukan per organisasi.
- **Jangan** pasang trait ini pada entitas yang kodenya dicari harfiah oleh kode program — `Izin`, `FiturPaket`, `FiturPlatform`, `TahapPipeline`. Di sana `Kode` adalah kunci semantik dan tetap wajib diisi.
