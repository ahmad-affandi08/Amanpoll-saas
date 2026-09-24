import {
  BellRing,
  Check,
  CircleX,
  Hourglass,
  Search,
  UserCheck,
  Wrench,
  type LucideIcon,
} from 'lucide-react';
import type { StatusKeluhanPelapor, WarnaChip } from '@/features/Lapangan/types';

/** Perhentian perjalanan laporan (DESIGN.md 36.3): Dilaporkan → Ditinjau → Ditugaskan → Dikerjakan → Selesai. */
export const PERHENTIAN_LAPORAN = ['Dilaporkan', 'Ditinjau', 'Ditugaskan', 'Dikerjakan', 'Selesai'] as const;

/**
 * Status keluhan di tiap perhentian. Alur keluhan Pemeliharaan
 * (Baru → Ditinjau → Diterima → Diproses → Selesai → Ditutup) dipetakan satu-satu;
 * Ditutup ikut perhentian Selesai.
 */
const STATUS_PERHENTIAN: StatusKeluhanPelapor[][] = [
  ['Baru'],
  ['Ditinjau'],
  ['Diterima'],
  ['Diproses'],
  ['Selesai', 'Ditutup'],
];

/** Indeks perhentian (0–4) untuk sebuah status; -1 untuk Ditolak/Dibatalkan. */
export function indeksPerhentian(status: StatusKeluhanPelapor): number {
  return STATUS_PERHENTIAN.findIndex((kelompok) => kelompok.includes(status));
}

/** Status keluhan yang tercakup satu perhentian. */
export function statusPerhentian(indeks: number): StatusKeluhanPelapor[] {
  return STATUS_PERHENTIAN[indeks] ?? [];
}

interface TampilanStatus {
  label: string;
  warna: WarnaChip;
  ikon: LucideIcon;
}

/** Chip status dalam bahasa sehari-hari (papan pelapor: "Menunggu ditinjau", "Perlu konfirmasimu"). */
export function tampilanStatus(status: StatusKeluhanPelapor): TampilanStatus {
  switch (status) {
    case 'Baru':
      return { label: 'Menunggu ditinjau', warna: 'abu', ikon: Hourglass };
    case 'Ditinjau':
      return { label: 'Sedang ditinjau', warna: 'biru', ikon: Search };
    case 'Diterima':
      return { label: 'Teknisi ditugaskan', warna: 'biru', ikon: UserCheck };
    case 'Diproses':
      return { label: 'Sedang dikerjakan', warna: 'biru', ikon: Wrench };
    case 'Selesai':
      return { label: 'Perlu konfirmasimu', warna: 'kuning', ikon: BellRing };
    case 'Ditutup':
      return { label: 'Ditutup', warna: 'hijau', ikon: Check };
    case 'Ditolak':
      return { label: 'Ditolak', warna: 'merah', ikon: CircleX };
    case 'Dibatalkan':
      return { label: 'Dibatalkan', warna: 'abu', ikon: CircleX };
  }
}

export type TabLaporan = 'aktif' | 'konfirmasi' | 'selesai';

/**
 * Apakah laporan tampil di tab Laporan Saya (Aktif · Perlu konfirmasi · Selesai).
 * Laporan yang menunggu konfirmasi masih aktif bagi pelapor, jadi tampil di keduanya
 * (papan pelapor layar 09).
 */
export function diTabLaporan(status: StatusKeluhanPelapor, tab: TabLaporan): boolean {
  const selesai = status === 'Ditutup' || status === 'Ditolak' || status === 'Dibatalkan';
  if (tab === 'selesai') return selesai;
  if (tab === 'konfirmasi') return status === 'Selesai';
  return !selesai;
}

/** "Langkah 4 dari 5 · Dikerjakan". */
export function teksLangkah(status: StatusKeluhanPelapor): string {
  const indeks = indeksPerhentian(status);
  if (indeks < 0) return tampilanStatus(status).label;
  return `Langkah ${indeks + 1} dari ${PERHENTIAN_LAPORAN.length} · ${PERHENTIAN_LAPORAN[indeks]}`;
}

/** Nama depan untuk kalimat akrab: "Budi Santoso" → "Budi". */
export function namaDepan(nama: string | null | undefined): string {
  return nama?.trim().split(/\s+/)[0] ?? '';
}

/** Durasi ringkas "1j 29m", "42 mnt", "2 hari". */
export function durasiRingkas(
  dari: string | null | undefined,
  sampai: string | Date | null | undefined,
): string | null {
  if (!dari || !sampai) return null;
  const selisih = new Date(sampai).getTime() - new Date(dari).getTime();
  if (Number.isNaN(selisih) || selisih < 0) return null;
  const menit = Math.round(selisih / 60000);
  if (menit < 60) return `${menit} mnt`;
  const jam = Math.floor(menit / 60);
  if (jam < 48) return `${jam}j ${menit % 60}m`;
  return `${Math.round(jam / 24)} hari`;
}
