export type StatusSertifikasi = 'Aktif' | 'Kedaluwarsa' | 'Dicabut';

export interface SertifikasiAset {
  Id: string;
  AsetId: string;
  KodeAset?: string | null;
  NamaAset?: string | null;
  JenisSertifikasi: string;
  NomorSertifikat: string | null;
  Penerbit: string | null;
  TerbitPada: string | null;
  BerlakuSampai: string | null;
  /** Null bila sertifikat tanpa masa berlaku; negatif berarti sudah lewat. */
  SisaHari: number | null;
  Status: StatusSertifikasi;
  BerkasId: string | null;
  DibuatPada: string;
}
