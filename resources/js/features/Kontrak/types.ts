export type StatusKontrak = 'Aktif' | 'Berakhir' | 'Dibatalkan';

export type JenisKontrak = 'Pemeliharaan' | 'Layanan' | 'Sewa' | 'Pembelian' | 'Lainnya';

export interface KontrakAset {
  Id: string;
  KontrakId: string;
  AsetId: string;
  KodeAset?: string | null;
  NamaAset?: string | null;
  MulaiPada: string | null;
  BerakhirPada: string | null;
  Catatan: string | null;
}

export interface LayananKontrak {
  Id: string;
  KontrakId: string;
  Nama: string;
  Deskripsi: string | null;
  Kuota: string | null;
  Satuan: string | null;
  Terpakai: string;
  DibuatPada: string;
}

export interface Kontrak {
  Id: string;
  Nomor: string;
  Nama: string;
  Jenis: JenisKontrak;
  PenyediaId: string | null;
  NamaPenyedia?: string | null;
  MulaiPada: string;
  BerakhirPada: string;
  /** Negatif berarti kontrak sudah lewat tanggal berakhir. */
  SisaHari: number;
  Nilai: string | null;
  MataUang: string;
  TingkatLayananId: string | null;
  NamaTingkatLayanan?: string | null;
  PeringatanHariSebelum: number;
  Status: StatusKontrak;
  Catatan: string | null;
  JumlahAset?: number;
  JumlahLayanan?: number;
  Aset?: KontrakAset[];
  Layanan?: LayananKontrak[];
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface RingkasanKontrak {
  total: number;
  aktif: number;
  akanBerakhir: number;
  kedaluwarsa: number;
  tanpaPenyedia: number;
}

export interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
}
