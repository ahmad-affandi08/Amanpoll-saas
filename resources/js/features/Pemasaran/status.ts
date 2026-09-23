/*
 * Pemetaan status Pemasaran ke varian Badge sesuai DESIGN.md 18 (Status Visual).
 * Varian `default` dan `secondary` sama-sama abu-abu, sehingga "Aktif" dan "Nonaktif"
 * dulu hanya dibedakan kata; peta ini memberi setiap makna warnanya sendiri.
 */
export type VarianStatus = 'netral' | 'info' | 'proses' | 'perhatian' | 'sukses' | 'bahaya';

/** Varian untuk status yang maknanya sama di beberapa modul (draf, terbit, gagal, dst). */
const VARIAN_UMUM: Record<string, VarianStatus> = {
  Draf: 'netral',
  Review: 'perhatian',
  Diajukan: 'perhatian',
  Menunggu: 'perhatian',
  Tertunda: 'perhatian',
  Terjadwal: 'info',
  Diproses: 'proses',
  Berjalan: 'proses',
  Siap: 'info',
  Aktif: 'sukses',
  Terbit: 'sukses',
  Disetujui: 'sukses',
  Diberikan: 'sukses',
  Dibayar: 'sukses',
  Selesai: 'sukses',
  Dijeda: 'perhatian',
  Ditangguhkan: 'perhatian',
  Gagal: 'bahaya',
  GagalPermanen: 'bahaya',
  Ditolak: 'bahaya',
  Kadaluarsa: 'bahaya',
  Kedaluwarsa: 'bahaya',
  Dibatalkan: 'netral',
  Diarsipkan: 'netral',
  Berhenti: 'netral',
  BerhentiKondisi: 'netral',
};

/** Trial: yang masih disiapkan menunggu, yang berjalan diproses, yang teraktivasi/konversi berhasil. */
const VARIAN_TRIAL: Record<string, VarianStatus> = {
  Terdaftar: 'perhatian',
  Setup: 'perhatian',
  Aktif: 'proses',
  Diperpanjang: 'proses',
  Teraktivasi: 'sukses',
  Konversi: 'sukses',
  Kadaluarsa: 'bahaya',
  Dibatalkan: 'netral',
};

export function varianStatus(status: string): VarianStatus {
  return VARIAN_UMUM[status] ?? 'netral';
}

export function varianStatusTrial(status: string): VarianStatus {
  return VARIAN_TRIAL[status] ?? 'netral';
}

/** Saklar hidup/mati (modul, program, sequence, formulir). */
export function varianAktif(aktif: boolean): VarianStatus {
  return aktif ? 'sukses' : 'netral';
}

/** Tingkat alert growth. */
export function varianTingkatAlert(tingkat: string): VarianStatus {
  if (tingkat === 'Kritis') {
    return 'bahaya';
  }

  return tingkat === 'Peringatan' ? 'perhatian' : 'info';
}
