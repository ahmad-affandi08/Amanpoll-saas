/** Satu baris hasil dari server; `Url` sudah berupa alamat halaman detailnya. */
export interface HasilPencarian {
  Id: string;
  Judul: string;
  Keterangan: string;
  Url: string;
}

export interface KelompokPencarian {
  Kelompok: string;
  Hasil: HasilPencarian[];
}

/** Halaman dari menu samping yang boleh dibuka pengguna, dicari di sisi klien. */
export interface HalamanTujuan {
  label: string;
  href: string;
  /** Jalur menu, mis. "Operasional & Aset › Manajemen Aset". */
  jalur: string;
}
