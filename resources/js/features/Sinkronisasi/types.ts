export type StatusAntrianSinkronisasi =
  'Menunggu' | 'Diproses' | 'Selesai' | 'Gagal' | 'Konflik' | 'Dibatalkan';

export type KeputusanKonflik = 'PakaiServer' | 'TerapkanUlang';

/** Status gabungan yang ditampilkan indikator topbar (DESIGN.md 24). */
export type StatusSinkronisasi = 'Online' | 'Offline' | 'Menyinkronkan' | 'GagalSinkron' | 'Konflik';

export type OperasiOffline =
  | 'PerintahKerja.ResponsPenugasan'
  | 'PerintahKerja.UbahStatus'
  | 'PerintahKerja.CatatWaktuKerja'
  | 'PerintahKerja.TambahCatatan'
  | 'DaftarPeriksa.SimpanJawaban'
  | 'DaftarPeriksa.Finalisasi'
  | 'Keluhan.Buat';

export interface DetailKonflik {
  Alasan: string;
  Pesan: string;
  VersiKlien?: number | null;
  VersiServer?: number | null;
  NilaiKlien?: Record<string, unknown> | null;
  NilaiServer?: Record<string, unknown> | null;
  Kesalahan?: Record<string, string[]> | null;
  Percobaan?: number;
}

/** Satu mutasi offline sebagaimana disimpan klien dan dikenal server. */
export interface MutasiOffline {
  KunciOperasi: string;
  Operasi: OperasiOffline;
  EntitasId: string | null;
  VersiKlien: number | null;
  MuatanData: Record<string, unknown>;
  Status: StatusAntrianSinkronisasi;
  Konflik: DetailKonflik | null;
  Percobaan: number;
  /** Ringkasan yang ditampilkan ke teknisi tanpa membedah muatan. */
  Label: string;
  DibuatPada: string;
}

/** Bentuk baris antrean yang dikembalikan server. */
export interface AntrianServer {
  Id: string;
  KunciOperasi: string;
  Operasi: OperasiOffline;
  JenisEntitas: string;
  EntitasId: string | null;
  VersiKlien: number | null;
  Status: StatusAntrianSinkronisasi;
  Konflik: DetailKonflik | null;
  Percobaan: number;
  DiterimaPada: string | null;
  DiprosesPada: string | null;
}

export interface PenugasanOffline {
  Id: string;
  Nomor: string;
  Judul: string;
  Deskripsi: string | null;
  Jenis: string | null;
  Status: string;
  Prioritas: string;
  Versi: number;
  NamaLokasi: string | null;
  DijadwalkanMulaiPada: string | null;
  BatasPenyelesaianPada: string | null;
  AsetId: string[];
  /** Penugasan yang belum direspons teknisi ini. */
  PerluResponsPenugasan: boolean;
  /** Hanya transisi yang benar-benar diizinkan policy untuk pengguna ini. */
  StatusTujuan: string[];
}

export interface AsetOffline {
  Id: string;
  KodeAset: string;
  /** Kode label fisik; dipakai pindai QR saat offline. */
  KodeQr?: string | null;
  KodeBatang?: string | null;
  Nama: string;
  NomorSeri: string | null;
  Status: string;
  Kondisi: string | null;
  TingkatKritis: string | null;
  NamaLokasi: string | null;
  /** Thumbnail foto utama; tanpa sinyal tampil hanya bila masih di cache peramban (PRD 8.4). */
  FotoUtamaThumbnailUrl?: string | null;
  Versi: number;
}

export interface ButirDaftarPeriksaOffline {
  Id: string;
  Urutan: number;
  Pertanyaan: string;
  TipeJawaban: string;
  Satuan: string | null;
  Wajib: boolean;
  Pilihan: string[] | null;
  NilaiMinimum: number | null;
  NilaiMaksimum: number | null;
}

export interface JawabanDaftarPeriksaOffline {
  ButirTemplatDaftarPeriksaId: string;
  NilaiTeks?: string | null;
  NilaiAngka?: number | null;
  NilaiBoolean?: boolean | null;
  NilaiTanggal?: string | null;
  Catatan?: string | null;
}

export interface DaftarPeriksaOffline {
  Id: string;
  PerintahKerjaId: string | null;
  AsetId: string | null;
  Status: string;
  NamaTemplat: string | null;
  VersiTemplat: number | null;
  Catatan: string | null;
  Butir: ButirDaftarPeriksaOffline[];
  Jawaban: JawabanDaftarPeriksaOffline[];
}

export interface PaketOffline {
  Penugasan: PenugasanOffline[];
  Aset: AsetOffline[];
  DaftarPeriksa: DaftarPeriksaOffline[];
  Token: Record<string, string>;
  OperasiDidukung: OperasiOffline[];
  /** Setelan organisasi untuk layar tanpa sinyal; kosong pada paket lama yang tersimpan sebelum 39.10. */
  Pengaturan?: { TandaTanganPenerimaWajib: boolean };
  DibuatPada: string;
}

export interface PenandaSinkronisasi {
  TokenSinkronisasi: string | null;
  TerakhirSinkronPada: string | null;
}

export interface ResponsPaketOffline {
  Perangkat: { Id: string; NamaPerangkat: string | null; TerakhirSinkronPada: string | null };
  Paket: PaketOffline;
  Penanda: Record<string, PenandaSinkronisasi>;
  Antrean: AntrianServer[];
}

export interface RingkasanSinkronisasi {
  Menunggu: number;
  Konflik: number;
  Gagal: number;
}
