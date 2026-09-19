export interface UnitOrganisasi {
  Id: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  Jenis: string;
  Email: string | null;
  Telepon: string | null;
  Status: 'Aktif' | 'Nonaktif';
  Urutan: number;
  DibuatPada: string;
}
