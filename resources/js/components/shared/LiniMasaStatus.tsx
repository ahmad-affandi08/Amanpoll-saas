import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type NadaTahap = 'selesai' | 'berjalan' | 'menunggu' | 'gagal';

export interface TahapStatus {
  id: string;
  label: string;
  nada: NadaTahap;
  waktu?: string | null;
  oleh?: string | null;
  catatan?: ReactNode;
}

const GAYA_TITIK: Record<NadaTahap, string> = {
  selesai: 'border-sukses-600 bg-sukses-600',
  berjalan: 'border-primary bg-background',
  menunggu: 'border-border bg-background',
  gagal: 'border-destructive bg-destructive',
};

const LABEL_NADA: Record<NadaTahap, string> = {
  selesai: 'selesai',
  berjalan: 'sedang berjalan',
  menunggu: 'menunggu',
  gagal: 'gagal',
};

/** Riwayat status sebuah entitas (DESIGN.md 12). */
export function LiniMasaStatus({ tahap, className }: { tahap: TahapStatus[]; className?: string }) {
  if (tahap.length === 0) {
    return null;
  }

  return (
    <ol className={cn('space-y-0', className)}>
      {tahap.map((satu, indeks) => {
        const terakhir = indeks === tahap.length - 1;

        return (
          <li key={satu.id} className="flex gap-3">
            <div className="flex flex-col items-center">
              <span
                aria-hidden="true"
                className={cn('mt-1 size-2.5 shrink-0 rounded-full border-2', GAYA_TITIK[satu.nada])}
              />
              {!terakhir && <span aria-hidden="true" className="w-px flex-1 bg-border" />}
            </div>

            <div className={cn('min-w-0 flex-1', terakhir ? 'pb-0' : 'pb-4')}>
              <p className="text-sm font-medium text-foreground">
                {satu.label}
                <span className="sr-only"> — {LABEL_NADA[satu.nada]}</span>
              </p>

              {(satu.waktu || satu.oleh) && (
                <p className="text-xs text-muted-foreground">
                  {[satu.waktu, satu.oleh].filter(Boolean).join(' · ')}
                </p>
              )}

              {satu.catatan && <div className="pt-1 text-sm text-muted-foreground">{satu.catatan}</div>}
            </div>
          </li>
        );
      })}
    </ol>
  );
}
