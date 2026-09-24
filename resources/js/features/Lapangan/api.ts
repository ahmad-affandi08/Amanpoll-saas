/**
 * Seluruh URL Mode Lapangan (PRD 8.20). Halaman dan komponen tidak menulis URL sendiri.
 *
 * Bagian bersama diisi fondasi (39.03). Sub-objek `teknisi` diisi layar Teknisi dan `pelapor`
 * diisi layar Pelapor. Kunci `beranda`, `tugas`, `pindai`, `aset` (Teknisi) serta `beranda`,
 * `laporan`, `lapor`, `aset` (Pelapor) dibaca navigasi bawah KerangkaLapangan: URL-nya boleh
 * disesuaikan dengan rute yang benar-benar didaftarkan, tetapi kuncinya jangan diganti.
 */
export const ruteLapangan = {
  /** `lapangan.beranda`: server mengalihkan ke beranda sesuai mode. */
  beranda: '/lapangan',
  notifikasi: '/lapangan/notifikasi',
  akun: '/lapangan/akun',
  /** `lapangan.tampilan` (POST): pengguna campuran berpindah Mode Lapangan ⇄ dasbor. */
  tampilan: '/lapangan/tampilan',

  // Teknisi (C)
  teknisi: {
    beranda: '/lapangan/teknisi',
    tugas: '/lapangan/teknisi/tugas',
    pindai: '/lapangan/teknisi/pindai',
    aset: '/lapangan/teknisi/aset',
  },

  // Pelapor (D)
  pelapor: {
    beranda: '/lapangan/pelapor',
    laporan: '/lapangan/pelapor/laporan',
    lapor: '/lapangan/pelapor/lapor',
    aset: '/lapangan/pelapor/aset',
  },
};
