export type IdAset = string;

export type StatusAset = 'Aktif' | 'Nonaktif' | 'Dipinjam' | 'Rusak' | 'Diarsipkan';
export type KondisiAset = 'Baik' | 'PerluPerhatian' | 'Rusak';
export type TingkatKritisAset = 'Normal' | 'Tinggi' | 'SangatTinggi';

export interface KategoriAset {
  Id: string;
  IndukId: string | null;
  NamaInduk: string | null;
  Kode: string;
  Nama: string;
  UmurManfaatBulan: number | null;
  MetodePenyusutanBawaan: string | null;
  PersentaseNilaiResidu: string | null;
  MemerlukanKalibrasi: boolean;
  MemerlukanPemeliharaan: boolean;
  DibuatPada: string;
}

export interface Merek {
  Id: string;
  Nama: string;
  NegaraAsal: string | null;
  Website: string | null;
  DibuatPada: string;
}

export interface ModelAset {
  Id: string;
  KategoriAsetId: string;
  NamaKategoriAset: string | null;
  MerekId: string | null;
  NamaMerek: string | null;
  KodeModel: string | null;
  Nama: string;
  Produsen: string | null;
  Spesifikasi: Record<string, unknown> | null;
  IntervalPemeliharaanHari: number | null;
  IntervalKalibrasiHari: number | null;
  UmurManfaatBulan: number | null;
  DibuatPada: string;
}

export interface Aset {
  Id: string;
  OrganisasiId: string;
  UnitOrganisasiId: string | null;
  NamaUnitOrganisasi: string | null;
  LokasiId: string | null;
  NamaLokasi: string | null;
  KategoriAsetId: string;
  NamaKategoriAset: string | null;
  ModelAsetId: string | null;
  NamaModelAset: string | null;
  PenyediaId: string | null;
  NamaPenyedia: string | null;
  KodeAset: string;
  Nama: string;
  NomorSeri: string | null;
  NomorInventaris: string | null;
  NomorRegistrasiEksternal: string | null;
  TanggalPerolehan: string | null;
  TanggalMulaiOperasi: string | null;
  TanggalAkhirOperasi: string | null;
  HargaPerolehan: string | null;
  NilaiResidu: string | null;
  MataUang: string;
  SumberDana: string | null;
  MetodePenyusutan: string | null;
  UmurManfaatBulan: number | null;
  Status: StatusAset;
  Kondisi: KondisiAset;
  TingkatKritis: TingkatKritisAset;
  KodeQr: string | null;
  NfcUid: string | null;
  KodeBatang: string | null;
  Catatan: string | null;
  Versi: number;
  NamaDibuatOleh: string | null;
  DibuatPada: string;
}

export interface RiwayatLokasiAset {
  Id: string;
  LokasiAsalId: string | null;
  NamaLokasiAsal: string | null;
  LokasiTujuanId: string | null;
  NamaLokasiTujuan: string | null;
  JenisPerpindahan: string;
  Alasan: string | null;
  NamaDipindahkanOleh: string | null;
  DipindahkanPada: string;
}

export interface RiwayatPenanggungJawabAset {
  Id: string;
  PenggunaId: string | null;
  NamaPengguna: string | null;
  UnitOrganisasiId: string | null;
  NamaUnitOrganisasi: string | null;
  MulaiPada: string;
  SelesaiPada: string | null;
  Catatan: string | null;
}

export interface RelasiAset {
  Id: string;
  AsetIndukId: string;
  NamaAsetInduk: string | null;
  AsetAnakId: string;
  NamaAsetAnak: string | null;
  JenisRelasi: 'Komponen' | 'Terkait';
  Jumlah: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  DibuatPada: string;
}

export interface GaransiAset {
  Id: string;
  AsetId: string;
  PenyediaId: string | null;
  NamaPenyedia: string | null;
  NomorGaransi: string | null;
  JenisGaransi: string | null;
  MulaiPada: string;
  BerakhirPada: string;
  Cakupan: string | null;
  Status: 'Aktif' | 'Berakhir' | 'Dibatalkan';
  SisaHari: number;
  AkanBerakhir: boolean;
  SudahBerakhir: boolean;
  DibuatPada: string;
}

export interface NilaiAset {
  Id: string;
  AsetId: string;
  TanggalNilai: string;
  NilaiBuku: string;
  AkumulasiPenyusutan: string;
  BebanPenyusutanPeriode: string;
  Metode: string | null;
  DibuatPada: string;
}

export interface MeterAset {
  Id: string;
  AsetId: string;
  Nama: string;
  Satuan: string;
  Jenis: 'Kumulatif' | 'NonKumulatif';
  NilaiAwal: string;
  Aktif: boolean;
  NilaiTerakhir: string | null;
  DibuatPada: string;
}

export interface PembacaanMeterAset {
  Id: string;
  MeterAsetId: string;
  Nilai: string;
  DibacaPada: string;
  Sumber: string;
  NamaDicatatOleh: string | null;
  DibuatPada: string;
}

export interface FilterAset {
  cari?: string;
  kategoriAsetId?: string;
  lokasiId?: string;
  status?: string;
  urutkan?: string;
  arah?: string;
}
