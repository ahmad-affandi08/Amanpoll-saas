import { HUE_UTAMA } from '@/components/grafik/palet';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { formatAngka } from '@/lib/angka';
import type { Attribution } from '@/features/Pemasaran/types';

/** Angka revenue tidak berarti apa-apa tanpa menyebut model pembagiannya. */
export function RevenueChannel({
  revenue,
  attribution,
}: {
  revenue: Record<string, number>;
  attribution: Attribution;
}) {
  const baris = Object.entries(revenue);
  const puncak = Math.max(...baris.map(([, nilai]) => nilai), 1);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Revenue per Channel</CardTitle>
        <p className="text-xs text-muted-foreground">
          Model {attribution.Label.toLowerCase()}
          {attribution.Model === 'TimeDecay' ? ` · paruh ${attribution.ParuhHari} hari` : ''} ·{' '}
          {attribution.Keterangan}
        </p>
      </CardHeader>
      <CardContent className="space-y-3">
        {baris.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada pembayaran pada rentang ini.</p>
        ) : (
          baris.map(([channel, nilai]) => (
            <div key={channel}>
              <div className="flex items-baseline justify-between gap-2 text-sm">
                <span className="text-foreground">{channel}</span>
                <span className="font-mono text-foreground">{formatAngka(nilai)}</span>
              </div>
              <div className="mt-1 h-2 rounded-sm bg-muted">
                <div
                  className="h-2 rounded-sm"
                  style={{ width: `${(nilai / puncak) * 100}%`, backgroundColor: HUE_UTAMA }}
                />
              </div>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );
}
