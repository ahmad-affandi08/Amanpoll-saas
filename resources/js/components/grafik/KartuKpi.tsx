import { Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Card } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { GrafikKpi } from '@/components/grafik/GrafikKpi';
import { formatNilaiKpi } from '@/features/Pelaporan/format';
import type { BentukKomponen, MetrikKpi } from '@/features/Pelaporan/types';

/**
 * Kartu satu komponen dasbor (arah N, DESIGN.md 13). KPI angka yang berjajar dirender
 * `menyatu` di dalam satu bingkai bersekat garis tipis (lihat Dashboard), bukan kartu
 * terpisah; grafik tetap di kartunya sendiri.
 */
export function KartuKpi({
  kpi,
  bentuk,
  judul,
  lebar,
  menyatu = false,
}: {
  kpi: MetrikKpi;
  bentuk: BentukKomponen;
  judul: string | null;
  lebar: number;
  /** Tanpa bingkai kartu: sel di dalam deret KPI angka. */
  menyatu?: boolean;
}) {
  const kolom =
    {
      1: 'md:col-span-1',
      2: 'md:col-span-2',
      3: 'md:col-span-3',
      4: 'md:col-span-4',
    }[Math.min(4, Math.max(1, lebar))] ?? 'md:col-span-1';

  const tanpaData = kpi.Konteks.AdaData === false;

  const isi = (
    <div className="space-y-2 px-5 py-4">
      <div className="flex items-start justify-between gap-2">
        <p className="min-w-0 truncate text-[13px] text-grafit-700">{judul ?? kpi.Nama}</p>
        <div className="flex shrink-0 items-center gap-1">
          <span className="text-xs text-grafit-500">{kpi.LabelKelompok}</span>
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                aria-label={`Rumus ${kpi.Nama}`}
                className="-my-1 -mr-1.5 flex size-11 shrink-0 items-center justify-center rounded-xs text-grafit-500 hover:bg-grafit-950/5 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:size-6"
              >
                <Info className="size-3.5" />
              </button>
            </TooltipTrigger>
            <TooltipContent className="max-w-xs text-xs leading-relaxed">
              <p className="mb-1 font-semibold">{kpi.Nama}</p>
              <p>{kpi.Formula}</p>
              <p className="mt-1 text-white/75">Sumber: {kpi.Sumber}</p>
              {kpi.Konteks.FilterDimensiBerlaku === false && (
                <p className="mt-1 text-safety-600">Filter unit dan lokasi tidak berlaku untuk KPI ini.</p>
              )}
              {kpi.Konteks.FilterUnitPengelolaBerlaku === false && (
                <p className="mt-1 text-safety-600">Filter unit pengelola tidak berlaku untuk KPI ini.</p>
              )}
            </TooltipContent>
          </Tooltip>
        </div>
      </div>

      {bentuk === 'Angka' ? (
        <div className="space-y-1">
          {/* Figur proporsional, bukan tabular. */}
          <p
            className={cn(
              'text-[26px] leading-tight font-semibold tracking-[-0.015em]',
              tanpaData && 'text-grafit-500',
            )}
          >
            {tanpaData ? '—' : formatNilaiKpi(kpi, true)}
          </p>
          {tanpaData ? (
            <p className="text-[12.5px] text-muted-foreground">Belum ada data pada rentang ini.</p>
          ) : (
            kpi.Konteks.Penyebut !== undefined && (
              <p className="text-[12.5px] text-muted-foreground">
                {kpi.Konteks.Pembilang?.toLocaleString('id-ID')} dari{' '}
                {kpi.Konteks.Penyebut.toLocaleString('id-ID')}
              </p>
            )
          )}
        </div>
      ) : (
        <GrafikKpi kpi={kpi} bentuk={bentuk} />
      )}
    </div>
  );

  if (menyatu) {
    return <div className={cn('col-span-1 min-w-0 border-r border-b border-border', kolom)}>{isi}</div>;
  }

  return <Card className={cn('col-span-1 min-w-0', kolom)}>{isi}</Card>;
}
