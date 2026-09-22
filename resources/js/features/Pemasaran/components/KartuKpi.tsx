import { Card, CardContent } from '@/components/ui/card';
import { formatAngka } from '@/lib/angka';
import type { KpiGrowth } from '@/features/Pemasaran/types';

const angka = (nilai: number, satuan: string, desimal: number) => {
  if (satuan === 'Uang') {
    return formatAngka(nilai);
  }

  return `${nilai.toLocaleString('id-ID', { maximumFractionDigits: desimal })}${satuan === 'Persen' ? '%' : ''}`;
};

export function KartuKpi({ kpi }: { kpi: KpiGrowth }) {
  return (
    <Card>
      <CardContent className="p-4">
        <p className="text-xs text-muted-foreground">{kpi.Nama}</p>
        <p className="mt-1 font-mono text-2xl font-medium text-foreground">
          {kpi.Nilai === null ? '—' : angka(kpi.Nilai, kpi.Satuan, kpi.Desimal)}
        </p>
        <p className="mt-1 text-xs text-muted-foreground">{kpi.LabelKelompok}</p>
      </CardContent>
    </Card>
  );
}
