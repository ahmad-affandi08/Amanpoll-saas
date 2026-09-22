import { router } from '@inertiajs/react';
import { AlertTriangle, CircleAlert, Info } from 'lucide-react';
import { WARNA_STATUS } from '@/components/grafik/palet';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { AlertGrowth } from '@/features/Pemasaran/types';

export function DaftarAlert({ alert }: { alert: AlertGrowth[] }) {
  const ikon = (tingkat: string) => {
    if (tingkat === 'Kritis') return <CircleAlert aria-hidden="true" className="size-4 shrink-0" />;
    if (tingkat === 'Peringatan') return <AlertTriangle aria-hidden="true" className="size-4 shrink-0" />;

    return <Info aria-hidden="true" className="size-4 shrink-0" />;
  };

  const warna = (tingkat: string) =>
    tingkat === 'Kritis'
      ? WARNA_STATUS.bahaya
      : tingkat === 'Peringatan'
        ? WARNA_STATUS.perhatian
        : WARNA_STATUS.info;

  return (
    <section className="mt-6 space-y-2">
      <h2 className="text-sm font-medium text-foreground">Alert</h2>
      {alert.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div className="flex min-w-0 items-start gap-2">
              <span style={{ color: warna(satu.Tingkat) }}>{ikon(satu.Tingkat)}</span>
              <div className="min-w-0">
                <p className="text-sm font-medium text-foreground">{satu.Judul}</p>
                <p className="text-xs text-muted-foreground">{satu.Isi}</p>
              </div>
            </div>
            <div className="flex shrink-0 items-center gap-2">
              <Badge variant="outline">{satu.Tingkat}</Badge>
              <Button
                variant="ghost"
                size="sm"
                onClick={() =>
                  router.post(rutePemasaran.growthAlertSelesai(satu.Id), {}, { preserveScroll: true })
                }
              >
                Selesai
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </section>
  );
}
