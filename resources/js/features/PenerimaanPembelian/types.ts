export type KondisiPenerimaan = 'Baik' | 'RusakRingan' | 'Rusak';

export interface DetailPenerimaanPembelian {
  Id: string;
  DetailPesananPembelianId: string | null;
  Deskripsi?: string | null;
  SukuCadangId: string | null;
  JumlahDipesan: string;
  JumlahDiterima: string;
  JumlahDitolak: string;
  Kondisi: KondisiPenerimaan | null;
  NomorSeri: string[];
  Catatan: string | null;
}

export interface PenerimaanPembelian {
  Id: string;
  Nomor: string;
  PesananPembelianId: string;
  NomorPesananPembelian?: string | null;
  NamaPenyedia?: string | null;
  GudangId: string | null;
  NamaGudang?: string | null;
  TanggalTerima: string;
  NomorSuratJalan: string | null;
  NamaPenerima?: string | null;
  Status: string;
  Catatan: string | null;
  JumlahItem?: number;
  Detail?: DetailPenerimaanPembelian[];
  DibuatPada: string;
}
