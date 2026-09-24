import { Badge } from '@/components/ui/badge';
import { varianStatus } from '@/features/Pemasaran/status';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatAngka } from '@/lib/angka';
import type { Kampanye, PilihanKampanye } from '@/features/Pemasaran/types';

function Butir({ label, isi }: { label: string; isi: React.ReactNode }) {
  return (
    <div>
      <p className="text-xs text-muted-foreground">{label}</p>
      <div className="mt-0.5 text-foreground">{isi}</div>
    </div>
  );
}

export function RingkasanKampanye({
  kampanye,
  pilihan,
  totalBiaya,
}: {
  kampanye: Kampanye;
  pilihan: PilihanKampanye;
  totalBiaya: number;
}) {
  const utm = [
    ['utm_campaign', kampanye.Kode],
    ['utm_source', kampanye.UtmSource],
    ['utm_medium', kampanye.UtmMedium],
    ['utm_term', kampanye.UtmTerm],
    ['utm_content', kampanye.UtmContent],
  ].filter(([, isi]) => isi);

  return (
    <div className="grid gap-4 lg:grid-cols-3">
      <Card className="lg:col-span-2">
        <CardHeader>
          <CardTitle>Rencana</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
          <Butir
            label="Status"
            isi={<Badge variant={varianStatus(kampanye.Status)}>{kampanye.Status}</Badge>}
          />
          <Butir label="Objective" isi={kampanye.Objective} />
          <Butir
            label="Budget"
            isi={kampanye.Budget === null ? 'Belum ditetapkan' : formatAngka(kampanye.Budget)}
          />
          <Butir label="Sudah dibelanjakan" isi={formatAngka(totalBiaya)} />
          <Butir label="Mulai" isi={kampanye.MulaiPada ?? '—'} />
          <Butir label="Selesai" isi={kampanye.SelesaiPada ?? '—'} />
          <Butir label="Audience" isi={kampanye.Audience ?? '—'} />
          <Butir label="Offer" isi={kampanye.Offer ?? '—'} />
          <Butir label="Landing page" isi={pilihan.Halaman[kampanye.HalamanId ?? ''] ?? '—'} />
          <Butir label="Formulir" isi={pilihan.Formulir[kampanye.FormulirId ?? ''] ?? '—'} />
          <Butir
            label="Channel"
            isi={
              kampanye.Channel.length === 0 ? (
                '—'
              ) : (
                <span className="flex flex-wrap gap-1">
                  {kampanye.Channel.map((satu) => (
                    <Badge key={satu} variant="outline">
                      {satu}
                    </Badge>
                  ))}
                </span>
              )
            }
          />
          <Butir label="Status berikutnya yang sah" isi={kampanye.TujuanStatus.join(', ') || 'Tidak ada'} />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Tag UTM</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          {utm.map(([kunci, isi]) => (
            <div key={kunci} className="flex justify-between gap-2">
              <span className="font-mono text-xs text-muted-foreground">{kunci}</span>
              <span className="font-mono text-foreground">{isi}</span>
            </div>
          ))}
          {kampanye.Catatan ? (
            <p className="border-t pt-2 text-muted-foreground">{kampanye.Catatan}</p>
          ) : null}
        </CardContent>
      </Card>
    </div>
  );
}
