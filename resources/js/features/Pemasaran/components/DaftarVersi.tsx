import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { HalamanDetail, VersiHalaman } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';

export function DaftarVersi({ halaman, versi }: { halaman: HalamanDetail; versi: VersiHalaman[] }) {
  return (
    <div className="grid gap-3">
      {versi.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-4 pt-6">
            <div className="grid gap-1">
              <div className="flex items-center gap-2">
                <span className="font-medium">Versi {satu.Nomor}</span>
                {satu.Terbit ? <Badge variant="sukses">Terbit</Badge> : null}
                {satu.Draf ? <Badge variant="netral">Draf</Badge> : null}
              </div>
              <span className="text-sm text-muted-foreground">
                {satu.Judul}
                {satu.Catatan ? ` · ${satu.Catatan}` : ''}
              </span>
              <span className="font-mono text-xs text-muted-foreground">
                {new Date(satu.DibuatPada).toLocaleString('id-ID')}
              </span>
            </div>

            <div className="flex gap-2">
              <Button variant="ghost" size="sm" asChild>
                <a
                  href={rutePemasaran.halamanPratinjau(halaman.Id, satu.Id)}
                  target="_blank"
                  rel="noreferrer"
                >
                  Pratinjau
                </a>
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={satu.Terbit}
                onClick={() =>
                  router.post(
                    rutePemasaran.halamanKembalikan(halaman.Id, satu.Id),
                    {},
                    { preserveScroll: true },
                  )
                }
              >
                Kembalikan
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
