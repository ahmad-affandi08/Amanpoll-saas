import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';

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

/** Jawaban penerima atas pekerjaan (PRD 8.22, `KonfirmasiPenerimaResource`). */
export interface KonfirmasiPenerimaPerintahKerja {
  Id: string;
  Metode: 'Pelapor' | 'PindaiQr' | 'TandaTanganPerangkat';
  LabelMetode: string;
  Hasil: 'Diterima' | 'MasihBermasalah';
  NamaPenerima: string;
  JabatanPenerima: string | null;
  Alasan: string | null;
  Ulasan: string | null;
  Penilaian: number | null;
  /** Konfirmasi "Diterima" siklus penyelesaian yang sedang berjalan. */
  Berlaku: boolean;
  DikonfirmasiPada: string;
  /** Gambar tanda tangan lewat rute terotorisasi per perintah kerja. */
  UrlTandaTangan: string | null;
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
  UnitPengelolaId: string | null;
  /** Bagian yang memelihara (PRD 8.21); ada bila relasinya dimuat. */
  UnitPengelola?: UnitPengelolaRingkas | null;
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
  /** Menunggu Verifikasi tanpa konfirmasi penerima (PRD 8.22); `null` bila server tidak menghitungnya. */
  MenungguKonfirmasiPenerima?: boolean | null;
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

export interface TeknisiOpsi {
  Id: string;
  Nama: string;
  Jabatan: string | null;
  BebanAktif: number;
}

export interface StokOpsi {
  GudangId: string;
  NamaGudang: string | null;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  TersediaBersih: number;
}

export interface KeluhanRingkas {
  Id: string;
  Nomor: string;
  Judul: string;
  Prioritas: PrioritasPerintahKerja;
  LokasiId: string | null;
  AsetId: string | null;
  UnitPengelolaId?: string | null;
}

export interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
  LokasiId: string | null;
  UnitPengelolaId?: string | null;
}

export interface LokasiRingkas {
  Id: string;
  Nama: string;
}
