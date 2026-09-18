export interface KunciApi {
  Id: string;
  Nama: string;
  AwalanKunci: string;
  Cakupan: string[] | null;
  AlamatIpDiizinkan: string[] | null;
  KadaluarsaPada: string | null;
  TerakhirDipakaiPada: string | null;
  Status: 'Aktif' | 'Dicabut';
  DibuatPada: string;
}
