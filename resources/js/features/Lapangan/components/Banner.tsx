import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Ikon3D, type NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';

const KELAS_GRADIEN = {
  oranye: 'gradien-banner-oranye-lapangan',
  biru: 'gradien-banner-biru-lapangan',
  hijau: 'gradien-banner-hijau-lapangan',
} as const;

interface PropsBanner {
  /** Gradien kartu. */
  warna: keyof typeof KELAS_GRADIEN;
  judul: ReactNode;
  teks?: ReactNode;
  /** Ikon 3D besar di kanan. */
  ikon?: NamaIkon3D;
  /** Seluruh banner menjadi tautan. */
  href?: string;
  /** Isi tambahan di bawah teks (mis. tombol kecil). Jangan dipakai bersama `href`. */
  aksi?: ReactNode;
  /** Ringkas (tinggi 76px) untuk karosel. */
  ringkas?: boolean;
  className?: string;
}

/** Banner info (DESIGN.md 36.3): kartu gradien oranye, biru, atau hijau dengan ikon 3D besar di kanan. */
export function Banner({ warna, judul, teks, ikon, href, aksi, ringkas = false, className }: PropsBanner) {
  const kelas = cn(
    'relative flex items-center gap-3 overflow-hidden rounded-[18px] text-white',
    ringkas ? 'min-h-[76px] px-4 py-3' : 'min-h-24 p-4',
    KELAS_GRADIEN[warna],
    href && 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/60',
    className,
  );

  const isi = (
    <>
      <div className="min-w-0 flex-1">
        <b className={cn('block font-bold', ringkas ? 'text-[15px]' : 'text-base')}>{judul}</b>
        {teks && <span className="mt-0.5 block text-[13px] opacity-95">{teks}</span>}
        {aksi && <div className="mt-2.5">{aksi}</div>}
      </div>
      {ikon && (
        <Ikon3D
          nama={ikon}
          ukuran={ringkas ? 58 : 'banner'}
          className={ringkas ? '-my-1 -mr-1' : '-my-3 -mr-1'}
        />
      )}
    </>
  );

  if (href) {
    return (
      <Link href={href} className={kelas}>
        {isi}
      </Link>
    );
  }

  return <div className={kelas}>{isi}</div>;
}

const KELAS_PITA = {
  kuning: {
    kotak: 'bg-lapangan-kuning-50 shadow-[inset_0_0_0_1.5px_rgb(246_226_166)]',
    tautan: 'text-lapangan-kuning-700',
  },
  merah: {
    kotak: 'bg-lapangan-merah-50 shadow-[inset_0_0_0_1.5px_rgb(179_38_30_/_0.2)]',
    tautan: 'text-lapangan-merah-700',
  },
  biru: {
    kotak: 'bg-lapangan-biru-50 shadow-[inset_0_0_0_1.5px_rgb(42_123_176_/_0.2)]',
    tautan: 'text-lapangan-biru-600',
  },
  hijau: {
    kotak: 'bg-lapangan-hijau-50 shadow-[inset_0_0_0_1.5px_rgb(14_122_79_/_0.2)]',
    tautan: 'text-lapangan-hijau-700',
  },
} as const;

interface PropsPitaInfo {
  nada: keyof typeof KELAS_PITA;
  judul: ReactNode;
  teks?: ReactNode;
  ikon?: NamaIkon3D;
  /** Tautan di kanan, mis. `{ label: 'Lihat', href: ruteLapangan.akun }`. */
  tautan?: { label: string; href: string };
  className?: string;
}

/** Pita info bertint (papan Teknisi layar 16: "3 perubahan menunggu dikirim · Lihat"). */
export function PitaInfo({ nada, judul, teks, ikon, tautan, className }: PropsPitaInfo) {
  const setelan = KELAS_PITA[nada];

  return (
    <div
      role="status"
      className={cn('flex items-center gap-3 rounded-[18px] px-3.5 py-3', setelan.kotak, className)}
    >
      {ikon && <Ikon3D nama={ikon} ukuran={44} />}
      <div className="min-w-0 flex-1">
        <b className="block text-[15px] leading-[1.3] font-bold text-lapangan-teks">{judul}</b>
        {teks && <span className="mt-0.5 block text-[13px] leading-[1.4] text-lapangan-teks-2">{teks}</span>}
      </div>
      {tautan && (
        <Link
          href={tautan.href}
          className={cn(
            '-my-2 inline-flex min-h-11 items-center text-[13px] font-bold whitespace-nowrap',
            setelan.tautan,
          )}
        >
          {tautan.label}
        </Link>
      )}
    </div>
  );
}
