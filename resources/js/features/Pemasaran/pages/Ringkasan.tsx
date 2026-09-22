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

const PINTASAN = [
  {
    label: 'Dashboard Growth',
    href: '/admin-platform/pemasaran/growth',
    keterangan: 'Funnel, KPI, revenue per channel, dan alert growth.',
    izin: 'platform.analytics.lihat',
  },
  {
    label: 'Prospek',
    href: '/admin-platform/pemasaran/prospek',
    keterangan: 'Pipeline, skor, dan aktivitas prospek.',
    izin: 'platform.prospek.lihat',
  },
  {
    label: 'Aturan Skor',
    href: '/admin-platform/pemasaran/prospek/aturan-skor',
    keterangan: 'Bobot tiap sinyal terhadap skor prospek.',
    izin: 'platform.prospek.lihat',
  },
  {
    label: 'Trial',
    href: '/admin-platform/pemasaran/trial',
    keterangan: 'Trial berjalan beserta checklist aktivasinya.',
    izin: 'platform.prospek.lihat',
  },
  {
    label: 'Kampanye',
    href: '/admin-platform/pemasaran/kampanye',
    keterangan: 'Kampanye dan biayanya.',
    izin: 'platform.kampanye.lihat',
  },
  {
    label: 'Demo Produk',
    href: '/admin-platform/pemasaran/demo',
    keterangan: 'Sandbox demo, sesinya, dan reset datasetnya.',
    izin: 'platform.pemasaran.lihat',
  },
  {
    label: 'Halaman Publik',
    href: '/admin-platform/pemasaran/halaman',
    keterangan: 'Landing page beserta versinya.',
    izin: 'platform.halaman.lihat',
  },
  {
    label: 'Formulir',
    href: '/admin-platform/pemasaran/formulir',
    keterangan: 'Formulir publik dan kirimannya.',
    izin: 'platform.halaman.lihat',
  },
  {
    label: 'Redirect',
    href: '/admin-platform/pemasaran/redirect',
    keterangan: 'Peta alih alamat situs publik.',
    izin: 'platform.halaman.lihat',
  },
  {
    label: 'Otomasi',
    href: '/admin-platform/pemasaran/otomasi',
    keterangan: 'Pemicu, kondisi, jeda, dan aksi beserta eksekusinya.',
    izin: 'platform.otomasi.lihat',
  },
  {
    label: 'Template Email',
    href: '/admin-platform/pemasaran/email/template',
    keterangan: 'Naskah email pemasaran dan variabelnya.',
    izin: 'platform.email.lihat',
  },
  {
    label: 'Sequence Email',
    href: '/admin-platform/pemasaran/email/sequence',
    keterangan: 'Rangkaian email onboarding dan jadwalnya.',
    izin: 'platform.email.lihat',
  },
  {
    label: 'Referral',
    href: '/admin-platform/pemasaran/referral',
    keterangan: 'Program referral, kode pelanggan, dan imbalannya.',
    izin: 'platform.referral.lihat',
  },
  {
    label: 'Consent dan Supresi',
    href: '/admin-platform/pemasaran/email/konsen',
    keterangan: 'Siapa boleh dikirimi pesan, dan permintaan penghapusan data.',
    izin: 'platform.email.lihat',
  },
] as const;

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
        <h2 className="text-sm font-medium text-foreground">Halaman Konsol</h2>
        <ul className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          {PINTASAN.filter((satu) => superAdmin || izinSaya.includes(satu.izin)).map((satu) => (
            <li key={satu.href}>
              <Link
                href={satu.href}
                className="block rounded-md border p-3 text-sm transition-colors hover:bg-accent"
              >
                <span className="font-medium text-foreground">{satu.label}</span>
                <span className="mt-0.5 block text-xs text-muted-foreground">{satu.keterangan}</span>
              </Link>
            </li>
          ))}
        </ul>
      </section>

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
