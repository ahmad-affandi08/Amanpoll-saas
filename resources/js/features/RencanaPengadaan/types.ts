export type StatusRencanaPengadaan = 'Draft' | 'Direncanakan' | 'Dibatalkan';

export interface DetailRencanaPengadaan {
  Id: string;
  UsulanAsetId: string | null;
  NomorUsulan: string | null;
  SukuCadangId: string | null;
  NamaSukuCadang: string | null;
  Deskripsi: string;
  Jumlah: string;
  Satuan: string;
  HargaEstimasi: string | null;
  BulanRencana: number | null;
}

export interface RencanaPengadaan {
  Id: string;
  Nomor: string;
  Nama: string;
  Tahun: number;
  PosAnggaranId: string | null;
  NamaPosAnggaran: string | null;
  Status: StatusRencanaPengadaan;
  TotalEstimasi: string;
  NamaPembuat: string | null;
  Detail?: DetailRencanaPengadaan[];
  DibuatPada: string;
  DiperbaruiPada: string;
}
