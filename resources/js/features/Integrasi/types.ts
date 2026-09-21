export type StatusIntegrasi = 'Aktif' | 'Nonaktif' | 'Bermasalah';

export type MetodeAutentikasi = 'Bearer' | 'ApiKey' | 'Basic' | 'TanpaAutentikasi';

export type StatusPengiriman = 'Antri' | 'Berhasil' | 'Gagal' | 'GagalPermanen';

export interface IntegrasiEksternal {
  Id: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  UrlDasar: string | null;
  MetodeAutentikasi: MetodeAutentikasi | null;
  /** Hanya nama kunci konfigurasi; nilainya tidak pernah dikirim ke klien. */
  KunciKonfigurasi: string[];
  Status: StatusIntegrasi;
  TerakhirSinkronPada: string | null;
  JumlahPemetaan: number;
  JumlahSinkronisasi: number;
}

export interface PemetaanDataEksternal {
  Id: string;
  JenisEntitas: string;
  EntitasId: string;
  KodeEksternal: string;
  Konflik: boolean;
  AlasanKonflik: string | null;
  DiperbaruiPada: string;
}

export interface SinkronisasiEksternal {
  Id: string;
  JenisProses: string;
  Arah: 'Tarik' | 'Dorong';
  Status: 'Diproses' | 'Berhasil' | 'Sebagian' | 'Gagal';
  JumlahData: number;
  JumlahBerhasil: number;
  JumlahGagal: number;
  PesanKesalahan: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
}

export interface PanggilanBalikWeb {
  Id: string;
  Nama: string;
  Url: string;
  Peristiwa: string[];
  Aktif: boolean;
  JumlahPengiriman?: number;
}

export interface PengirimanPanggilanBalikWeb {
  Id: string;
  Peristiwa: string;
  Status: StatusPengiriman;
  StatusHttp: number | null;
  Percobaan: number;
  JadwalCobaLagiPada: string | null;
  DikirimPada: string | null;
  DibuatPada: string;
  Respons: string | null;
}

export interface AntrianPeristiwa {
  menunggu: number;
  gagal: number;
  pengirimanGagal: number;
}
