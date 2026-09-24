import { Head, Link } from '@inertiajs/react';
import { CircleCheck, CircleDashed, Settings } from 'lucide-react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { HALAMAN_PEMASARAN } from '@/features/Pemasaran/navigasi';

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

export default function PemasaranRingkasan({ modul, izinSaya, superAdmin }: Props) {
  const hidup = modul.filter((satu) => satu.Aktif).length;

  return (
    <KerangkaPlatform>
      <Head title="Growth & Marketing" />

      <KepalaHalaman
        judul="Growth & Marketing"
        deskripsi={`${hidup} dari ${modul.length} modul aktif.`}
        tanpaBreadcrumb
        aksi={
          <Button asChild>
            <Link href={rutePemasaran.pengaturan}>
              <Settings aria-hidden="true" className="size-4" />
              Pengaturan
            </Link>
          </Button>
        }
      />

      <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {modul.map((satu) => (
          <Card key={satu.Kode}>
            <CardHeader className="flex-row items-start justify-between gap-3 space-y-0">
              <CardTitle>{satu.Nama}</CardTitle>
              {satu.Aktif ? (
                <Badge variant="sukses" className="gap-1">
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

      <section className="mt-5">
        <h2 className="text-sm font-semibold text-foreground">Halaman Konsol</h2>
        <ul className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          {HALAMAN_PEMASARAN.filter(
            (satu) =>
              (superAdmin || izinSaya.includes(satu.izin)) &&
              (satu.modul === null || modul.some((m) => m.Kode === satu.modul && m.Aktif)),
          ).map((satu) => (
            <li key={satu.href}>
              <Link
                href={satu.href}
                className="block rounded-md border bg-card p-3 text-sm transition-colors hover:border-primary/40 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
              >
                <span className="font-medium text-foreground">{satu.label}</span>
                <span className="mt-0.5 block text-xs text-muted-foreground">{satu.keterangan}</span>
              </Link>
            </li>
          ))}
        </ul>
      </section>

      <section className="mt-5">
        <h2 className="text-sm font-semibold text-foreground">Kewenangan Anda</h2>
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
