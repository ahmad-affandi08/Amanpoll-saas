import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
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
  className?: string;
}

const IKON_ARAH: Record<Arah, LucideIcon> = {
  naik: ArrowUpRight,
  turun: ArrowDownRight,
  tetap: Minus,
};

/** Kartu angka tunggal (DESIGN.md 13). */
export function KartuStatistik({ label, nilai, keterangan, ikon: Ikon, perubahan, className }: Props) {
  const IkonArah = perubahan ? IKON_ARAH[perubahan.arah] : null;
  const naikItuBaik = perubahan?.naikItuBaik ?? true;

  const warnaPerubahan =
    perubahan?.arah === 'tetap'
      ? 'text-muted-foreground'
      : (perubahan?.arah === 'naik') === naikItuBaik
        ? 'text-emerald-600'
        : 'text-destructive';

  return (
    <Card className={cn('min-w-0', className)}>
      <CardContent className="space-y-1.5 p-4">
        <div className="flex items-start justify-between gap-2">
          <p className="min-w-0 truncate text-xs font-medium uppercase tracking-wide text-muted-foreground">
            {label}
          </p>
          {Ikon && <Ikon aria-hidden="true" className="size-4 shrink-0 text-muted-foreground" />}
        </div>

        <p className="text-2xl font-semibold tabular-nums tracking-tight text-foreground">{nilai}</p>

        {(keterangan || perubahan) && (
          <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs">
            {perubahan && IkonArah && (
              <span className={cn('inline-flex items-center gap-0.5 font-medium', warnaPerubahan)}>
                <IkonArah aria-hidden="true" className="size-3.5" />
                {perubahan.teks}
              </span>
            )}
            {keterangan && <span className="text-muted-foreground">{keterangan}</span>}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
