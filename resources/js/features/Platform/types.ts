/** Satu isian kredensial penyedia. Isian rahasia tidak pernah membawa nilainya (PRD 8.23). */
export interface IsianPenyedia {
  Kunci: string;
  Label: string;
  Rahasia: boolean;
  Wajib: boolean;
  Petunjuk: string | null;
  Pilihan: string[];
  Bawaan: string | null;
  /** Selalu `null` untuk isian rahasia. */
  Nilai: string | null;
  Tersimpan: boolean;
  /** Empat karakter terakhir rahasia yang cukup panjang; selain itu `null`. */
  Akhiran: string | null;
}

export interface PenyediaLayanan {
  Kode: string;
  Nama: string;
  Keterangan: string;
  Resmi: boolean;
  MendukungModeUji: boolean;
  DapatDiuji: boolean;
  Aktif: boolean;
  Utama: boolean;
  ModeUji: boolean;
  DiperbaruiPada: string | null;
  Isian: IsianPenyedia[];
}

export interface KategoriPenyediaLayanan {
  Kode: 'Pembayaran' | 'WhatsApp' | 'Email';
  Label: string;
  BolehBanyakAktif: boolean;
  Penyedia: PenyediaLayanan[];
}
