import { useId, useMemo, useState } from 'react';
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Line,
  LineChart,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import { Table2, TrendingUp } from 'lucide-react';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { TooltipGrafik } from '@/components/grafik/TooltipGrafik';
import {
  BATAS_DERET,
  HUE_UTAMA,
  WARNA_GRID,
  WARNA_PERMUKAAN,
  WARNA_SUMBU,
  warnaIrisan,
} from '@/components/grafik/palet';
import { formatNilai, formatSumbu, labelPeriode } from '@/features/Pelaporan/format';
import type { BentukKomponen, MetrikKpi, RincianKpi } from '@/features/Pelaporan/types';

const TINGGI_PLOT = 200;

/** Satu grafik KPI, lengkap dengan legenda, tabel padanan, dan tooltip. */
export function GrafikKpi({ kpi, bentuk }: { kpi: MetrikKpi; bentuk: BentukKomponen }) {
  const [tabelTampil, setTabelTampil] = useState(bentuk === 'Tabel');
  const idJudul = useId();

  const deretWaktu = bentuk === 'Garis';
  const data = useMemo(
    () =>
      (deretWaktu ? kpi.Rincian : lipatEkor(kpi.Rincian)).map((baris) => ({
        ...baris,
        Label: labelTampil(baris.Label),
        Kunci: baris.Label,
      })),
    [kpi.Rincian, deretWaktu],
  );
  const formatter = (nilai: number) => formatNilai(nilai, kpi.SatuanRincian, kpi.DesimalRincian);
  const formatterSumbu = (nilai: number) => formatSumbu(nilai, kpi.SatuanRincian, kpi.DesimalRincian);
  const sumbuBilanganBulat = kpi.SatuanRincian === 'Jumlah';

  if (data.length === 0) {
    return (
      <KeadaanKosong
        judul="Belum ada data pada rentang ini."
        deskripsi="Grafik muncul setelah ada transaksi yang memenuhi filter."
      />
    );
  }

  const tabel = (
    <div className="overflow-x-auto">
      <table className="w-full text-sm" aria-labelledby={idJudul}>
        <caption className="sr-only">Nilai {kpi.Nama} per rincian</caption>
        <thead className="border-b border-border text-left text-xs uppercase text-muted-foreground">
          <tr>
            <th scope="col" className="py-2 pr-3 font-medium">
              Rincian
            </th>
            <th scope="col" className="py-2 text-right font-medium">
              Nilai
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {data.map((baris, indeks) => (
            <tr key={baris.Label}>
              <td className="flex items-center gap-2 py-1.5 pr-3">
                <span
                  aria-hidden="true"
                  className="size-2.5 shrink-0 rounded-[2px]"
                  style={{ backgroundColor: warnaIrisan(baris.Kunci, indeks) }}
                />
                <span className="truncate">{deretWaktu ? labelPeriode(baris.Label) : baris.Label}</span>
              </td>
              <td className="py-1.5 text-right tabular-nums">{formatter(baris.Nilai)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );

  if (bentuk === 'Tabel') {
    return tabel;
  }

  return (
    <div className="space-y-3">
      <div style={{ height: TINGGI_PLOT }}>
        <ResponsiveContainer width="100%" height="100%">
          {bentuk === 'Garis' ? (
            <LineChart data={data} margin={{ top: 8, right: 12, bottom: 4, left: 4 }}>
              <CartesianGrid stroke={WARNA_GRID} strokeWidth={1} vertical={false} />
              <XAxis
                dataKey="Label"
                tickFormatter={labelPeriode}
                tick={{ fill: WARNA_SUMBU, fontSize: 11 }}
                tickLine={false}
                axisLine={{ stroke: WARNA_GRID }}
                minTickGap={24}
              />
              <YAxis
                tick={{ fill: WARNA_SUMBU, fontSize: 11 }}
                tickLine={false}
                axisLine={false}
                width={64}
                allowDecimals={!sumbuBilanganBulat}
                tickFormatter={formatterSumbu}
              />
              <Tooltip
                cursor={{ stroke: WARNA_SUMBU, strokeWidth: 1 }}
                content={(props) => (
                  <TooltipGrafik {...props} formatNilai={formatter} formatLabel={labelPeriode} />
                )}
              />
              <Line
                type="monotone"
                dataKey="Nilai"
                name={kpi.Nama}
                stroke={HUE_UTAMA}
                strokeWidth={2}
                strokeLinecap="round"
                strokeLinejoin="round"
                dot={false}
                activeDot={{ r: 4, strokeWidth: 2, stroke: WARNA_PERMUKAAN }}
              />
            </LineChart>
          ) : bentuk === 'Donat' ? (
            <PieChart margin={{ top: 4, right: 4, bottom: 4, left: 4 }}>
              <Tooltip content={(props) => <TooltipGrafik {...props} formatNilai={formatter} />} />
              <Pie
                data={data}
                dataKey="Nilai"
                nameKey="Label"
                innerRadius="58%"
                outerRadius="82%"
                paddingAngle={2}
                stroke={WARNA_PERMUKAAN}
                strokeWidth={2}
              >
                {data.map((baris, indeks) => (
                  <Cell key={baris.Label} fill={warnaIrisan(baris.Kunci, indeks)} />
                ))}
              </Pie>
            </PieChart>
          ) : (
            <BarChart data={data} layout="vertical" margin={{ top: 4, right: 40, bottom: 4, left: 4 }}>
              <CartesianGrid stroke={WARNA_GRID} strokeWidth={1} horizontal={false} />
              <XAxis
                type="number"
                tick={{ fill: WARNA_SUMBU, fontSize: 11 }}
                tickLine={false}
                axisLine={{ stroke: WARNA_GRID }}
                allowDecimals={!sumbuBilanganBulat}
                tickFormatter={formatterSumbu}
              />
              <YAxis
                type="category"
                dataKey="Label"
                tick={{ fill: WARNA_SUMBU, fontSize: 11 }}
                tickLine={false}
                axisLine={false}
                width={120}
              />
              <Tooltip
                cursor={{ fill: WARNA_GRID }}
                content={(props) => <TooltipGrafik {...props} formatNilai={formatter} />}
              />
              <Bar
                dataKey="Nilai"
                name={kpi.Nama}
                barSize={18}
                radius={[0, 4, 4, 0]}
                isAnimationActive={false}
              >
                {data.map((baris, indeks) => (
                  <Cell key={baris.Label} fill={warnaIrisan(baris.Kunci, indeks)} />
                ))}
              </Bar>
            </BarChart>
          )}
        </ResponsiveContainer>
      </div>

      {/* Legenda wajib untuk dua irisan atau lebih: identitas tidak boleh bergantung pada warna saja. */}
      {!deretWaktu && data.length >= 2 && (
        <ul className="flex flex-wrap gap-x-3 gap-y-1.5 text-xs text-muted-foreground">
          {data.map((baris, indeks) => (
            <li key={baris.Label} className="flex items-center gap-1.5">
              <span
                aria-hidden="true"
                className="size-2.5 shrink-0 rounded-[2px]"
                style={{ backgroundColor: warnaIrisan(baris.Kunci, indeks) }}
              />
              <span className="truncate">{baris.Label}</span>
              <span className="tabular-nums text-foreground">{formatter(baris.Nilai)}</span>
            </li>
          ))}
        </ul>
      )}

      <button
        type="button"
        onClick={() => setTabelTampil((tampil) => !tampil)}
        className="inline-flex min-h-11 items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground sm:min-h-6"
        aria-expanded={tabelTampil}
      >
        {tabelTampil ? <TrendingUp className="size-3.5" /> : <Table2 className="size-3.5" />}
        {tabelTampil ? 'Sembunyikan tabel' : 'Lihat tabel'}
      </button>

      {tabelTampil && tabel}
      <span id={idJudul} className="sr-only">
        {kpi.Nama}
      </span>
    </div>
  );
}

/** Nilai enum dari basis data (`PerluPerhatian`) dipecah menjadi kata agar terbaca: `Perlu Perhatian`. */
function labelTampil(label: string): string {
  return /^[A-Z][a-z]+(?:[A-Z][a-z]+)+$/.test(label) ? label.replace(/([a-z])([A-Z])/g, '$1 $2') : label;
}

/** Melipat ekor menjadi "Lainnya" alih-alih menghasilkan warna kelima. */
function lipatEkor(rincian: RincianKpi[]): RincianKpi[] {
  if (rincian.length <= BATAS_DERET + 1) {
    return rincian;
  }

  const utama = rincian.slice(0, BATAS_DERET);
  const sisa = rincian.slice(BATAS_DERET);
  const jumlahSisa = sisa.reduce((total, baris) => total + baris.Nilai, 0);

  return [...utama, { Label: 'Lainnya', Nilai: jumlahSisa }];
}
