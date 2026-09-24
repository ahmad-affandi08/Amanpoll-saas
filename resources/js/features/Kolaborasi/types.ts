export interface Berkas {
  Id: string;
  NamaAsli: string;
  JenisMime: string | null;
  /** Ukuran yang diterima saat mengunduh. */
  UkuranByte: number | null;
  /** Gambar yang dikodekan ulang diunduh sebagai `.webp`. */
  NamaUnduhan: string;
  MetodeKompresi: 'Tidak' | 'Gzip' | 'GambarUlang';
  UkuranAsliByte: number | null;
  UkuranTersimpanByte: number | null;
  DiunggahOleh: string | null;
  DibuatPada: string;
}

export interface LampiranEntitas {
  Id: string;
  JenisEntitas: string;
  EntitasId: string;
  BerkasId: string;
  Kategori: string | null;
  Keterangan: string | null;
  Berkas: Berkas | null;
  DibuatPada: string;
}

export interface Tag {
  Id: string;
  Nama: string;
  Warna: string | null;
  DibuatPada: string;
}

export interface EntitasTag {
  Id: string;
  TagId: string;
  JenisEntitas: string;
  EntitasId: string;
  Tag: Tag | null;
  DibuatPada: string;
}

export type TipeDataKolomKustom = 'Teks' | 'Angka' | 'Tanggal' | 'Boolean' | 'Pilihan' | 'PilihanGanda';

export interface DefinisiKolomKustom {
  Id: string;
  JenisEntitas: string;
  Kode: string;
  Label: string;
  TipeData: TipeDataKolomKustom;
  Wajib: boolean;
  Pilihan: string[] | null;
  AturanValidasi: string[] | null;
  NilaiBawaan: unknown;
  Urutan: number;
  Aktif: boolean;
  DibuatPada: string;
}

export interface NilaiKolomKustom {
  Id: string;
  DefinisiKolomKustomId: string;
  JenisEntitas: string;
  EntitasId: string;
  Nilai: unknown;
  Definisi: DefinisiKolomKustom | null;
  DibuatPada: string;
}

export interface KomentarEntitas {
  Id: string;
  JenisEntitas: string;
  EntitasId: string;
  IndukKomentarId: string | null;
  Isi: string;
  DibuatOleh: string;
  NamaPembuat: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}
