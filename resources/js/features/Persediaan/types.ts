export type StatusGudang = 'Aktif' | 'Nonaktif';
export type StatusSukuCadang = 'Aktif' | 'Nonaktif';
export type JenisMutasiStok = 'Penerimaan' | 'Pengeluaran' | 'Transfer' | 'Adjustment' | 'Return';
export type StatusMutasiStok = 'Draft' | 'Diposting' | 'Dibatalkan';
export type StatusReservasiSukuCadang = 'Aktif' | 'Dilepas' | 'Dipakai' | 'Kadaluarsa';

export interface LokasiGudang {
  Id: string;
  GudangId: string;
  IndukId: string | null;
  NamaInduk: string | null;
  Kode: string;
  Nama: string;
  DibuatPada: string;
}

export interface Gudang {
  Id: string;
  LokasiId: string | null;
  NamaLokasi: string | null;
  Kode: string;
  Nama: string;
  PenanggungJawabId: string | null;
  NamaPenanggungJawab: string | null;
  Status: StatusGudang;
  JumlahLokasiGudang: number | null;
  DibuatPada: string;
}

export interface KategoriSukuCadang {
  Id: string;
  IndukId: string | null;
  NamaInduk: string | null;
  Kode: string;
  Nama: string;
  DibuatPada: string;
}

export interface SukuCadang {
  Id: string;
  KategoriSukuCadangId: string | null;
  NamaKategori: string | null;
  Kode: string;
  Nama: string;
  NomorBagian: string | null;
  KodeBatang: string | null;
  SatuanDasar: string;
  StokMinimum: string;
  StokMaksimum: string | null;
  TitikPesanUlang: string | null;
  HargaRataRata: string;
  MemakaiBatch: boolean;
  MemakaiKadaluarsa: boolean;
  Status: StatusSukuCadang;
  JumlahTersediaBersih: number | null;
  DibuatPada: string;
}

export interface KelompokSukuCadang {
  Id: string;
  SukuCadangId: string;
  NomorBatch: string;
  TanggalProduksi: string | null;
  TanggalKadaluarsa: string | null;
  HargaPerolehan: string | null;
  DibuatPada: string;
}

export interface KompatibilitasSukuCadang {
  Id: string;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  KategoriAsetId: string | null;
  NamaKategoriAset: string | null;
  ModelAsetId: string | null;
  NamaModelAset: string | null;
  AsetId: string | null;
  NamaAset: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface StokSukuCadang {
  Id: string;
  GudangId: string;
  NamaGudang: string | null;
  LokasiGudangId: string | null;
  NamaLokasiGudang: string | null;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  SatuanDasar: string | null;
  KelompokSukuCadangId: string | null;
  NomorBatch: string | null;
  JumlahTersedia: string;
  JumlahDipesan: string;
  JumlahDitahan: string;
  JumlahTersediaBersih: number;
  Versi: number;
  DiperbaruiPada: string | null;
}

export interface DetailMutasiStok {
  Id: string;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  SatuanDasar: string | null;
  KelompokSukuCadangId: string | null;
  NomorBatch: string | null;
  Jumlah: string;
  HargaSatuan: string | null;
  LokasiGudangAsalId: string | null;
  NamaLokasiGudangAsal: string | null;
  LokasiGudangTujuanId: string | null;
  NamaLokasiGudangTujuan: string | null;
  DibuatPada: string;
}

export interface MutasiStok {
  Id: string;
  Nomor: string;
  Jenis: JenisMutasiStok;
  GudangAsalId: string | null;
  NamaGudangAsal: string | null;
  GudangTujuanId: string | null;
  NamaGudangTujuan: string | null;
  ReferensiJenis: string | null;
  ReferensiId: string | null;
  Tanggal: string;
  Status: StatusMutasiStok;
  Catatan: string | null;
  NamaDibuatOleh: string | null;
  DetailMutasiStok: DetailMutasiStok[];
  DibuatPada: string;
}

export interface ReservasiSukuCadang {
  Id: string;
  PerintahKerjaId: string | null;
  GudangId: string;
  NamaGudang: string | null;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  Jumlah: string;
  Status: StatusReservasiSukuCadang;
  KadaluarsaPada: string | null;
  NamaDibuatOleh: string | null;
  DibuatPada: string;
}

/** Baris stok, pemakaian, dan reservasi di halaman detail suku cadang. */
export interface StokSukuCadangRingkas {
  Id: string;
  Gudang: string | null;
  LokasiGudang: string | null;
  NomorBatch: string | null;
  JumlahTersedia: number;
  JumlahDitahan: number;
  JumlahBersih: number;
}

export interface PemakaianSukuCadangBaris {
  Id: string;
  PerintahKerjaId: string | null;
  NomorPerintahKerja: string | null;
  JudulPerintahKerja: string | null;
  Gudang: string | null;
  Jumlah: number;
  HargaSatuan: number | null;
  DipakaiOleh: string | null;
  DipakaiPada: string;
}

export interface ReservasiSukuCadangBaris {
  Id: string;
  PerintahKerjaId: string | null;
  NomorPerintahKerja: string | null;
  Gudang: string | null;
  Jumlah: number;
  KadaluarsaPada: string | null;
}
