export type StatusPermintaanPembelian = 'Draft' | 'MenungguPersetujuan' | 'Disetujui' | 'Ditolak';

export type PrioritasPermintaanPembelian = 'Rendah' | 'Normal' | 'Tinggi' | 'Mendesak';

export type JenisItemPengadaan = 'Aset' | 'SukuCadang' | 'Jasa' | 'Lainnya';

export interface DetailPermintaanPembelian {
  Id: string;
  PermintaanPembelianId: string;
  JenisItem: JenisItemPengadaan;
  AsetReferensiId: string | null;
  NamaAsetReferensi?: string | null;
  SukuCadangId: string | null;
  NamaSukuCadang?: string | null;
  Deskripsi: string;
  Jumlah: string;
  Satuan: string;
  HargaEstimasi: string | null;
  Spesifikasi: string | null;
}

export interface PermintaanPembelian {
  Id: string;
  Nomor: string;
  UnitOrganisasiId: string | null;
  NamaUnitOrganisasi?: string | null;
  RencanaPengadaanId: string | null;
  NomorRencanaPengadaan?: string | null;
  PosAnggaranId: string | null;
  NamaPosAnggaran?: string | null;
  TanggalPermintaan: string;
  TanggalDibutuhkan: string | null;
  Prioritas: PrioritasPermintaanPembelian;
  Status: StatusPermintaanPembelian;
  Alasan: string | null;
  NamaPeminta?: string | null;
  TotalEstimasi: string;
  JumlahItem?: number;
  Detail?: DetailPermintaanPembelian[];
  DibuatPada: string;
  DiperbaruiPada: string;
}
