import type { StatusGudang, StatusMutasiStok, StatusReservasiSukuCadang, StatusSukuCadang } from './types';

type VarianBadge = 'sukses' | 'info' | 'netral' | 'bahaya' | 'perhatian' | 'proses';

export const VARIAN_BADGE_STATUS_GUDANG: Record<StatusGudang, VarianBadge> = {
  Aktif: 'sukses',
  Nonaktif: 'netral',
};

export const VARIAN_BADGE_STATUS_SUKU_CADANG: Record<StatusSukuCadang, VarianBadge> = {
  Aktif: 'sukses',
  Nonaktif: 'netral',
};

export const VARIAN_BADGE_STATUS_MUTASI_STOK: Record<StatusMutasiStok, VarianBadge> = {
  Draft: 'netral',
  Diposting: 'sukses',
  Dibatalkan: 'bahaya',
};

export const VARIAN_BADGE_STATUS_RESERVASI: Record<StatusReservasiSukuCadang, VarianBadge> = {
  Aktif: 'perhatian',
  Dilepas: 'netral',
  Dipakai: 'sukses',
  Kadaluarsa: 'bahaya',
};
