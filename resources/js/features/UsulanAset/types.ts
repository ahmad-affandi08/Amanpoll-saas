export type StatusUsulanAset = 'Draft' | 'Diajukan' | 'MenungguPersetujuan' | 'Disetujui' | 'Ditolak';
export type PrioritasUsulanAset = 'Rendah' | 'Normal' | 'Tinggi' | 'Kritis';

export interface PenilaianUsulanAset {
  Id: string;
  Kriteria: string;
  Bobot: string;
  Nilai: string;
  Skor: string;
  NamaPenilai: string | null;
  DinilaiPada: string;
}

export interface UsulanAset {
  Id: string;
  Nomor: string;
  UnitOrganisasiId: string;
  NamaUnitOrganisasi: string;
  KategoriAsetId: string | null;
  NamaKategoriAset: string | null;
  ModelAsetId: string | null;
  NamaModelAset: string | null;
  NamaKebutuhan: string;
  Jumlah: string;
  EstimasiHargaSatuan: string | null;
  Alasan: string;
  JenisKebutuhan: string | null;
  TahunKebutuhan: number | null;
  Prioritas: PrioritasUsulanAset;
  Status: StatusUsulanAset;
  NamaPengaju: string;
  DiajukanPada: string | null;
  Penilaian?: PenilaianUsulanAset[];
  DibuatPada: string;
}
