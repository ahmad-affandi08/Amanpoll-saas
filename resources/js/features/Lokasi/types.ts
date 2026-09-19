export interface KategoriLokasi {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  DibuatPada: string;
}

export interface Lokasi {
  Id: string;
  IndukId: string | null;
  UnitOrganisasiId: string | null;
  KategoriLokasiId: string | null;
  Kode: string;
  Nama: string;
  Alamat: string | null;
  Lantai: string | null;
  Latitude: string | null;
  Longitude: string | null;
  ZonaWaktu: string | null;
  Status: 'Aktif' | 'Nonaktif';
  NamaKategoriLokasi: string | null;
  NamaUnitOrganisasi: string | null;
  DibuatPada: string;
}
