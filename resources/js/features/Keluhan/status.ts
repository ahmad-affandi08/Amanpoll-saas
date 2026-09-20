import type { PrioritasKeluhan, StatusKeluhan } from './types';

export const VARIAN_STATUS_KELUHAN: Record<
  StatusKeluhan,
  'default' | 'secondary' | 'destructive' | 'outline' | 'sukses' | 'perhatian' | 'netral' | 'proses'
> = {
  Baru: 'default',
  Ditinjau: 'perhatian',
  Diterima: 'secondary',
  Diproses: 'proses',
  Selesai: 'sukses',
  Ditutup: 'netral',
  Ditolak: 'destructive',
  Dibatalkan: 'outline',
};

export const VARIAN_PRIORITAS_KELUHAN: Record<
  PrioritasKeluhan,
  'default' | 'secondary' | 'destructive' | 'outline' | 'perhatian' | 'netral'
> = {
  Rendah: 'netral',
  Normal: 'secondary',
  Tinggi: 'perhatian',
  Kritis: 'destructive',
};
