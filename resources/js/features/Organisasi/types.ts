export interface Organisasi {
  Id: string;
  Kode: string;
  Nama: string;
  NamaLegal: string | null;
  JenisUsaha: string | null;
  NomorIdentitasPajak: string | null;
  Email: string | null;
  Telepon: string | null;
  Alamat: string | null;
  Negara: string | null;
  Provinsi: string | null;
  Kota: string | null;
  ZonaWaktu: string;
  LogoUrl: string | null;
  Status: string;
  DibuatPada: string;
}
