import type { LucideIcon } from 'lucide-react';
import { Building2, QrCode, Smartphone, Timer } from 'lucide-react';

/**
 * Salinan panel pemasaran halaman autentikasi (DESIGN.md 37).
 *
 * Seluruh kalimat di sini menjelaskan fitur yang benar-benar ada di produk
 * (PRD 8.7, 8.17, 8.20, 8.21, 8.4, 8.18). Tidak ada testimoni, logo pelanggan,
 * jumlah pengguna, atau angka pencapaian: repo belum punya satu pun yang
 * disetujui. Durasi trial tidak ditulis di sini; ia datang dari server.
 */

export interface ManfaatProduk {
  ikon: LucideIcon;
  judul: string;
  keterangan: string;
  /** Versi sangat singkat untuk pita manfaat di layar sempit. */
  ringkas: string;
}

export interface TiketContoh {
  nomor: string;
  judul: string;
  unit: string;
  status: string;
  varian: 'proses' | 'perhatian';
  sla: string;
}

export const manfaatProduk: ManfaatProduk[] = [
  {
    ikon: Timer,
    judul: 'Tiket kerja dengan SLA',
    keterangan: 'Keluhan menjadi tiket kerja, tenggat SLA-nya terpantau.',
    ringkas: 'Tiket kerja dan SLA',
  },
  {
    ikon: Smartphone,
    judul: 'Mode Lapangan di HP',
    keterangan: 'Teknisi dan pelapor bekerja dari HP, juga saat sinyal putus.',
    ringkas: 'Mode Lapangan di HP',
  },
  {
    ikon: QrCode,
    judul: 'QR di setiap aset',
    keterangan: 'Pindai label untuk melihat riwayat atau melapor kerusakan.',
    ringkas: 'QR di setiap aset',
  },
  {
    ikon: Building2,
    judul: 'Beberapa unit pengelola',
    keterangan: 'Teknik dan IT punya antrean, teknisi, dan gudang sendiri.',
    ringkas: 'Beberapa unit pengelola',
  },
];

/** Data rekaan untuk pratinjau produk; ditandai "Contoh tampilan" di layar, bukan data pelanggan. */
export const tiketContoh: TiketContoh[] = [
  {
    nomor: 'PK-0142',
    judul: 'AC ruang server tidak dingin',
    unit: 'IT',
    status: 'Dikerjakan',
    varian: 'proses',
    sla: 'Sisa 3 jam',
  },
  {
    nomor: 'PK-0139',
    judul: 'Servis berkala genset gedung B',
    unit: 'Teknik',
    status: 'Menunggu suku cadang',
    varian: 'perhatian',
    sla: 'Sisa 1 hari',
  },
];

export const isiPanelAutentikasi = {
  judul: 'Aset dan pemeliharaan, rapi dalam satu sistem',
  subjudul: 'Untuk pabrik, gedung, kampus, hotel, rumah sakit, dan organisasi lain yang merawat banyak aset.',
  pitaBaru: {
    label: 'Baru',
    teks: 'Mode Lapangan di HP dan Unit Pengelola',
  },
  pratinjau: {
    judul: 'Ringkasan pemeliharaan',
    catatan: 'Contoh tampilan',
    kpi: [
      { label: 'Tiket terbuka', nilai: '18' },
      { label: 'Lewat SLA', nilai: '2' },
      { label: 'Preventif minggu ini', nilai: '11' },
    ],
  },
  ajakan: {
    tombol: (durasiHari: number) => `Coba gratis ${durasiHari} hari`,
  },
} as const;
