import { Fragment } from 'react';
import { cn } from '@/lib/utils';
import type { StatusKeluhanPelapor } from '@/features/Lapangan/types';
import {
  indeksPerhentian,
  PERHENTIAN_LAPORAN,
  teksLangkah,
} from '@/features/Lapangan/components/pelapor/status';

interface PropsJejakLaporan {
  status: StatusKeluhanPelapor;
  className?: string;
}

/**
 * Jejak kemajuan mini lima perhentian (papan pelapor layar 02 dan 09): titik biru
 * yang sudah dilewati, titik oranye bercincin di perhentian saat ini.
 */
export function JejakLaporan({ status, className }: PropsJejakLaporan) {
  const kini = indeksPerhentian(status);
  const tuntas = status === 'Ditutup';

  return (
    <div role="img" aria-label={teksLangkah(status)} className={cn('flex items-center', className)}>
      {PERHENTIAN_LAPORAN.map((label, i) => {
        const lewat = tuntas || i < kini;
        const sekarang = !tuntas && i === kini;

        return (
          <Fragment key={label}>
            {i > 0 && (
              <b
                aria-hidden
                className={cn(
                  'h-[3px] flex-1 rounded-sm',
                  lewat || sekarang ? 'bg-lapangan-biru-600' : 'bg-lapangan-garis',
                )}
              />
            )}
            <i
              aria-hidden
              className={cn(
                'shrink-0 rounded-full',
                sekarang
                  ? 'size-3 bg-lapangan-oranye-700 shadow-[0_0_0_4px_var(--color-lapangan-oranye-100)]'
                  : cn('size-2.5', lewat ? 'bg-lapangan-biru-600' : 'bg-lapangan-garis'),
              )}
            />
          </Fragment>
        );
      })}
    </div>
  );
}
