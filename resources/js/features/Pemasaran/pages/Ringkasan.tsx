import { Head, Link } from '@inertiajs/react';
import { CircleCheck, CircleDashed, Settings } from 'lucide-react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageHeader } from '@/components/shared/PageHeader';

interface Modul {
  Kode: string;
  Nama: string;
  Keterangan: string;
  Aktif: boolean;
}

interface Props {
  modul: Modul[];
  izinSaya: string[];
  superAdmin: boolean;
}

export default function Ringkasan({ modul, izinSaya, superAdmin }: Props) {
  const hidup = modul.filter((satu) => satu.Aktif).length;

  return (
    <KerangkaPlatform>
      <Head title="Growth & Marketing" />

      <PageHeader
        judul="Growth & Marketing"
        deskripsi={`${hidup} dari ${modul.length} modul aktif.`}
        tanpaBreadcrumb
        aksi={
          <Button asChild>
            <Link href="/admin-platform/pemasaran/pengaturan">
              <Settings aria-hidden="true" className="size-4" />
              Pengaturan
            </Link>
          </Button>
        }
      />

      <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {modul.map((satu) => (
          <Card key={satu.Kode}>
            <CardHeader className="flex-row items-start justify-between gap-3 space-y-0">
              <CardTitle className="text-base">{satu.Nama}</CardTitle>
              {satu.Aktif ? (
                <Badge variant="secondary" className="gap-1">
                  <CircleCheck aria-hidden="true" className="size-3.5" />
                  Aktif
                </Badge>
              ) : (
                <Badge variant="outline" className="gap-1 text-muted-foreground">
                  <CircleDashed aria-hidden="true" className="size-3.5" />
                  Belum aktif
                </Badge>
              )}
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">{satu.Keterangan}</CardContent>
          </Card>
        ))}
      </div>

      <section className="mt-8">
        <h2 className="text-sm font-medium text-foreground">Kewenangan Anda</h2>
        {superAdmin ? (
          <p className="mt-2 text-sm text-muted-foreground">
            Super admin platform: seluruh izin pemasaran berlaku.
          </p>
        ) : izinSaya.length === 0 ? (
          <p className="mt-2 text-sm text-muted-foreground">
            Belum ada izin pemasaran yang diberikan ke akun ini.
          </p>
        ) : (
          <ul className="mt-2 flex flex-wrap gap-2">
            {izinSaya.map((kode) => (
              <li key={kode}>
                <Badge variant="outline" className="font-mono text-xs">
                  {kode}
                </Badge>
              </li>
            ))}
          </ul>
        )}
      </section>
    </KerangkaPlatform>
  );
}
