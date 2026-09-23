/** Palet grafik Amanpoll. */

/** Slot kategorikal, dipakai berurutan dan tidak pernah didaur ulang. */
export const PALET_KATEGORIKAL = ['#376FA6', '#F59E0B', '#16835B', '#C2413B'] as const;

/** Maksimal deret berwarna sebelum sisanya dilipat menjadi "Lainnya". */
export const BATAS_DERET = PALET_KATEGORIKAL.length;

/** Satu hue untuk magnitudo dan deret tunggal. */
export const HUE_UTAMA = '#27718F';

/** Ramp ordinal teknisi, terang ke gelap. */
export const RAMP_SEQUENTIAL = ['#3487A6', '#27718F', '#205B78', '#1D4663', '#17324D'] as const;

/** Warna status; maknanya dikunci dan tidak pernah dipakai sebagai "deret ke-4". */
export const WARNA_STATUS = {
  baik: '#16835B',
  perhatian: '#D97706',
  bahaya: '#C2413B',
  info: '#376FA6',
  netral: '#6E7A82',
} as const;

/** Abu-abu redaksi untuk deret latar pada grafik beraksen. */
export const WARNA_REDUP = '#B6BFC6';

export const WARNA_GRID = '#E7ECEF';
export const WARNA_SUMBU = '#5F6B73';
export const WARNA_PERMUKAAN = '#FFFFFF';

/** Label kondisi/status yang memang bermakna baik–buruk memakai token status. */
const PETA_STATUS: Record<string, string> = {
  Baik: WARNA_STATUS.baik,
  Patuh: WARNA_STATUS.baik,
  Tersedia: WARNA_STATUS.baik,
  'Masih berlaku': WARNA_STATUS.baik,
  'Memenuhi SLA': WARNA_STATUS.baik,
  'Respons tepat waktu': WARNA_STATUS.baik,
  Selesai: WARNA_STATUS.baik,
  PerluPerhatian: WARNA_STATUS.perhatian,
  'Segera jatuh tempo': WARNA_STATUS.perhatian,
  'Segera berakhir': WARNA_STATUS.perhatian,
  'Jatuh tempo hari ini': WARNA_STATUS.perhatian,
  Terjadwal: WARNA_STATUS.perhatian,
  Rusak: WARNA_STATUS.bahaya,
  TidakPatuh: WARNA_STATUS.bahaya,
  Terlambat: WARNA_STATUS.bahaya,
  Downtime: WARNA_STATUS.bahaya,
  'Melewati SLA': WARNA_STATUS.bahaya,
  'Respons terlambat': WARNA_STATUS.bahaya,
  'Lewat jatuh tempo': WARNA_STATUS.bahaya,
  'Lewat tanggal berakhir': WARNA_STATUS.bahaya,
  Dibatalkan: WARNA_STATUS.netral,
  BelumDiperiksa: WARNA_STATUS.netral,
};

/** Warna untuk satu irisan. */
export function warnaIrisan(label: string, indeks: number): string {
  return PETA_STATUS[label] ?? PALET_KATEGORIKAL[indeks % BATAS_DERET];
}

/** Apakah seluruh label pada grafik ini bermakna status. */
export function semuaBermaknaStatus(label: string[]): boolean {
  return label.length > 0 && label.every((satu) => satu in PETA_STATUS);
}
