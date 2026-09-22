import { Head, Link } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { EmptyState } from '@/components/shared/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Formulir, PengirimanFormulir } from '@/features/Pemasaran/types';

interface Props {
  formulir: Formulir;
  pengiriman: PengirimanFormulir[];
}

export default function Show({ formulir, pengiriman }: Props) {
  return (
    <KerangkaPlatform>
      <Head title={formulir.Nama} />

      <PageHeader
        judul={formulir.Nama}
        deskripsi={`Kode ${formulir.Kode} · ${formulir.JumlahPengiriman} pengiriman`}
        tanpaBreadcrumb
        lencana={formulir.Aktif ? <Badge>Aktif</Badge> : <Badge variant="outline">Nonaktif</Badge>}
        aksi={
          <Button variant="ghost" asChild>
            <Link href="/admin-platform/pemasaran/formulir">Kembali</Link>
          </Button>
        }
        className="mb-6"
      />

      {pengiriman.length === 0 ? (
        <EmptyState
          judul="Belum ada pengiriman"
          deskripsi="Pengiriman muncul di sini segera setelah formulir dipasang pada halaman terbit."
        />
      ) : (
        <div className="grid gap-3">
          {pengiriman.map((satu) => (
            <Card key={satu.Id}>
              <CardContent className="grid gap-3 pt-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <span className="font-mono text-xs text-muted-foreground">
                    {new Date(satu.DikirimPada).toLocaleString('id-ID')}
                  </span>
                  <div className="flex gap-2">
                    {satu.Persetujuan ? <Badge variant="secondary">Setuju</Badge> : null}
                    {satu.ProspekId ? (
                      <Button variant="ghost" size="sm" asChild>
                        <Link href={`/admin-platform/pemasaran/prospek/${satu.ProspekId}`}>
                          {satu.ProspekNama ?? 'Prospek'}
                        </Link>
                      </Button>
                    ) : null}
                  </div>
                </div>

                <dl className="grid gap-2 sm:grid-cols-2">
                  {Object.entries(satu.Data).map(([kunci, nilai]) => (
                    <div key={kunci} className="text-sm">
                      <dt className="text-muted-foreground">{kunci}</dt>
                      <dd className="break-words">
                        {nilai === null || nilai === '' ? '—' : String(nilai)}
                      </dd>
                    </div>
                  ))}
                </dl>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}
