/** Penanda Mode Lapangan pada peran (PRD 8.20); `null` berarti peran meja. */
export type TampilanLapangan = 'Teknisi' | 'Pelapor';

export interface Peran {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  BawaanSistem: boolean;
  TampilanLapangan: TampilanLapangan | null;
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
