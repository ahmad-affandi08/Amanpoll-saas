---
paths:
  - 'resources/js/features/**'
---

# Features

## Struktur fitur frontend dan letak URL
PRD 14.1. Tiap fitur: `resources/js/features/<Fitur>/{pages,components,api.ts,types.ts,status.ts,hooks}`. Tidak ada barrel `index.ts`.

- **URL tidak pernah ditulis di halaman.** Seluruh URL satu fitur hidup di satu objek `ruteX` di `api.ts`. Termasuk jalur turunan seperti `/gudang/{id}/lokasi` — buat helper, jangan template literal di JSX.
- Halaman Inertia diselesaikan dari nama berkas `pages/<Nama>.tsx`; nama berkasnya mengikat, nama fungsinya tidak.
- Permintaan non-Inertia memakai `@/lib/http`, bukan fetch/axios langsung.
- Komponen dialog memegang state `buka` dan `useForm`-nya sendiri, dirender per baris lewat `DialogTrigger` — itu pola 70+ komponen yang sudah ada.
