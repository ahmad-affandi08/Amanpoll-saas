export type StatusPermintaanMutasiAset =
  'Draft' | 'Menunggu' | 'Disetujui' | 'Ditolak' | 'Dibatalkan' | 'Selesai';
export type JenisMutasiAset = 'AntarLokasi' | 'AntarUnit' | 'Peminjaman' | 'Pengembalian';
export type StatusDetailMutasiAset = 'Menunggu' | 'Selesai' | 'Dibatalkan';

export interface DetailMutasiAset {
  Id: string;
  AsetId: string;
  NamaAset: string | null;
  KodeAset: string | null;
  Status: StatusDetailMutasiAset;
  Catatan: string | null;
  DibuatPada: string;
}

export interface PermintaanMutasiAset {
  Id: string;
  Nomor: string;
  JenisMutasi: JenisMutasiAset;
  UnitAsalId: string | null;
  NamaUnitAsal: string | null;
  UnitTujuanId: string | null;
  NamaUnitTujuan: string | null;
  LokasiAsalId: string | null;
  NamaLokasiAsal: string | null;
  LokasiTujuanId: string | null;
  NamaLokasiTujuan: string | null;
  Alasan: string | null;
  Status: StatusPermintaanMutasiAset;
  DimintaOleh: string;
  NamaDimintaOleh: string | null;
  DimintaPada: string;
  DisetujuiPada: string | null;
  SelesaiPada: string | null;
  DetailMutasiAset: DetailMutasiAset[];
  Versi: number;
  DibuatPada: string;
}

export type StatusSerahTerimaAset = 'Diserahkan' | 'Diterima';

export interface DetailSerahTerimaAset {
  Id: string;
  AsetId: string;
  NamaAset: string | null;
  KodeAset: string | null;
  KondisiSaatDiserahkan: string | null;
  KondisiSaatDiterima: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface SerahTerimaAset {
  Id: string;
  Nomor: string;
  PermintaanMutasiAsetId: string | null;
  Jenis: string;
  PihakMenyerahkan: string | null;
  NamaPihakMenyerahkan: string | null;
  PihakMenerima: string | null;
  NamaPihakMenerima: string | null;
  DiserahkanPada: string | null;
  DiterimaPada: string | null;
  Status: StatusSerahTerimaAset;
  Catatan: string | null;
  DetailSerahTerimaAset: DetailSerahTerimaAset[];
  DibuatPada: string;
}

export type StatusPengajuanPenghapusanAset =
  'Draft' | 'Menunggu' | 'Disetujui' | 'Ditolak' | 'Dibatalkan' | 'Selesai';
export type MetodePenghapusanAset = 'Dijual' | 'Dimusnahkan' | 'Hibah' | 'Hilang' | 'Lainnya';
export type StatusDetailPenghapusanAset = 'Menunggu' | 'Selesai' | 'Dibatalkan';

export interface DetailPenghapusanAset {
  Id: string;
  AsetId: string;
  NamaAset: string | null;
  KodeAset: string | null;
  NilaiBukuSaatPenghapusan: string | null;
  HasilPelepasan: string | null;
  Status: StatusDetailPenghapusanAset;
  Catatan: string | null;
  DibuatPada: string;
}

export interface PengajuanPenghapusanAset {
  Id: string;
  Nomor: string;
  Alasan: string;
  MetodePenghapusan: MetodePenghapusanAset | null;
  Status: StatusPengajuanPenghapusanAset;
  DiajukanOleh: string;
  NamaDiajukanOleh: string | null;
  DiajukanPada: string;
  DiselesaikanPada: string | null;
  DetailPenghapusanAset: DetailPenghapusanAset[];
  DibuatPada: string;
}
