import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ComponentProps, ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PropsKartu extends ComponentProps<'div'> {
  /** Beri padding 16px. */
  pad?: boolean;
}

/** Kartu putih radius 20px dengan bayangan lembut (papan acuan `.kartu`). */
export function Kartu({ pad = false, className, ...props }: PropsKartu) {
  return (
    <div
      className={cn('rounded-[20px] bg-white shadow-lapangan-kartu', pad && 'p-4', className)}
      {...props}
    />
  );
}

/**
 * Kartu apung: menimpa 56px bagian bawah hero/appbar panjang (DESIGN.md 36.2).
 * Taruh sebagai anak pertama isi KerangkaLapangan varian `hero` atau `appbar` dengan `panjang`.
 */
export function KartuApung({ pad = false, className, ...props }: PropsKartu) {
  return (
    <div
      className={cn(
        'relative z-10 -mt-14 rounded-[20px] bg-white shadow-lapangan-apung',
        pad && 'p-4',
        className,
      )}
      {...props}
    />
  );
}

interface PropsJudulBagian {
  judul: ReactNode;
  /** Tautan di kanan, mis. "Lihat semua". */
  tautan?: { label: string; href: string };
  /** Isi kanan bebas (menggantikan `tautan`). */
  kanan?: ReactNode;
  className?: string;
}

/** Judul bagian di antara kartu: teks 17px tebal + tautan oranye di kanan. */
export function JudulBagian({ judul, tautan, kanan, className }: PropsJudulBagian) {
  return (
    <div className={cn('flex items-center justify-between gap-3 px-0.5 pt-1', className)}>
      <h2 className="text-[17px] leading-tight font-bold tracking-[-0.01em] text-lapangan-teks">{judul}</h2>
      {kanan ??
        (tautan && (
          <Link
            href={tautan.href}
            className="-my-2 inline-flex min-h-11 items-center text-sm font-bold text-lapangan-oranye-teks"
          >
            {tautan.label}
          </Link>
        ))}
    </div>
  );
}

interface PropsBarisDaftar {
  /** Ikon di kiri, biasanya `<WadahIkon3D ukuran="kecil" />`. */
  ikon?: ReactNode;
  judul: ReactNode;
  keterangan?: ReactNode;
  /** Isi kanan sebelum chevron, mis. nilai "Aktif" atau chip. */
  kanan?: ReactNode;
  /** Tujuan tautan (Inertia). */
  href?: string;
  /** Aksi tombol (dipakai bila tanpa `href`). */
  onClick?: () => void;
  /** Tampilkan chevron kanan. Bawaan: ya bila baris dapat ditekan. */
  chevron?: boolean;
  /** Ikon kanan pengganti chevron. */
  ikonKanan?: ReactNode;
  /** Judul merah (mis. Keluar). */
  bahaya?: boolean;
  className?: string;
}

/**
 * Satu baris menu di dalam `<Kartu>` (papan acuan `.menu-baris`): ikon, judul, keterangan, chevron.
 * Baris berurutan dipisah garis tipis otomatis; bungkus dengan `<Kartu className="overflow-hidden">`.
 */
export function BarisDaftar({
  ikon,
  judul,
  keterangan,
  kanan,
  href,
  onClick,
  chevron,
  ikonKanan,
  bahaya = false,
  className,
}: PropsBarisDaftar) {
  const dapatDitekan = Boolean(href || onClick);
  const tampilChevron = chevron ?? dapatDitekan;

  const isi = (
    <>
      {ikon}
      <span className="min-w-0 flex-1">
        <span
          className={cn(
            'block text-[15px] leading-snug font-bold',
            bahaya ? 'text-lapangan-merah-700' : 'text-lapangan-teks',
          )}
        >
          {judul}
        </span>
        {keterangan && <span className="block text-[13px] text-lapangan-teks-3">{keterangan}</span>}
      </span>
      {kanan}
      {ikonKanan ??
        (tampilChevron && <ChevronRight aria-hidden className="size-[18px] shrink-0 text-lapangan-teks-3" />)}
    </>
  );

  const kelas = cn(
    'flex min-h-14 w-full items-center gap-3 px-4 py-3 text-left [&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2',
    dapatDitekan &&
      'transition-colors hover:bg-lapangan-latar focus-visible:bg-lapangan-latar focus-visible:outline-none',
    className,
  );

  if (href) {
    return (
      <Link href={href} className={kelas}>
        {isi}
      </Link>
    );
  }

  if (onClick) {
    return (
      <button type="button" onClick={onClick} className={kelas}>
        {isi}
      </button>
    );
  }

  return <div className={kelas}>{isi}</div>;
}
