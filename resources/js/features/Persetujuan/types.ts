export type JenisPenyetuju = 'Pengguna' | 'Peran' | 'Unit';

export interface TahapPersetujuan {
  Id: string;
  AlurPersetujuanId: string;
  Urutan: number;
  Nama: string;
  JenisPenyetuju: JenisPenyetuju;
  PeranId: string | null;
  NamaPeran: string | null;
  PenggunaId: string | null;
  NamaPengguna: string | null;
  JumlahMinimumPenyetuju: number;
  BolehMenyetujuiSendiri: boolean;
  BatasWaktuMenit: number | null;
}

export interface AlurPersetujuan {
  Id: string;
  Kode: string;
  Nama: string;
  JenisEntitas: string;
  KondisiAktivasi: Record<string, unknown> | null;
  Aktif: boolean;
  TahapPersetujuan: TahapPersetujuan[];
  DibuatPada: string;
}

export type StatusPermintaanPersetujuan = 'Menunggu' | 'Disetujui' | 'Ditolak' | 'Dibatalkan';

export interface KeputusanPersetujuan {
  Id: string;
  PermintaanPersetujuanId: string;
  TahapPersetujuanId: string;
  PenyetujuId: string;
  NamaPenyetuju: string | null;
  Keputusan: 'Disetujui' | 'Ditolak';
  Catatan: string | null;
  DiputuskanPada: string;
}

export interface PermintaanPersetujuan {
  Id: string;
  AlurPersetujuanId: string;
  NamaAlur: string | null;
  JenisEntitas: string;
  EntitasId: string;
  TahapSaatIni: number;
  Status: StatusPermintaanPersetujuan;
  DimintaOleh: string;
  NamaPeminta: string | null;
  DimintaPada: string;
  SelesaiPada: string | null;
  DataTambahan: Record<string, unknown> | null;
  Keputusan: KeputusanPersetujuan[];
}
