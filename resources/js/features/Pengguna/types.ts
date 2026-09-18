export type IdPengguna = string;

export interface PenggunaPeranBaris {
  Id: string;
  PeranId: string;
  NamaPeran: string | null;
  UnitOrganisasiId: string | null;
  LokasiId: string | null;
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
