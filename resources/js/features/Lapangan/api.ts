import { ruteInspeksi } from '@/features/Inspeksi/api';
import { ruteKolaborasi } from '@/features/Kolaborasi/api';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';

/** Jalur dengan kueri; nilai kosong dilewati. */
function denganKueri(jalur: string, isian: Record<string, string | undefined>): string {
  const kueri = new URLSearchParams();
  Object.entries(isian).forEach(([kunci, nilai]) => {
    if (nilai) kueri.set(kunci, nilai);
  });
  const teks = kueri.toString();
  return teks ? `${jalur}?${teks}` : jalur;
}

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
    /** Menyiapkan Mode Lapangan (unduh paket offline pertama kali). */
    siapkan: '/lapangan/teknisi/siapkan',
    sukuCadang: '/lapangan/teknisi/suku-cadang',
    tugasTab: (tab: string) => `/lapangan/teknisi/tugas?tab=${encodeURIComponent(tab)}`,
    tugasDetail: (id: string) => `/lapangan/teknisi/tugas/${id}`,
    kerjakan: (id: string, langkah?: string) =>
      langkah
        ? `/lapangan/teknisi/tugas/${id}/kerjakan?langkah=${encodeURIComponent(langkah)}`
        : `/lapangan/teknisi/tugas/${id}/kerjakan`,
    /** JSON: cari suku cadang + stok per gudang untuk satu tiket (policy `operate`). */
    cariSukuCadang: (id: string) => `/lapangan/teknisi/tugas/${id}/suku-cadang`,
    pindaiKode: (kode: string) => `/lapangan/teknisi/pindai?kode=${encodeURIComponent(kode)}`,
    pindaiAset: (asetId: string) => `/lapangan/teknisi/pindai?aset=${encodeURIComponent(asetId)}`,
    asetCari: (kata: string) => `/lapangan/teknisi/aset?cari=${encodeURIComponent(kata)}`,
    asetPilih: (asetId: string, kata?: string) =>
      kata
        ? `/lapangan/teknisi/aset?cari=${encodeURIComponent(kata)}&aset=${encodeURIComponent(asetId)}`
        : `/lapangan/teknisi/aset?aset=${encodeURIComponent(asetId)}`,
    riwayatAset: (asetId: string) => `/lapangan/teknisi/aset/${asetId}/riwayat`,
    konflik: (kunciOperasi: string) => `/lapangan/teknisi/konflik/${kunciOperasi}`,
    /** Endpoint domain yang dipakai layar teknisi (policy yang sama dengan dasbor). */
    analisisKegagalan: rutePerintahKerja.analisisKegagalan,
    reservasiSukuCadang: rutePerintahKerja.reservasiSukuCadang,
    berkas: ruteKolaborasi.berkas,
    laksanakanInspeksi: ruteInspeksi.laksanakan,
  },

  // Pelapor (D)
  pelapor: {
    beranda: '/lapangan/pelapor',
    laporan: '/lapangan/pelapor/laporan',
    /** `lapangan.pelapor.lapor`; `?aset=<AsetId>` adalah kontrak tetap hasil pindai QR. */
    lapor: '/lapangan/pelapor/lapor',
    aset: '/lapangan/pelapor/aset',
    /** Langkah lapor dengan isian awal: aset, kode QR yang terbaca, lokasi, atau kategori. */
    laporDengan: (isian: { aset?: string; kode?: string; lokasi?: string; kategori?: string }) =>
      denganKueri('/lapangan/pelapor/lapor', isian),
    asetDi: (lokasiId: string) => denganKueri('/lapangan/pelapor/aset', { lokasi: lokasiId }),
    laporanDetail: (id: string) => `/lapangan/pelapor/laporan/${id}`,
    terkirim: (id: string) => `/lapangan/pelapor/laporan/${id}/terkirim`,
    konfirmasi: (id: string) => `/lapangan/pelapor/laporan/${id}/konfirmasi`,
    terimaKasih: (id: string) => `/lapangan/pelapor/laporan/${id}/terima-kasih`,
    /** Keterangan dan foto tambahan memakai endpoint Kolaborasi yang ada (policy `view` pelapor). */
    komentar: ruteKolaborasi.komentar,
    berkas: ruteKolaborasi.berkas,
    fotoBerkas: ruteKolaborasi.berkasUnduh,
  },
};
