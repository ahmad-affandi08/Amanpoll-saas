export type StatusLangganan = 'UjiCoba' | 'Aktif' | 'Tenggang' | 'Kedaluwarsa' | 'Dibatalkan';

export type TipeBatasFitur = 'Boolean' | 'Angka';

export interface Entitlement {
  PaketId: string | null;
  NamaPaket: string | null;
  Status: StatusLangganan;
  LabelStatus: string;
  AksesPenuh: boolean;
  Fitur: Record<string, boolean>;
  Batas: Record<string, number | null>;
  BerakhirPada: string | null;
  UjiCobaSampai: string | null;
}

export interface DefinisiFitur {
  Kode: string;
  Nama: string;
  Deskripsi: string;
  TipeBatas: TipeBatasFitur;
  LabelTipeBatas: string;
  DiizinkanBawaan: boolean;
  BatasBawaan: number | null;
  SatuanBatas: string | null;
}

export interface TagihanItem {
  Id: string;
  Nomor: string;
  PeriodeMulai: string | null;
  PeriodeSelesai: string | null;
  JatuhTempo: string | null;
  Subtotal: number;
  Pajak: number;
  Total: number;
  Status: string;
  LabelStatus: string;
  DapatDibayar: boolean;
}

export interface InstruksiPembayaran {
  Penyedia: string;
  NomorTagihan: string;
  Instruksi: Record<string, unknown>;
}

export interface FiturPaketTersimpan {
  Kode: string;
  Diizinkan: boolean;
  BatasNilai: number | null;
}

export interface PaketItem {
  Id: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  HargaBulanan: number;
  HargaTahunan: number;
  MataUang: string;
  Aktif: boolean;
  JumlahLangganan: number;
  Fitur: FiturPaketTersimpan[];
}

export interface LanggananPlatformItem {
  Id: string;
  OrganisasiId: string;
  NamaOrganisasi: string | null;
  KodeOrganisasi: string | null;
  NamaPaket: string | null;
  Siklus: string;
  MulaiPada: string | null;
  BerakhirPada: string | null;
  UjiCobaSampai: string | null;
  StatusTersimpan: string;
  StatusEfektif: StatusLangganan;
  LabelStatusEfektif: string;
  BatalPada: string | null;
}

export interface PilihanRingkas {
  Id: string;
  Nama: string;
  Kode?: string;
}
