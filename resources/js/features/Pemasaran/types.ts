export interface TahapRingkas {
  Kode: string;
  Nama: string;
  TahapAkhir: boolean;
}

export interface Prospek {
  Id: string;
  Nama: string;
  Email: string | null;
  Perusahaan: string | null;
  Industri: string | null;
  Sumber: string;
  Kampanye: string | null;
  Tahap: string | null;
  KodeTahap: string | null;
  Skor: number;
  AktivitasTerakhirPada: string | null;
  DibuatPada: string;
}

export interface ProspekDetail extends Prospek {
  Telepon: string | null;
  WhatsApp: string | null;
  Jabatan: string | null;
  Catatan: string | null;
  Tag: string[];
  RincianSkor: Array<{ Peristiwa: string; Bobot: number }>;
  Qualified: boolean;
}

export interface EntriTimeline {
  Sumber: 'Peristiwa' | 'Aktivitas';
  Jenis: string;
  Judul: string;
  Isi: string | null;
  Pada: string;
}
