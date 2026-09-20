export interface TitikUkurKalibrasi {
  Id: string;
  OrganisasiId: string;
  JenisKalibrasiId?: string | null;
  KategoriAsetId?: string | null;
  Nama: string;
  Satuan?: string | null;
  NilaiReferensi?: number | string | null;
  ToleransiMinus?: number | string | null;
  ToleransiPlus?: number | string | null;
  Urutan: number;
  Aktif: boolean;
  DibuatPada?: string;
}

export interface JenisKalibrasi {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Deskripsi?: string | null;
  Aktif: boolean;
  DibuatPada?: string;
  titik_ukur?: TitikUkurKalibrasi[];
  titikUkur?: TitikUkurKalibrasi[];
  rencana_kalibrasi_count?: number;
  pelaksanaan_kalibrasi_count?: number;
}

export interface HasilTitikUkurKalibrasi {
  Id: string;
  OrganisasiId: string;
  PelaksanaanKalibrasiId: string;
  TitikUkurKalibrasiId?: string | null;
  NamaTitik: string;
  NilaiReferensi?: number | string | null;
  NilaiTerukur?: number | string | null;
  Koreksi?: number | string | null;
  Ketidakpastian?: number | string | null;
  Satuan?: string | null;
  Hasil?: 'Lolos' | 'Gagal' | 'BelumDiuji' | string | null;
  Catatan?: string | null;
  DibuatPada?: string;
  titik_ukur_kalibrasi?: TitikUkurKalibrasi | null;
  titikUkurKalibrasi?: TitikUkurKalibrasi | null;
}

export interface RencanaKalibrasi {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  JenisKalibrasiId?: string | null;
  PenyediaId?: string | null;
  IntervalHari: number;
  TanggalMulai: string;
  TanggalBerikutnya: string;
  PeringatanHariSebelum: number;
  Aktif: boolean;
  DibuatPada?: string;
  DiperbaruiPada?: string;
  StatusKalibrasi?: 'Valid' | 'SegeraJatuhTempo' | 'Terlambat' | 'TidakAktif';
  SisaHari?: number;
  pelaksanaan_kalibrasi_count?: number;
  aset?: {
    Id: string;
    KodeAset: string;
    Nama: string;
    LokasiId?: string | null;
    lokasi?: { Id: string; Nama: string } | null;
  } | null;
  jenis_kalibrasi?: JenisKalibrasi | null;
  jenisKalibrasi?: JenisKalibrasi | null;
  penyedia?: {
    Id: string;
    Kode: string;
    Nama: string;
  } | null;
  pelaksanaan_kalibrasi?: PelaksanaanKalibrasi[];
  pelaksanaanKalibrasi?: PelaksanaanKalibrasi[];
}

export interface PelaksanaanKalibrasi {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  RencanaKalibrasiId?: string | null;
  AsetId: string;
  JenisKalibrasiId?: string | null;
  PenyediaId?: string | null;
  PerintahKerjaId?: string | null;
  TanggalKalibrasi: string;
  TanggalBerlakuSampai?: string | null;
  Hasil: 'Terjadwal' | 'Lolos' | 'Gagal' | 'LolosDenganCatatan' | string;
  NomorSertifikat?: string | null;
  Laboratorium?: string | null;
  KondisiLingkungan?: Record<string, any> | null;
  Catatan?: string | null;
  DilaksanakanOleh?: string | null;
  DiverifikasiOleh?: string | null;
  DiverifikasiPada?: string | null;
  DibuatPada?: string;
  DiperbaruiPada?: string;
  hasil_titik_ukur_count?: number;
  aset?: {
    Id: string;
    KodeAset: string;
    Nama: string;
    lokasi?: { Id: string; Nama: string } | null;
  } | null;
  jenis_kalibrasi?: JenisKalibrasi | null;
  jenisKalibrasi?: JenisKalibrasi | null;
  penyedia?: {
    Id: string;
    Kode: string;
    Nama: string;
  } | null;
  dilaksanakan_oleh?: {
    Id: string;
    Nama: string;
  } | null;
  dilaksanakanOleh?: {
    Id: string;
    Nama: string;
  } | null;
  diverifikasi_oleh?: {
    Id: string;
    Nama: string;
  } | null;
  diverifikasiOleh?: {
    Id: string;
    Nama: string;
  } | null;
  rencana_kalibrasi?: RencanaKalibrasi | null;
  rencanaKalibrasi?: RencanaKalibrasi | null;
  hasil_titik_ukur?: HasilTitikUkurKalibrasi[];
  hasilTitikUkur?: HasilTitikUkurKalibrasi[];
}

export interface StatistikKepatuhan {
  total: number;
  valid: number;
  segeraJatuhTempo: number;
  terlambat: number;
  persentaseKepatuhan: number;
}
