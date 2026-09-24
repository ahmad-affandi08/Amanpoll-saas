import type { NamaIkon3D, TintIkon } from '@/components/shared/Ikon3D';

interface PadananIkon {
  pola: RegExp;
  ikon: NamaIkon3D;
  tint: TintIkon;
}

/**
 * Pemetaan nama kategori/jenis aset atau kategori keluhan ke ikon 3D (DESIGN.md 36.5).
 * Dicocokkan dari kata kunci karena nama kategori ditentukan tenant; yang tidak cocok
 * memakai `toolbox`.
 */
const PADANAN: PadananIkon[] = [
  { pola: /genset|generator|baterai|ups\b|aki\b/i, ikon: 'battery', tint: 'hijau' },
  { pola: /\blift\b|elevator|eskalator/i, ikon: 'elevator', tint: 'ungu' },
  { pola: /\bac\b|pendingin|udara|hvac|chiller|kulkas|freezer/i, ikon: 'snowflake', tint: 'biru' },
  { pola: /pompa|\bair\b|pipa|plumbing|sanitasi|keran/i, ikon: 'droplet', tint: 'biru' },
  { pola: /forklift|kendaraan|truk|alat berat/i, ikon: 'articulated_lorry', tint: 'kuning' },
  { pola: /cctv|kamera|keamanan|security/i, ikon: 'video_camera', tint: 'hijau' },
  { pola: /panel|lvmdp|mdp|kelistrikan|stop ?kontak/i, ikon: 'electric_plug', tint: 'kuning' },
  { pola: /listrik|elektrikal|daya/i, ikon: 'high_voltage', tint: 'kuning' },
  { pola: /apar|pemadam|hydrant|kebakaran/i, ikon: 'fire_extinguisher', tint: 'merah' },
  { pola: /lampu|penerangan/i, ikon: 'light_bulb', tint: 'kuning' },
  { pola: /printer|pencetak/i, ikon: 'printer', tint: 'ungu' },
  { pola: /\bit\b|komputer|laptop|jaringan|server/i, ikon: 'desktop_computer', tint: 'ungu' },
  { pola: /bangunan|gedung|sipil|atap|dinding|lantai/i, ikon: 'brick', tint: 'oranye' },
  { pola: /pintu|jendela/i, ikon: 'door', tint: 'oranye' },
];

/** Ikon 3D dan tint wadah untuk sebuah nama kategori; bawaan `toolbox` bertint merah muda seperti "Lainnya". */
export function ikonKategori(nama: string | null | undefined): { ikon: NamaIkon3D; tint: TintIkon } {
  if (nama) {
    const cocok = PADANAN.find((satu) => satu.pola.test(nama));
    if (cocok) return { ikon: cocok.ikon, tint: cocok.tint };
  }

  return { ikon: 'toolbox', tint: 'merah' };
}
