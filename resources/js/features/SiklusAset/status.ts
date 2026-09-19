import type { StatusPengajuanPenghapusanAset, StatusPermintaanMutasiAset, StatusSerahTerimaAset } from './types';

type VarianBadge = 'sukses' | 'info' | 'netral' | 'bahaya' | 'perhatian' | 'proses';

/* Pemetaan status ke varian Badge sesuai DESIGN.md 18 (Status Visual). */
export const VARIAN_BADGE_STATUS_MUTASI: Record<StatusPermintaanMutasiAset, VarianBadge> = {
  Draft: 'netral',
  Menunggu: 'perhatian',
  Disetujui: 'info',
  Ditolak: 'bahaya',
  Dibatalkan: 'netral',
  Selesai: 'sukses',
};

export const VARIAN_BADGE_STATUS_SERAH_TERIMA: Record<StatusSerahTerimaAset, VarianBadge> = {
  Diserahkan: 'perhatian',
  Diterima: 'sukses',
};

export const VARIAN_BADGE_STATUS_PENGHAPUSAN: Record<StatusPengajuanPenghapusanAset, VarianBadge> = {
  Draft: 'netral',
  Menunggu: 'perhatian',
  Disetujui: 'info',
  Ditolak: 'bahaya',
  Dibatalkan: 'netral',
  Selesai: 'sukses',
};
