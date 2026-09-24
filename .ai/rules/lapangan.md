---
paths:
  - 'resources/js/features/Lapangan/**'
---

# Lapangan

## Mode Lapangan wajib mengikuti papan acuan dan DESIGN §36
Layar Teknisi & Pelapor dibangun sedekat mungkin dengan docs/source-of-truth/mockup-mode-lapangan/{teknisi,pelapor}.png dan DESIGN §36 (gaya KAI Access: hero gradien, kartu apung, tiket bersobekan + rute jam, perhentian, tombol oranye lapangan-oranye-700, nav bawah dengan tombol tengah, ikon 3D Fluent di public/aset/3d). Pakai token lapangan-* saja. Dilarang: tabel/DataTable, sidebar, breadcrumb, grafik KPI, label HURUF KAPITAL berderet, kartu bergaris tepi kiri. Konteks multi-industri, bukan rumah sakit. Font Plus Jakarta Sans baru boleh dipasang setelah dependensinya disetujui; sebelum itu IBM Plex Sans. Kerangka di resources/js/layouts/KerangkaLapangan.tsx; offline memakai hook use-sinkronisasi-offline yang ada.
