export type TipeJawabanDaftarPeriksa = 'Teks' | 'Angka' | 'Pilihan' | 'YaTidak' | 'Foto';

export interface ButirTemplatDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  TemplatDaftarPeriksaId: string;
  Urutan: number;
  Kode?: string | null;
  Pertanyaan: string;
  TipeJawaban: TipeJawabanDaftarPeriksa;
  Satuan?: string | null;
  Wajib: boolean;
  NilaiMinimum?: number | null;
  NilaiMaksimum?: number | null;
  Pilihan?: string[] | null;
  BuktiFotoWajib: boolean;
  MemicuTemuanJika?: {
    nilai?: string | boolean;
    diLuarBatas?: boolean;
  } | null;
  DibuatPada?: string;
}

export interface TemplatDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  KategoriAsetId?: string | null;
  ModelAsetId?: string | null;
  VersiTemplat: number;
  Aktif: boolean;
  DibuatPada?: string;
  DiperbaruiPada?: string;
  butir_count?: number;
  butir?: ButirTemplatDaftarPeriksa[];
  kategori_aset?: { Id: string; Nama: string } | null;
  modelAset?: { Id: string; Nama: string } | null;
}

export interface JawabanDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  PelaksanaanDaftarPeriksaId: string;
  ButirTemplatDaftarPeriksaId: string;
  NilaiTeks?: string | null;
  NilaiAngka?: number | null;
  NilaiBoolean?: boolean | null;
  NilaiTanggal?: string | null;
  NilaiJson?: any | null;
  Sesuai?: boolean | null;
  Catatan?: string | null;
  DijawabPada?: string | null;
  butirTemplatDaftarPeriksa?: ButirTemplatDaftarPeriksa;
}

export interface PelaksanaanDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  TemplatDaftarPeriksaId: string;
  PerintahKerjaId?: string | null;
  AsetId?: string | null;
  DilaksanakanOleh?: string | null;
  MulaiPada?: string | null;
  SelesaiPada?: string | null;
  Status: 'Draft' | 'SedangDikerjakan' | 'Selesai' | 'Dibatalkan';
  Skor?: number | null;
  Catatan?: string | null;
  DibuatPada?: string;
  templat_daftar_periksa?: TemplatDaftarPeriksa;
  jawaban?: JawabanDaftarPeriksa[];
  aset?: { Id: string; KodeAset: string; Nama: string } | null;
  perintah_kerja?: { Id: string; Nomor: string; Judul: string } | null;
  dilaksanakan_oleh?: { Id: string; Nama: string } | null;
}

export interface RencanaPemeliharaan {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  TemplatDaftarPeriksaId?: string | null;
  Prioritas: string;
  StrategiJadwal: string;
  IntervalNilai: number;
  IntervalSatuan: string;
  BerdasarkanMeter: boolean;
  AmbangMeter?: number | null;
  ToleransiHari: number;
  BuatPerintahKerjaHariSebelum: number;
  Aktif: boolean;
  DibuatPada?: string;
  DiperbaruiPada?: string;
  aset_count?: number;
  templat_daftar_periksa?: TemplatDaftarPeriksa | null;
  aset?: RencanaPemeliharaanAset[];
}

export interface RencanaPemeliharaanAset {
  Id: string;
  OrganisasiId: string;
  RencanaPemeliharaanId: string;
  AsetId: string;
  TanggalMulai: string;
  TanggalBerikutnya?: string | null;
  NilaiMeterBerikutnya?: number | null;
  TerakhirDilaksanakanPada?: string | null;
  Aktif: boolean;
  DibuatPada?: string;
  DiperbaruiPada?: string;
  aset?: {
    Id: string;
    KodeAset: string;
    Nama: string;
    LokasiId?: string | null;
    lokasi?: { Id: string; Nama: string } | null;
  };
  jadwal?: JadwalPemeliharaan[];
}

export interface JadwalPemeliharaan {
  Id: string;
  OrganisasiId: string;
  RencanaPemeliharaanAsetId: string;
  PerintahKerjaId?: string | null;
  TanggalJadwal: string;
  Status: string;
  DihasilkanOtomatis: boolean;
  DibuatPada?: string;
}

export interface TemplatInspeksi {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  KategoriAsetId?: string | null;
  TemplatDaftarPeriksaId?: string | null;
  IntervalHari: number;
  Aktif: boolean;
  DibuatPada?: string;
  inspeksi_count?: number;
  kategori_aset?: { Id: string; Nama: string } | null;
  templat_daftar_periksa?: TemplatDaftarPeriksa | null;
}

/**
 * Baris daftar templat disusun manual di controller dengan relasi camelCase,
 * berbeda dari model mentah di halaman detail yang relasinya snake_case.
 */
export type BarisTemplatInspeksi = Omit<TemplatInspeksi, 'kategori_aset' | 'templat_daftar_periksa'> & {
  kategoriAset?: { Id: string; Nama: string } | null;
  templatDaftarPeriksa?: { Id: string; Kode: string; Nama: string } | null;
};

export type BarisTemplatDaftarPeriksa = Omit<TemplatDaftarPeriksa, 'kategori_aset'> & {
  kategoriAset?: { Id: string; Nama: string } | null;
};

export interface Inspeksi {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  TemplatInspeksiId: string;
  AsetId: string;
  PelaksanaanDaftarPeriksaId?: string | null;
  DijadwalkanPada?: string | null;
  DilaksanakanPada?: string | null;
  Status: 'Terjadwal' | 'SedangDikerjakan' | 'Selesai' | 'Dibatalkan';
  Hasil?: 'Lolos' | 'PerluPerhatian' | 'Gagal' | null;
  Temuan?: string | null;
  TindakLanjut?: string | null;
  PerintahKerjaId?: string | null;
  DilaksanakanOleh?: string | null;
  DibuatPada?: string;
  DiperbaruiPada?: string;
  templat_inspeksi?: TemplatInspeksi;
  aset?: {
    Id: string;
    KodeAset: string;
    Nama: string;
    LokasiId?: string | null;
    lokasi?: { Id: string; Nama: string } | null;
  };
  pelaksanaan_daftar_periksa?: PelaksanaanDaftarPeriksa | null;
  perintah_kerja?: { Id: string; Nomor: string; Judul: string } | null;
  dilaksanakan_oleh?: { Id: string; Nama: string } | null;
}
