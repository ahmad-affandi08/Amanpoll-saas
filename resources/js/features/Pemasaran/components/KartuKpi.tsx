import { KartuStatistik } from '@/components/shared/KartuStatistik';
import { formatAngka } from '@/lib/angka';
import type { KpiGrowth } from '@/features/Pemasaran/types';

const angka = (nilai: number, satuan: string, desimal: number) => {
  if (satuan === 'Uang') {
    return formatAngka(nilai);
  }

  return `${nilai.toLocaleString('id-ID', { maximumFractionDigits: desimal })}${satuan === 'Persen' ? '%' : ''}`;
};

/** Satu sel KPI di dalam `DeretStatistik`. */
export function KartuKpi({ kpi }: { kpi: KpiGrowth }) {
  return (
    <KartuStatistik
      menyatu
      label={kpi.Nama}
      nilai={kpi.Nilai === null ? '—' : angka(kpi.Nilai, kpi.Satuan, kpi.Desimal)}
      keterangan={kpi.LabelKelompok}
    />
  );
}
