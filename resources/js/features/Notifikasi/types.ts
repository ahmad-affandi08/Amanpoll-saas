export type KanalNotifikasi = 'InApp' | 'Email' | 'WhatsApp';

export interface Notifikasi {
  Id: string;
  Kanal: string;
  JenisPeristiwa: string;
  Judul: string | null;
  Isi: string;
  JenisEntitas: string | null;
  EntitasId: string | null;
  Status: string;
  DibacaPada: string | null;
  DibuatPada: string;
}

export interface TemplatNotifikasi {
  Id: string;
  Kode: string;
  Kanal: KanalNotifikasi;
  JudulTemplat: string | null;
  IsiTemplat: string;
  Variabel: string[] | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface PreferensiBaris {
  JenisPeristiwa: string;
  Label: string;
  Kanal: KanalNotifikasi;
  Aktif: boolean;
}

/** Mengapa WhatsApp belum akan datang walau preferensinya menyala. */
export interface KesiapanWhatsApp {
  PenyediaAktif: boolean;
  NomorValid: boolean;
}
