export type IdPengguna = string;

export interface PenggunaPeranBaris {
  Id: string;
  PeranId: string;
  NamaPeran: string | null;
  UnitOrganisasiId: string | null;
  NamaUnitOrganisasi?: string | null;
  LokasiId: string | null;
  NamaLokasi?: string | null;
  BerlakuMulai: string | null;
  BerlakuSampai: string | null;
}

export interface PerangkatPenggunaBaris {
  Id: string;
  NamaPerangkat: string | null;
  Platform: string | null;
  Status: string;
  TerakhirSinkronPada: string | null;
}

export interface Pengguna {
  Id: string;
  Nama: string;
  Email: string;
  Telepon: string | null;
  NomorPegawai: string | null;
  Jabatan: string | null;
  JenisPengguna: 'Internal' | 'Eksternal';
  Status: 'Aktif' | 'Nonaktif';
  UnitOrganisasiId: string | null;
  TerakhirMasukPada: string | null;
  Peran: PenggunaPeranBaris[];
  Perangkat: PerangkatPenggunaBaris[];
  DibuatPada: string;
}

export interface PeranRingkas {
  Id: string;
  Nama: string;
}

/** Ringkasan beban kerja di kepala halaman detail pengguna. */
export interface RingkasanPengguna {
  PenugasanBerjalan: number;
  TotalMenitKerja: number;
  AsetDitanggung: number;
  JumlahPeran: number;
}

interface BagianRiwayat<T> {
  total: number;
  data: T[];
}

export interface PenugasanPenggunaBaris {
  Id: string;
  PerintahKerjaId: string;
  Nomor: string | null;
  Judul: string | null;
  StatusPerintahKerja: string | null;
  PeranTugas: string;
  Status: string;
  DitugaskanPada: string;
  SelesaiPada: string | null;
}

export interface WaktuKerjaPenggunaBaris {
  Id: string;
  PerintahKerjaId: string;
  Nomor: string | null;
  JenisWaktu: string;
  DurasiMenit: number | null;
  MulaiPada: string;
  SelesaiPada: string | null;
}

export interface TanggungJawabAsetBaris {
  Id: string;
  AsetId: string;
  Aset: string | null;
  KodeAset: string | null;
  UnitOrganisasi: string | null;
  MulaiPada: string;
  SelesaiPada: string | null;
}

export interface BebanKerjaPengguna {
  ringkasan: {
    JumlahPenugasan: number;
    JumlahPenugasanBerjalan: number;
    TotalMenitKerja: number;
    JumlahAsetDitanggung: number;
  };
  penugasan: BagianRiwayat<PenugasanPenggunaBaris>;
  waktuKerja: BagianRiwayat<WaktuKerjaPenggunaBaris>;
  tanggungJawabAset: BagianRiwayat<TanggungJawabAsetBaris>;
}

export interface CatatanAksesBaris {
  Id: string;
  Jenis: string;
  Berhasil: boolean;
  AlasanGagal: string | null;
  AlamatIp: string | null;
  DibuatPada: string;
}

export interface AktivitasPengguna {
  ringkasan: {
    JumlahAkses: number;
    JumlahAksesGagal: number;
    TerakhirMasukPada: string | null;
    JumlahPerangkat: number;
  };
  akses: BagianRiwayat<CatatanAksesBaris>;
  perangkat: PerangkatPenggunaBaris[];
}
