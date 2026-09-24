export interface UnitOrganisasi {
  Id: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  Jenis: string;
  Email: string | null;
  Telepon: string | null;
  Status: 'Aktif' | 'Nonaktif';
  /** Unit pengelola pemeliharaan (PRD 8.21), mis. IPSRS atau IT. */
  MengelolaAset: boolean;
  Urutan: number;
  DibuatPada: string;
}

/**
 * Satu pilihan unit pengelola, bentuk yang dikirim `OpsiUnitPengelola::daftar()` di server.
 * Petakan ke opsi Combobox dengan `opsiUnitPengelola()` dari `@/lib/pilihan`.
 */
export interface UnitPengelolaRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
