export type StatusKepatuhan = 'BelumDiperiksa' | 'Patuh' | 'TidakPatuh' | 'Kedaluwarsa';

export interface PersyaratanKepatuhan {
  Id: string;
  StandarKepatuhanId: string;
  NamaStandar?: string | null;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  BuktiYangDiperlukan: string | null;
  IntervalHari: number | null;
  JumlahAsetDitugaskan?: number;
}

export interface StandarKepatuhan {
  Id: string;
  Kode: string;
  Nama: string;
  Penerbit: string | null;
  VersiStandar: string | null;
  JenisIndustri: string | null;
  Deskripsi: string | null;
  Aktif: boolean;
  JumlahPersyaratan?: number;
  Persyaratan?: PersyaratanKepatuhan[];
  DibuatPada: string;
}

export interface KepatuhanAset {
  Id: string;
  AsetId: string;
  KodeAset?: string | null;
  NamaAset?: string | null;
  PersyaratanKepatuhanId: string;
  KodePersyaratan?: string | null;
  NamaPersyaratan?: string | null;
  BuktiYangDiperlukan?: string | null;
  Status: StatusKepatuhan;
  TanggalPemeriksaan: string | null;
  BerlakuSampai: string | null;
  /** Null bila persyaratan tidak berbatas waktu; negatif berarti sudah lewat. */
  SisaHari: number | null;
  Catatan: string | null;
  NamaPemeriksa?: string | null;
  DiperbaruiPada: string;
}

export interface RingkasanKepatuhan {
  totalKewajiban: number;
  patuh: number;
  tidakPatuh: number;
  belumDiperiksa: number;
  kedaluwarsa: number;
  persentaseKepatuhan: number;
  sertifikatAktif: number;
  sertifikatAkanBerakhir: number;
  sertifikatKedaluwarsa: number;
}

export interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
}
