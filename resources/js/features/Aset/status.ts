import type { Aset } from './types';

/* Pemetaan status Aset ke varian Badge sesuai DESIGN.md 18 (Status Visual). */
export const VARIAN_BADGE_STATUS_ASET: Record<Aset['Status'], 'sukses' | 'info' | 'netral' | 'bahaya'> = {
  Aktif: 'sukses',
  Dipinjam: 'info',
  Nonaktif: 'netral',
  Diarsipkan: 'netral',
  Rusak: 'bahaya',
};
