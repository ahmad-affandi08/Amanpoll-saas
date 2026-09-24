import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';

export type IdKeluhan = string;
export type PrioritasKeluhan = 'Rendah' | 'Normal' | 'Tinggi' | 'Kritis';
export type StatusKeluhan =
  'Baru' | 'Ditinjau' | 'Diterima' | 'Diproses' | 'Selesai' | 'Ditutup' | 'Ditolak' | 'Dibatalkan';
/** Server `UrgensiPelapor` (Pemeliharaan). */
export type UrgensiPelapor = 'TidakBuruBuru' | 'MenggangguKerja' | 'KerjaTerhenti' | 'Berbahaya';
export type PemicuEskalasi = 'Menjelang' | 'Terlewati';

export interface AturanTingkatLayanan {
  Id?: string;
  Prioritas: PrioritasKeluhan;
  MenitRespons: number | null;
  MenitPenyelesaian: number | null;
  MenghitungJamKerja: boolean;
}

export interface EskalasiTingkatLayanan {
  Id?: string;
  Tahap: number;
  Pemicu: PemicuEskalasi;
  SetelahMenit: number;
  PeranId: string | null;
  PenggunaId: string | null;
  Kanal: string[];
  Aktif: boolean;
  NamaPeran?: string | null;
  NamaPengguna?: string | null;
}

export interface TingkatLayanan {
  Id: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  HariKerja: number[];
  JamKerjaMulai: string;
  JamKerjaSelesai: string;
  MemperhitungkanHariLibur: boolean;
  Aktif: boolean;
  Aturan: AturanTingkatLayanan[];
  Eskalasi: EskalasiTingkatLayanan[];
}

export interface KategoriKeluhan {
  Id: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  TingkatLayananId: string | null;
  PrioritasBawaan: PrioritasKeluhan;
  AsetWajib: boolean;
  PeranPenanggungJawabId: string | null;
  /** Antrean yang menerima keluhan kategori ini (PRD 8.21); kosong: mengikuti induk, lalu aset. */
  UnitPengelolaId: string | null;
  UnitPengelola: UnitPengelolaRingkas | null;
  Aktif: boolean;
  NamaInduk: string | null;
  NamaTingkatLayanan: string | null;
  NamaPeranPenanggungJawab: string | null;
}

export interface RiwayatStatusKeluhan {
  Id: string;
  StatusSebelum: StatusKeluhan | null;
  StatusSesudah: StatusKeluhan;
  Catatan: string | null;
  NamaPengubah: string | null;
  DiubahPada: string;
}

export interface Keluhan {
  Id: string;
  Nomor: string;
  KategoriKeluhanId: string;
  TingkatLayananId: string | null;
  AsetId: string | null;
  LokasiId: string;
  /** Bagian yang memelihara (PRD 8.21), diturunkan saat dibuat atau dialihkan koordinator. */
  UnitPengelolaId: string | null;
  UnitPengelola?: UnitPengelolaRingkas | null;
  Judul: string;
  Deskripsi: string;
  Prioritas: PrioritasKeluhan;
  /** Urgensi yang dipilih pelapor di Mode Lapangan; hanya usulan, bukan prioritas. */
  UsulanUrgensi: UrgensiPelapor | null;
  LabelUsulanUrgensi: string | null;
  /** Prioritas yang diusulkan urgensi pelapor. */
  PrioritasUsulan: PrioritasKeluhan | null;
  Status: StatusKeluhan;
  Sumber: string;
  PelaporId: string | null;
  NamaKategori: string | null;
  NamaTingkatLayanan: string | null;
  NamaAset: string | null;
  KodeAset: string | null;
  NamaLokasi: string | null;
  NamaPelapor: string | null;
  DilaporkanPada: string;
  DiresponsPada: string | null;
  BatasResponsPada: string | null;
  BatasPenyelesaianPada: string | null;
  DiresolusikanPada: string | null;
  DitutupPada: string | null;
  Versi: number;
  RiwayatStatus: RiwayatStatusKeluhan[];
}
