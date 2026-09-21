export type StatusAnggaran = 'Draft' | 'MenungguPersetujuan' | 'Aktif' | 'Ditolak' | 'Ditutup';
export type JenisTransaksiAnggaran = 'Komitmen' | 'Realisasi' | 'PelepasanKomitmen' | 'Penyesuaian';

export interface TransaksiAnggaran {
  Id: string;
  PosAnggaranId: string;
  NamaPosAnggaran?: string;
  Jenis: JenisTransaksiAnggaran;
  ReferensiJenis: string | null;
  ReferensiId: string | null;
  Jumlah: string;
  Tanggal: string;
  Keterangan: string | null;
  DibuatPada: string;
}

export interface PosAnggaran {
  Id: string;
  AnggaranId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  Jumlah: string;
  Terpakai: string;
  Ditahan: string;
  Sisa: string;
  NamaInduk?: string | null;
  Anak?: PosAnggaran[];
}

export interface Anggaran {
  Id: string;
  Kode: string;
  Nama: string;
  Tahun: number;
  MataUang: string;
  Jumlah: string;
  Status: StatusAnggaran;
  UnitOrganisasiId: string | null;
  NamaUnitOrganisasi?: string | null;
  JumlahPos?: number;
  PosAnggaran?: PosAnggaran[];
  DibuatPada: string;
  DiperbaruiPada: string;
}
