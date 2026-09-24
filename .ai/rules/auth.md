---
paths:
  - 'resources/js/features/Auth/**'
---

# Auth

## Tidak ada halaman login khusus Mode Lapangan
Teknisi dan Pelapor login lewat halaman Login dan LoginController yang sama dengan pengguna lain (tampilannya mengikuti DESIGN §37 Halaman Autentikasi, arah desain 2: hero gradien biru, kartu formulir mengapung, ikon 3D, font Plus Jakarta Sans lewat html[data-tampilan='autentikasi'] yang disetujui 24 September 2026), bukan halaman login terpisah. Layar 01 "Masuk" di papan mockup Mode Lapangan diberi cap TIDAK DIBANGUN. Yang berubah hanya tujuan pengalihan sesudah login (pengguna lapangan murni → /lapangan), dikerjakan di server (PRD 8.20).
