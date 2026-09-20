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
  kategoriAset?: { Id: string; Nama: string } | null;
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
  templatDaftarPeriksa?: TemplatDaftarPeriksa;
  jawaban?: JawabanDaftarPeriksa[];
  aset?: { Id: string; KodeAset: string; Nama: string } | null;
  perintahKerja?: { Id: string; Nomor: string; Judul: string } | null;
  dilaksanakanOleh?: { Id: string; Nama: string } | null;
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
  templatDaftarPeriksa?: TemplatDaftarPeriksa | null;
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
  kategoriAset?: { Id: string; Nama: string } | null;
  templatDaftarPeriksa?: TemplatDaftarPeriksa | null;
}

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
  templatInspeksi?: TemplatInspeksi;
  aset?: {
    Id: string;
    KodeAset: string;
    Nama: string;
    LokasiId?: string | null;
    lokasi?: { Id: string; Nama: string } | null;
  };
  pelaksanaanDaftarPeriksa?: PelaksanaanDaftarPeriksa | null;
  perintahKerja?: { Id: string; Nomor: string; Judul: string } | null;
  dilaksanakanOleh?: { Id: string; Nama: string } | null;
}
