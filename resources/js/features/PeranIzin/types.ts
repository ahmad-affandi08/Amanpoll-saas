export interface Peran {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  BawaanSistem: boolean;
  JumlahIzin: number;
  JumlahPengguna: number;
  DaftarIzinId: string[];
  DibuatPada: string;
}

export interface Izin {
  Id: string;
  Kode: string;
  Nama: string;
  Modul: string;
  Keterangan: string | null;
}

export type KatalogIzin = Record<string, Izin[]>;
