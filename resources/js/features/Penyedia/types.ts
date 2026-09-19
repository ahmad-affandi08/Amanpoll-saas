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
