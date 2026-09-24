---
paths:
  - 'resources/js/features/Lapangan/**'
---

# Lapangan

## Mode Lapangan wajib mengikuti papan acuan dan DESIGN §36
Layar Teknisi & Pelapor dibangun sedekat mungkin dengan docs/source-of-truth/mockup-mode-lapangan/{teknisi,pelapor}.png dan DESIGN §36 (gaya KAI Access: hero gradien, kartu apung, tiket bersobekan + rute jam, perhentian, tombol oranye lapangan-oranye-700, nav bawah dengan tombol tengah, ikon 3D Fluent di public/images/3d). Pakai token lapangan-* saja. Dilarang: tabel/DataTable, sidebar, breadcrumb, grafik KPI, label HURUF KAPITAL berderet, kartu bergaris tepi kiri. Konteks multi-industri, bukan rumah sakit. Font Plus Jakarta Sans (disetujui 24 September 2026) hanya untuk Mode Lapangan dan halaman autentikasi (DESIGN §37), dipasang lewat html[data-tampilan='lapangan'] oleh KerangkaLapangan dan html[data-tampilan='autentikasi'] oleh KerangkaAutentikasi; dasbor tetap IBM Plex Sans. Komponen ikon 3D bersama ada di resources/js/components/shared/Ikon3D.tsx (bukan di features/Lapangan); menambah ikon berarti menyalin PNG ke public/images/3d dan menambah namanya di NAMA_IKON_3D (dijaga Ikon3DTersediaTest). Kerangka di resources/js/layouts/KerangkaLapangan.tsx; offline memakai hook use-sinkronisasi-offline yang ada.

## Bayangan setipis mungkin
Mode Lapangan tidak memakai bayangan tebal (pemilik produk menolaknya, 24 September 2026). Pakai hanya token `shadow-lapangan-*` (nilainya sudah tipis, lihat DESIGN §36.4); jangan menulis bayangan berblur besar lewat kelas arbitrer atau `shadow-lg`. Cincin fokus/halo `0 0 0 Npx` tetap boleh.
