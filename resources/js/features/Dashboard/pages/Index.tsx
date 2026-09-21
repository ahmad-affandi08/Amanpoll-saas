import { Head, Link, router } from '@inertiajs/react';
import { LayoutGrid, SlidersHorizontal } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { KartuKpi } from '@/components/grafik/KartuKpi';
import { BarisFilter } from '@/features/Pelaporan/components/BarisFilter';
import { rutePelaporan } from '@/features/Pelaporan/api';
import type {
  DasborTersimpanRingkas,
  FilterMetrik,
  MetrikKpi,
  PilihanDimensi,
  SusunanDasbor,
} from '@/features/Pelaporan/types';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  susunan: SusunanDasbor;
  metrik: Record<string, MetrikKpi>;
  filter: FilterMetrik;
  dasborTersimpan: DasborTersimpanRingkas[];
  pilihanUnit: PilihanDimensi[];
  pilihanLokasi: PilihanDimensi[];
}

/**
 * Dasbor operasional (21.02).
 *
 * Susunan datang dari server — preset sesuai kewenangan atau dasbor tersimpan
 * pilihan pengguna — dan setiap kartu hanya dirender bila KPI-nya benar-benar
 * dihitung. Komponen yang KPI-nya disaring izin hilang dari layar alih-alih
 * menjadi kartu kosong.
 */
export default function Dashboard({
  susunan,
  metrik,
  filter,
  dasborTersimpan,
  pilihanUnit,
  pilihanLokasi,
}: Props) {
  const komponenTampil = susunan.Komponen.filter((komponen) => metrik[komponen.KunciKpi] !== undefined);

  const gantiDasbor = (nilai: string) => {
    router.get(
      rutePelaporan.dasbor,
      { ...filter, dasbor: nilai },
      { preserveState: true, preserveScroll: true },
    );
  };

  return (
    <AppLayout>
      <Head title="Dashboard" />
      <div className="space-y-5">
        <PageHeader
          judul={susunan.Nama}
          deskripsi="Ringkasan operasional Amanpoll. Setiap angka membawa rumusnya sendiri."
          aksi={
            <>
              <div className="flex flex-wrap items-center gap-2">
                <Select value={susunan.Kunci} onValueChange={gantiDasbor}>
                  <SelectTrigger className="w-[13rem]">
                    <SelectValue placeholder="Pilih dasbor" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="preset">Bawaan sesuai peran</SelectItem>
                    {dasborTersimpan.map((dasbor) => (
                      <SelectItem key={dasbor.Id} value={dasbor.Id}>
                        {dasbor.Nama}
                        {dasbor.Bawaan ? ' (bawaan)' : ''}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Button variant="outline" size="sm" asChild>
                  <Link href={rutePelaporan.dasborKustom}>
                    <LayoutGrid className="size-4" />
                    Atur dasbor
                  </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                  <Link href={rutePelaporan.laporan}>
                    <SlidersHorizontal className="size-4" />
                    Laporan
                  </Link>
                </Button>
              </div>
            </>
          }
        />

        <BarisFilter
          filter={filter}
          pilihanUnit={pilihanUnit}
          pilihanLokasi={pilihanLokasi}
          url={rutePelaporan.dasbor}
          paramTambahan={{ dasbor: susunan.Kunci }}
        />

        {komponenTampil.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/dashboard-analitik.webp"
            judul="Belum ada KPI yang dapat ditampilkan."
            deskripsi="Dasbor ini kosong karena kewenangan Anda belum mencakup KPI di dalamnya, atau komponennya belum dipilih."
            aksi={
              <Button asChild size="sm">
                <Link href={rutePelaporan.dasborKustom}>Atur dasbor</Link>
              </Button>
            }
          />
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
            {komponenTampil.map((komponen) => (
              <KartuKpi
                key={komponen.Id}
                kpi={metrik[komponen.KunciKpi]}
                bentuk={komponen.Bentuk}
                judul={komponen.Judul}
                lebar={komponen.Lebar}
              />
            ))}
          </div>
        )}

        <footer className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
          <Badge variant="netral">{komponenTampil.length} komponen</Badge>
          <span>
            Rentang {filter.Dari} s.d. {filter.Sampai}
          </span>
        </footer>
      </div>
    </AppLayout>
  );
}
