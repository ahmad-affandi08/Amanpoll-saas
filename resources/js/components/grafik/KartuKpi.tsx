import { Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Card, CardContent } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { GrafikKpi } from '@/components/grafik/GrafikKpi';
import { formatNilaiKpi } from '@/features/Pelaporan/format';
import type { BentukKomponen, MetrikKpi } from '@/features/Pelaporan/types';

/** Kartu satu komponen dasbor. */
export function KartuKpi({
  kpi,
  bentuk,
  judul,
  lebar,
}: {
  kpi: MetrikKpi;
  bentuk: BentukKomponen;
  judul: string | null;
  lebar: number;
}) {
  const kolom =
    {
      1: 'md:col-span-1',
      2: 'md:col-span-2',
      3: 'md:col-span-3',
      4: 'md:col-span-4',
    }[Math.min(4, Math.max(1, lebar))] ?? 'md:col-span-1';

  const tanpaData = kpi.Konteks.AdaData === false;

  return (
    <Card className={cn('col-span-1', kolom)}>
      <CardContent className="space-y-3 p-4">
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0">
            <p className="truncate text-sm text-muted-foreground">{judul ?? kpi.Nama}</p>
            <p className="text-xs text-muted-foreground/80">{kpi.LabelKelompok}</p>
          </div>
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                aria-label={`Rumus ${kpi.Nama}`}
                className="flex size-11 shrink-0 items-center justify-center rounded-[5px] text-muted-foreground hover:bg-permukaan-100 hover:text-foreground sm:size-7"
              >
                <Info className="size-4" />
              </button>
            </TooltipTrigger>
            <TooltipContent className="max-w-xs text-xs leading-relaxed">
              <p className="mb-1 font-semibold">{kpi.Nama}</p>
              <p>{kpi.Formula}</p>
              <p className="mt-1 text-muted-foreground">Sumber: {kpi.Sumber}</p>
              {kpi.Konteks.FilterDimensiBerlaku === false && (
                <p className="mt-1 text-safety-600">Filter unit dan lokasi tidak berlaku untuk KPI ini.</p>
              )}
            </TooltipContent>
          </Tooltip>
        </div>

        {bentuk === 'Angka' ? (
          <div>
            {/* Figur proporsional, bukan tabular. */}
            <p className="text-3xl font-semibold tracking-tight">
              {tanpaData ? '—' : formatNilaiKpi(kpi, true)}
            </p>
            {tanpaData ? (
              <p className="text-xs text-muted-foreground">Belum ada data pada rentang ini.</p>
            ) : (
              kpi.Konteks.Penyebut !== undefined && (
                <p className="text-xs text-muted-foreground">
                  {kpi.Konteks.Pembilang?.toLocaleString('id-ID')} dari{' '}
                  {kpi.Konteks.Penyebut.toLocaleString('id-ID')}
                </p>
              )
            )}
          </div>
        ) : (
          <GrafikKpi kpi={kpi} bentuk={bentuk} />
        )}
      </CardContent>
    </Card>
  );
}
