---
paths:
  - 'app/**'
---

# App

## Jangan menyimpulkan konvensi dari folder scaffold kosong
295 folder di `app/` hanya berisi `.gitkeep` — sisa generator scaffolding di awal proyek. Di antaranya `Domain/Repositories`, `Domain/Rules`, `Domain/Policies`, `Jobs`, `Support`, `Notifications` di hampir tiap domain.

Keberadaannya **bukan** tanda bahwa polanya dipakai. Amanpoll memakai Action tunggal di atas Eloquent langsung; lapisan repository sengaja ditinggalkan. Jangan membuat repository atau DTO baru hanya karena foldernya ada.

Lihat pola domain yang benar-benar terpakai di `app/Domain/Persediaan/` atau `app/Domain/Pemeliharaan/`, bukan di daftar folder.
