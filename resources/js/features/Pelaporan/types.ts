export type SatuanKpi = 'Jumlah' | 'Persen' | 'Menit' | 'Jam' | 'Hari' | 'Uang';

export type BentukKomponen = 'Angka' | 'Batang' | 'Garis' | 'Donat' | 'Tabel';

export type FormatEkspor = 'Csv' | 'Xlsx' | 'Pdf';

export interface RincianKpi {
  Label: string;
  Nilai: number;
  [kunci: string]: unknown;
}

/** Definisi KPI dari KatalogKpi, digabung dengan hasil hitungannya. */
export interface MetrikKpi {
  Kunci: string;
  Nama: string;
  Kelompok: string;
  LabelKelompok: string;
  Satuan: SatuanKpi;
  Desimal: number;
  /** Satuan tiap baris Rincian; KPI persen biasanya merinci jumlah, bukan persen. */
  SatuanRincian: SatuanKpi;
  DesimalRincian: number;
  /** Rumus terdokumentasi; ditampilkan pada tiap kartu (Gate 21). */
  Formula: string;
  Sumber: string;
  NaikItuBaik: boolean | null;
  Nilai: number;
  Rincian: RincianKpi[];
  Konteks: {
    AdaData?: boolean;
    Pembilang?: number;
    Penyebut?: number;
    FilterDimensiBerlaku?: boolean;
    /** False bila filter unit pengelola sedang dipakai tetapi tidak menyaring KPI ini (mis. anggaran). */
    FilterUnitPengelolaBerlaku?: boolean;
    [kunci: string]: unknown;
  };
}

export interface PilihanBentuk {
  Nilai: BentukKomponen;
  Label: string;
}

/** Entri katalog untuk pemilih komponen dan pemilih KPI laporan. */
export interface DefinisiKpi {
  Kunci: string;
  Nama: string;
  Kelompok: string;
  LabelKelompok: string;
  Satuan: SatuanKpi;
  Desimal: number;
  SatuanRincian: SatuanKpi;
  DesimalRincian: number;
  Formula: string;
  Sumber: string;
  NaikItuBaik: boolean | null;
  Bentuk?: PilihanBentuk[];
}

export interface KomponenSusunan {
  Id: string;
  JenisKomponen: string;
  Judul: string | null;
  KunciKpi: string;
  Bentuk: BentukKomponen;
  Lebar: number;
}

export interface SusunanDasbor {
  Kunci: string;
  Nama: string;
  Komponen: KomponenSusunan[];
}

export interface DasborTersimpanRingkas {
  Id: string;
  Nama: string;
  Bawaan: boolean;
  Milik: boolean;
}

export interface DasborTersimpanPenuh extends SusunanDasbor {
  Id: string;
  Bawaan: boolean;
  Milik: boolean;
}

export interface FilterMetrik {
  Dari: string;
  Sampai: string;
  UnitOrganisasiId: string[];
  LokasiId: string[];
  /** PRD 8.21; selalu kosong bila organisasi tidak memakai unit pengelola. */
  UnitPengelolaId: string[];
  /** Filter dikirim apa adanya sebagai muatan kunjungan Inertia. */
  [kunci: string]: string | string[];
}

export interface PilihanDimensi {
  Id: string;
  Nama: string;
}

export interface LaporanTersimpanItem {
  Id: string;
  Nama: string;
  Jenis: string;
  Pribadi: boolean;
  Milik: boolean;
  NamaPemilik: string | null;
  KunciKpi: string[];
  Filter: Partial<FilterMetrik>;
  DiperbaruiPada: string;
}

export interface EksporItem {
  Id: string;
  NamaAsli: string;
  Judul: string;
  Format: string;
  JumlahBaris: number;
  UkuranByte: number | null;
  DibuatPada: string;
}
