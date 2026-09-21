export type IdPerintahKerja = string;

export type StatusPerintahKerja =
  | 'Draf'
  | 'Terjadwal'
  | 'Ditugaskan'
  | 'Diterima'
  | 'Dikerjakan'
  | 'MenungguSukuCadang'
  | 'MenungguPenyedia'
  | 'Dijeda'
  | 'MenungguVerifikasi'
  | 'Selesai'
  | 'Ditutup'
  | 'Dibatalkan';

export type PrioritasPerintahKerja = 'Rendah' | 'Normal' | 'Tinggi' | 'Kritis';

export type JenisPerintahKerja = 'Korektif' | 'Preventif' | 'Inspeksi' | 'Kalibrasi' | 'Umum' | 'Vendor';

export interface AsetPerintahKerja {
  Id: string;
  KodeAset: string;
  Nama: string;
  Utama: boolean;
  KondisiAwal: string | null;
  KondisiAkhir: string | null;
}

export interface PenugasanPerintahKerjaItem {
  Id: string;
  PenggunaId: string;
  NamaPengguna: string | null;
  PeranTugas: string;
  Status: string;
  DitugaskanPada: string | null;
  DiterimaPada: string | null;
  SelesaiPada: string | null;
}

export interface RiwayatStatusPerintahKerja {
  Id: string;
  StatusSebelum: StatusPerintahKerja | null;
  StatusSesudah: StatusPerintahKerja;
  Catatan: string | null;
  NamaPengubah: string | null;
  DiubahPada: string;
}

export interface WaktuKerjaItem {
  Id: string;
  PenggunaId: string;
  NamaPengguna: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  DurasiMenit: number | null;
  JenisWaktu: string;
  Catatan: string | null;
}

export interface WaktuHentiAsetItem {
  Id: string;
  AsetId: string;
  NamaAset: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  DurasiMenit: number | null;
  Jenis: string;
  Alasan: string;
}

export interface BiayaPerintahKerjaItem {
  Id: string;
  JenisBiaya: string;
  Deskripsi: string;
  Jumlah: number;
  MataUang: string;
  TanggalBiaya: string | null;
}

export interface AnalisisKegagalanData {
  KodeMasalahId: string | null;
  KodePenyebabId: string | null;
  KodeTindakanId: string | null;
  AkarMasalah: string | null;
  TindakanKorektif: string | null;
  TindakanPencegahan: string | null;
}

export interface ReservasiSukuCadangItem {
  Id: string;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  NamaGudang: string | null;
  Jumlah: number;
  Status: string;
}

export interface PemakaianSukuCadangItem {
  Id: string;
  NamaSukuCadang: string | null;
  Jumlah: number;
  HargaSatuan: number;
  DipakaiPada: string | null;
}

export interface PerintahKerja {
  Id: string;
  Nomor: string;
  KeluhanId: string | null;
  NomorKeluhan?: string | null;
  Jenis: JenisPerintahKerja;
  Judul: string;
  Deskripsi: string | null;
  Prioritas: PrioritasPerintahKerja;
  Status: StatusPerintahKerja;
  LokasiId: string | null;
  NamaLokasi?: string | null;
  UnitOrganisasiId: string | null;
  DijadwalkanMulaiPada: string | null;
  DijadwalkanSelesaiPada: string | null;
  DiterimaPada: string | null;
  DimulaiPada: string | null;
  DiselesaikanPada: string | null;
  DitutupPada: string | null;
  BatasResponsPada: string | null;
  BatasPenyelesaianPada: string | null;
  PersentaseSelesai: number;
  MembutuhkanWaktuHenti: boolean;
  MembutuhkanPersetujuan: boolean;
  RingkasanPenyelesaian: string | null;
  Versi: number;
  Aset: AsetPerintahKerja[];
  Penugasan: PenugasanPerintahKerjaItem[];
  RiwayatStatus: RiwayatStatusPerintahKerja[];
  WaktuKerja: WaktuKerjaItem[];
  WaktuHenti: WaktuHentiAsetItem[];
  Biaya: BiayaPerintahKerjaItem[];
  AnalisisKegagalan: AnalisisKegagalanData | null;
  ReservasiSukuCadang: ReservasiSukuCadangItem[];
  PemakaianSukuCadang: PemakaianSukuCadangItem[];
  TotalWaktuKerjaMenit: number;
  TotalDowntimeMenit: number;
  TotalBiaya: number;
}

export interface KodeKegagalan {
  Id: string;
  Kode: string;
  Nama: string;
  Jenis: 'Masalah' | 'Penyebab' | 'Tindakan';
  KategoriAsetId: string | null;
  kategoriAset?: { Id: string; Nama: string } | null;
  Deskripsi: string | null;
  Aktif: boolean;
}
