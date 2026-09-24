import type { NamaIkon3D } from '@/components/shared/Ikon3D';

/**
 * Salinan halaman autentikasi (DESIGN.md 37).
 *
 * Kalimat hero menjelaskan fitur yang benar-benar ada (tiket kerja dengan SLA, Mode Lapangan
 * di HP, preventif terjadwal). Tidak ada testimoni, logo pelanggan, jumlah pengguna, atau
 * angka pencapaian: repo belum punya satu pun yang disetujui. Durasi trial tidak ditulis di
 * sini; ia datang dari server.
 */

export interface JenisTempat {
  ikon: NamaIkon3D;
  label: string;
}

/** Jenis tempat yang dirawat asetnya (PRD 1, 3.1: multi-industri). Jenis, bukan nama pelanggan. */
export const jenisTempat: JenisTempat[] = [
  { ikon: 'factory', label: 'Pabrik' },
  { ikon: 'office_building', label: 'Gedung' },
  { ikon: 'package', label: 'Gudang' },
  { ikon: 'school', label: 'Kampus' },
  { ikon: 'hotel', label: 'Hotel' },
  { ikon: 'hospital', label: 'Klinik' },
];

/**
 * Data rekaan untuk tiket ilustrasi hero. Ditandai "Contoh" di layar; bukan data pelanggan.
 * Bentuknya mengikuti tiket Mode Lapangan (DESIGN.md 36.3): nomor, status, rute jam, teknisi.
 */
export const tiketContoh = {
  nomor: 'PK/2026/0142',
  status: 'Dikerjakan',
  judul: 'Genset Gedung B tidak mau menyala',
  dilaporkan: '08.05',
  targetSla: '10.30',
  sisa: 'sisa 1j 25m',
  teknisi: 'Budi Santoso',
  inisial: 'BS',
  keterangan: 'Teknisi listrik · tiba 08.21',
  posisi: 'Di lokasi',
} as const;

export const isiAutentikasi = {
  judulBaris1: 'Tiap aset punya jadwal.',
  judulBaris2: 'Tiap keluhan punya tenggat.',
  paragraf:
    'Teknisi menerima tiket di HP, pengelola melihat pekerjaan bergerak di pabrik, gedung, gudang, sampai kampus.',
  judulJenisTempat: 'Untuk merawat aset di',
  catatanContoh: 'Contoh',
  kaki: 'Manajemen aset dan pemeliharaan',
  ajakan: {
    pertanyaan: 'Belum punya akun?',
    tombol: (durasiHari: number) => `Coba gratis ${durasiHari} hari`,
  },
} as const;
