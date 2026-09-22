export type IdPenyedia = string;

export type StatusPenyedia = 'Aktif' | 'Nonaktif';

export interface KategoriPenyedia {
  Id: string;
  Kode: string;
  Nama: string;
  DibuatPada: string;
}

export interface Penyedia {
  Id: string;
  Kode: string;
  Nama: string;
  NamaLegal: string | null;
  NomorIdentitasPajak: string | null;
  Email: string | null;
  Telepon: string | null;
  Website: string | null;
  Alamat: string | null;
  Kota: string | null;
  Provinsi: string | null;
  Negara: string | null;
  Status: StatusPenyedia;
  KategoriPenyediaId: string[];
  NamaKategoriPenyedia: string[];
  DibuatPada: string;
}

export interface KontakPenyedia {
  Id: string;
  PenyediaId: string;
  Nama: string;
  Jabatan: string | null;
  Email: string | null;
  Telepon: string | null;
  Utama: boolean;
  DibuatPada: string;
}

export interface PenilaianPenyedia {
  Id: string;
  PenyediaId: string;
  PeriodeMulai: string;
  PeriodeSelesai: string;
  SkorKualitas: string | null;
  SkorKetepatanWaktu: string | null;
  SkorHarga: string | null;
  SkorLayanan: string | null;
  SkorTotal: string | null;
  Catatan: string | null;
  NamaPenilai: string | null;
  DibuatPada: string;
}

export interface RekapPenilaianPenyedia {
  SkorTotalRataRata: number | null;
  JumlahPenilaian: number;
}

/** Ringkasan hubungan dagang di kepala halaman detail penyedia. */
export interface RingkasanPenyedia {
  JumlahPesanan: number;
  NilaiPesanan: number;
  SisaTagihan: number;
  JumlahKontrakAktif: number;
  JumlahAset: number;
}

interface BagianRiwayat<T> {
  total: number;
  data: T[];
}

export interface PenawaranPenyediaBaris {
  Id: string;
  NomorPenawaran: string;
  PermintaanPenawaran: string | null;
  Status: string;
  Total: number;
  MataUang: string;
  TanggalPenawaran: string;
  BerlakuSampai: string | null;
}

export interface PesananPenyediaBaris {
  Id: string;
  Nomor: string;
  Status: string;
  Total: number;
  MataUang: string;
  TanggalPesanan: string;
  TanggalKirimRencana: string | null;
}

export interface TagihanPenyediaBaris {
  Id: string;
  NomorTagihan: string;
  NomorPesanan: string | null;
  Status: string;
  Total: number;
  Sisa: number;
  TanggalTagihan: string;
  JatuhTempo: string | null;
}

export interface RiwayatPengadaanPenyedia {
  ringkasan: {
    JumlahPenawaran: number;
    JumlahPenawaranTerpilih: number;
    JumlahPesanan: number;
    NilaiPesanan: number;
    JumlahTagihan: number;
    NilaiTagihan: number;
    SisaTagihan: number;
  };
  penawaran: BagianRiwayat<PenawaranPenyediaBaris>;
  pesanan: BagianRiwayat<PesananPenyediaBaris>;
  tagihan: BagianRiwayat<TagihanPenyediaBaris>;
}

export interface KontrakPenyediaBaris {
  Id: string;
  Nomor: string;
  Nama: string;
  Jenis: string;
  Status: string;
  Nilai: number | null;
  MataUang: string;
  TingkatLayanan: string | null;
  MulaiPada: string;
  BerakhirPada: string;
}

export interface AsetPenyediaBaris {
  Id: string;
  KodeAset: string;
  Nama: string;
  Kategori: string | null;
  Status: string;
  Kondisi: string;
  HargaPerolehan: number | null;
  TanggalPerolehan: string | null;
}

export interface KalibrasiPenyediaBaris {
  Id: string;
  Nomor: string;
  Aset: string | null;
  AsetId: string | null;
  JenisKalibrasi: string | null;
  Hasil: string;
  NomorSertifikat: string | null;
  TanggalKalibrasi: string;
  TanggalBerlakuSampai: string | null;
}

export interface RiwayatLayananPenyedia {
  ringkasan: {
    JumlahKontrak: number;
    JumlahKontrakAktif: number;
    NilaiKontrakAktif: number;
    JumlahAset: number;
    JumlahKalibrasi: number;
  };
  kontrak: BagianRiwayat<KontrakPenyediaBaris>;
  aset: BagianRiwayat<AsetPenyediaBaris>;
  kalibrasi: BagianRiwayat<KalibrasiPenyediaBaris>;
}
