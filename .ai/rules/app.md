---
paths:
  - 'app/**'
---

# App

## Folder yang ada adalah folder yang dipakai
Folder scaffold kosong sudah dihapus seluruhnya (dulu 295 folder berisi `.gitkeep` saja), jadi setiap folder di `app/` sekarang benar-benar berisi kode. Jangan menghidupkan kembali lapisan yang sengaja ditinggalkan.

Amanpoll memakai **Action tunggal di atas Eloquent langsung**. Tidak ada lapisan repository, dan DTO hanya dipakai di dua tempat. Jangan membuat `Domain/Repositories`, `Domain/Rules`, atau folder sejenis hanya karena pola itu lazim di proyek lain.

Jangan pula menambahkan `.gitkeep` untuk folder baru: bila belum ada kodenya, foldernya memang belum perlu ada.

Lihat pola yang benar-benar terpakai di `app/Domain/Persediaan/` atau `app/Domain/Pemeliharaan/`.
