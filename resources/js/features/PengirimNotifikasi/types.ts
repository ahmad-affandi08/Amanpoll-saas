import type { PenyediaLayanan } from '@/features/Platform/types';

export interface PenyediaOrganisasi extends PenyediaLayanan {
  /** Kegagalan terakhir lebih baru daripada keberhasilan terakhir. */
  Bermasalah: boolean;
  GalatTerakhir: string | null;
  TerakhirBerhasilPada: string | null;
  TerakhirGagalPada: string | null;
}

export interface KategoriPengirim {
  Kode: 'Email' | 'WhatsApp';
  Label: string;
  Penyedia: PenyediaOrganisasi[];
}

export interface KuotaWhatsApp {
  Terpakai: number;
  /** `null` berarti tanpa batas. */
  Batas: number | null;
  Sisa: number | null;
  TermasukPaket: boolean;
  Habis: boolean;
}
