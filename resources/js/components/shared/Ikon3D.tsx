import { cn } from '@/lib/utils';

/**
 * Nama ikon 3D (Microsoft Fluent Emoji 3D, MIT) yang tersedia di `public/images/3d/<nama>.png`.
 * Dipakai Mode Lapangan (DESIGN.md 36.5) dan halaman autentikasi (DESIGN.md 37), karena itu
 * komponennya bersama, bukan milik satu fitur. Hanya ikon yang benar-benar dipakai: menambah
 * ikon berarti menyalin berkasnya ke `public/images/3d/` lalu menambah namanya di sini
 * (`Ikon3DTersediaTest` memeriksa keduanya cocok).
 */
export const NAMA_IKON_3D = [
  'alarm_clock',
  'articulated_lorry',
  'battery',
  'bell',
  'bookmark_tabs',
  'brick',
  'camera',
  'camera_with_flash',
  'card_index_dividers',
  'check_mark_button',
  'clipboard',
  'cloud',
  'construction',
  'cross_mark',
  'desktop_computer',
  'door',
  'droplet',
  'electric_plug',
  'elevator',
  'factory',
  'fire',
  'fire_extinguisher',
  'gear',
  'hammer_and_wrench',
  'handshake',
  'headphone',
  'high_voltage',
  'hospital',
  'hotel',
  'hourglass_done',
  'key',
  'light_bulb',
  'locked_with_key',
  'magnifying_glass_tilted_left',
  'man_mechanic',
  'megaphone',
  'memo',
  'mobile_phone',
  'nut_and_bolt',
  'office_building',
  'package',
  'party_popper',
  'police_car_light',
  'printer',
  'rocket',
  'round_pushpin',
  'satellite_antenna',
  'school',
  'shield',
  'snowflake',
  'sparkles',
  'spiral_calendar',
  'star',
  'stopwatch',
  'telephone_receiver',
  'thumbs_up',
  'ticket',
  'toolbox',
  'trophy',
  'video_camera',
  'warning',
  'waving_hand',
  'wrench',
] as const;

export type NamaIkon3D = (typeof NAMA_IKON_3D)[number];

/** Tint wadah ikon 3D (papan acuan Mode Lapangan: `.t-oranye`, `.t-hijau`, dst.). */
export type TintIkon = 'biru' | 'oranye' | 'hijau' | 'kuning' | 'merah' | 'ungu' | 'putih' | 'latar';

/** Ukuran bernama (px). Angka bebas juga diterima. */
const UKURAN_IKON = {
  xs: 20,
  sm: 26,
  md: 32,
  lg: 40,
  xl: 56,
  banner: 76,
  ilustrasi: 104,
} as const;

export type UkuranIkon3D = keyof typeof UKURAN_IKON | number;

export function sumberIkon3D(nama: NamaIkon3D): string {
  return `/images/3d/${nama}.png`;
}

interface PropsIkon3D {
  nama: NamaIkon3D;
  /** Bawaan `md` (32px). */
  ukuran?: UkuranIkon3D;
  /**
   * Teks alternatif. Kosongkan (bawaan) bila ikon hanya hiasan di samping teks yang sudah
   * menjelaskan maknanya; isi bila ikon berdiri sendiri membawa makna.
   */
  alt?: string;
  /** Muat segera (ikon di atas lipatan, mis. ilustrasi layar sukses). Bawaan: lazy. */
  segera?: boolean;
  className?: string;
}

/** Ikon 3D clay. Selalu di atas wadah tint (`WadahIkon3D`) atau berdiri bebas sebagai ilustrasi. */
export function Ikon3D({ nama, ukuran = 'md', alt = '', segera = false, className }: PropsIkon3D) {
  const piksel = typeof ukuran === 'number' ? ukuran : UKURAN_IKON[ukuran];
  const hiasan = alt === '';

  return (
    <img
      src={sumberIkon3D(nama)}
      alt={alt}
      aria-hidden={hiasan ? true : undefined}
      width={piksel}
      height={piksel}
      loading={segera ? 'eager' : 'lazy'}
      decoding="async"
      draggable={false}
      className={cn(
        'block shrink-0 object-contain drop-shadow-[0_4px_6px_rgb(11_34_57_/_0.15)] select-none',
        className,
      )}
      style={{ width: piksel, height: piksel }}
    />
  );
}

const KELAS_TINT: Record<TintIkon, string> = {
  biru: 'bg-lapangan-biru-50',
  oranye: 'bg-lapangan-oranye-50',
  hijau: 'bg-lapangan-hijau-50',
  kuning: 'bg-lapangan-kuning-50',
  merah: 'bg-lapangan-merah-50',
  ungu: 'bg-lapangan-ungu-50',
  putih: 'bg-white',
  latar: 'bg-lapangan-latar',
};

/** Ukuran wadah: kecil 40 (ikon 26), sedang 48 (32), menu 60 (40), besar 64 (46). */
const UKURAN_WADAH = {
  kecil: { wadah: 'size-10 rounded-xl', ikon: 26 },
  sedang: { wadah: 'size-12 rounded-[14px]', ikon: 32 },
  menu: { wadah: 'size-[60px] rounded-[18px]', ikon: 40 },
  besar: { wadah: 'size-16 rounded-[18px]', ikon: 46 },
} as const;

export type UkuranWadahIkon3D = keyof typeof UKURAN_WADAH;

interface PropsWadahIkon3D {
  nama: NamaIkon3D;
  /** Bawaan `biru`. */
  tint?: TintIkon;
  /** Bawaan `sedang`. */
  ukuran?: UkuranWadahIkon3D;
  alt?: string;
  className?: string;
}

/** Ikon 3D di atas wadah tint bersudut 12–18px (DESIGN.md 36.5). */
export function WadahIkon3D({ nama, tint = 'biru', ukuran = 'sedang', alt, className }: PropsWadahIkon3D) {
  const setelan = UKURAN_WADAH[ukuran];

  return (
    <span
      className={cn('flex shrink-0 items-center justify-center', setelan.wadah, KELAS_TINT[tint], className)}
    >
      <Ikon3D nama={nama} ukuran={setelan.ikon} alt={alt} />
    </span>
  );
}

/** Kelas latar tint, untuk komponen lain yang memakai wadah bertint sendiri. */
export function kelasTint(tint: TintIkon): string {
  return KELAS_TINT[tint];
}
