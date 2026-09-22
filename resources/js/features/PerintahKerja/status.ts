import type { JenisPerintahKerja, PrioritasPerintahKerja, StatusPerintahKerja } from './types';

export const VARIAN_STATUS_PERINTAH_KERJA: Record<
  StatusPerintahKerja,
  'default' | 'secondary' | 'destructive' | 'outline' | 'sukses' | 'perhatian' | 'netral' | 'proses' | 'info'
> = {
  Draf: 'default',
  Terjadwal: 'info',
  Ditugaskan: 'secondary',
  Diterima: 'proses',
  Dikerjakan: 'proses',
  MenungguSukuCadang: 'perhatian',
  MenungguPenyedia: 'perhatian',
  Dijeda: 'perhatian',
  MenungguVerifikasi: 'info',
  Selesai: 'sukses',
  Ditutup: 'netral',
  Dibatalkan: 'destructive',
};

export const VARIAN_PRIORITAS_PERINTAH_KERJA: Record<
  PrioritasPerintahKerja,
  'default' | 'secondary' | 'destructive' | 'outline' | 'perhatian' | 'netral'
> = {
  Rendah: 'netral',
  Normal: 'secondary',
  Tinggi: 'perhatian',
  Kritis: 'destructive',
};

export const DAFTAR_PRIORITAS: PrioritasPerintahKerja[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];

export const DAFTAR_JENIS: JenisPerintahKerja[] = [
  'Korektif',
  'Preventif',
  'Inspeksi',
  'Kalibrasi',
  'Umum',
  'Vendor',
];
