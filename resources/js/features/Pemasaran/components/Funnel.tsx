import { HUE_UTAMA } from '@/components/grafik/palet';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { TahapFunnel } from '@/features/Pemasaran/types';

/** Magnitudo per tahap berurutan: satu deret, satu hue, tanpa legenda. */
export function Funnel({ funnel }: { funnel: TahapFunnel[] }) {
  const puncak = Math.max(...funnel.map((satu) => satu.Jumlah), 1);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Funnel Visitor → Paid</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {funnel.map((tahap) => (
          <div key={tahap.Tahap}>
            <div className="flex items-baseline justify-between gap-2 text-sm">
              <span className="font-medium text-foreground">{tahap.Tahap}</span>
              <span className="font-mono text-foreground">
                {tahap.Jumlah.toLocaleString('id-ID')}
                {tahap.PersenDariSebelumnya === null ? null : (
                  <span className="ml-2 text-xs text-muted-foreground">{tahap.PersenDariSebelumnya}%</span>
                )}
              </span>
            </div>
            <div
              className="mt-1 h-2 rounded-sm bg-muted"
              role="img"
              aria-label={`${tahap.Tahap}: ${tahap.Jumlah}, sumber ${tahap.Sumber}`}
            >
              <div
                className="h-2 rounded-sm"
                style={{
                  width: `${Math.max((tahap.Jumlah / puncak) * 100, tahap.Jumlah > 0 ? 2 : 0)}%`,
                  backgroundColor: HUE_UTAMA,
                }}
              />
            </div>
            <p className="mt-0.5 text-xs text-muted-foreground">Sumber: {tahap.Sumber}</p>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
