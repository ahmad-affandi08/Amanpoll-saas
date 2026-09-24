import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Arah = 'naik' | 'turun' | 'tetap';

interface Props {
  label: string;
  nilai: ReactNode;
  /** Keterangan singkat di bawah angka, mis. rentang waktunya. */
  keterangan?: ReactNode;
  ikon?: LucideIcon;
  perubahan?: {
    arah: Arah;
    teks: string;
    /** Apakah arah naik berarti kabar baik; menentukan warnanya. */
    naikItuBaik?: boolean;
  };
  /** Sel di dalam `DeretStatistik`: tanpa bingkai kartu, disekat garis tipis. */
  menyatu?: boolean;
  className?: string;
}

const IKON_ARAH: Record<Arah, LucideIcon> = {
  naik: ArrowUpRight,
  turun: ArrowDownRight,
  tetap: Minus,
};

/** Kartu angka tunggal (DESIGN.md 13). */
export function KartuStatistik({
  label,
  nilai,
  keterangan,
  ikon: Ikon,
  perubahan,
  menyatu = false,
  className,
}: Props) {
  const IkonArah = perubahan ? IKON_ARAH[perubahan.arah] : null;
  const naikItuBaik = perubahan?.naikItuBaik ?? true;

  const warnaPerubahan =
    perubahan?.arah === 'tetap'
      ? 'text-muted-foreground'
      : (perubahan?.arah === 'naik') === naikItuBaik
        ? 'text-sukses-700'
        : 'text-destructive';

  const isi = (
    <div className="space-y-1.5 px-5 py-4">
      <div className="flex items-start justify-between gap-2">
        <p className="min-w-0 truncate text-[13px] text-grafit-700">{label}</p>
        {Ikon && <Ikon aria-hidden="true" className="size-4 shrink-0 text-muted-foreground" />}
      </div>

      <p className="text-[26px] leading-tight font-semibold tabular-nums tracking-[-0.015em] text-foreground">
        {nilai}
      </p>

      {(keterangan || perubahan) && (
        <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[12.5px]">
          {perubahan && IkonArah && (
            <span className={cn('inline-flex items-center gap-0.5 font-medium', warnaPerubahan)}>
              <IkonArah aria-hidden="true" className="size-3.5" />
              {perubahan.teks}
            </span>
          )}
          {keterangan && <span className="text-muted-foreground">{keterangan}</span>}
        </div>
      )}
    </div>
  );

  if (menyatu) {
    return <div className={cn('min-w-0 border-r border-b border-border', className)}>{isi}</div>;
  }

  return <Card className={cn('min-w-0', className)}>{isi}</Card>;
}

const KOLOM_DERET = {
  2: 'sm:grid-cols-2',
  3: 'sm:grid-cols-3',
  4: 'grid-cols-2 lg:grid-cols-4',
  5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
} as const;

/**
 * Deret angka ringkas (arah N, DESIGN.md 13): beberapa `KartuStatistik menyatu` di dalam
 * satu bingkai bersekat garis tipis, pengganti jajaran kartu terpisah. Sekat kanan dan
 * bawah sel paling luar terpotong oleh overflow-hidden lewat margin negatif.
 */
export function DeretStatistik({
  kolom = 4,
  className,
  children,
}: {
  kolom?: keyof typeof KOLOM_DERET;
  className?: string;
  children: ReactNode;
}) {
  return (
    <div className={cn('overflow-hidden rounded-md border border-border bg-card', className)}>
      <div className={cn('-mr-px -mb-px grid grid-cols-1', KOLOM_DERET[kolom])}>{children}</div>
    </div>
  );
}
