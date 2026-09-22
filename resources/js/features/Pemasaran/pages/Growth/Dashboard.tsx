import { Head } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type {
  AlertGrowth,
  Attribution,
  BarisCac,
  BarisHalaman,
  BarisKampanye,
  CacTakTerpecah,
  FilterGrowth,
  KpiGrowth,
  PilihanGrowth,
  TahapFunnel,
} from '@/features/Pemasaran/types';
import { BarisFilterGrowth } from '@/features/Pemasaran/components/BarisFilterGrowth';
import { KartuKpi } from '@/features/Pemasaran/components/KartuKpi';
import { Funnel } from '@/features/Pemasaran/components/Funnel';
import { RevenueChannel } from '@/features/Pemasaran/components/RevenueChannel';
import { PerbandinganModel } from '@/features/Pemasaran/components/PerbandinganModel';
import { CacChannel } from '@/features/Pemasaran/components/CacChannel';
import { DaftarAlert } from '@/features/Pemasaran/components/DaftarAlert';
import { TabelKampanye } from '@/features/Pemasaran/components/TabelKampanye';
import { TabelHalaman } from '@/features/Pemasaran/components/TabelHalaman';
import { TabelDefinisi } from '@/features/Pemasaran/components/TabelDefinisi';

interface Props {
  filter: FilterGrowth;
  funnel: TahapFunnel[];
  kpi: KpiGrowth[];
  revenuePerChannel: Record<string, number>;
  attribution: Attribution;
  cacPerChannel: BarisCac[];
  cacTakTerpecah: CacTakTerpecah;
  kampanye: BarisKampanye[];
  halaman: BarisHalaman[];
  alert: AlertGrowth[];
  pilihan: PilihanGrowth;
}

export default function PemasaranGrowthDashboard({
  filter,
  funnel,
  kpi,
  revenuePerChannel,
  attribution,
  cacPerChannel,
  cacTakTerpecah,
  kampanye,
  halaman,
  alert,
  pilihan,
}: Props) {
  const tersedia = kpi.filter((satu) => satu.Tersedia);
  const belum = kpi.filter((satu) => !satu.Tersedia);

  return (
    <KerangkaPlatform>
      <Head title="Dashboard Growth" />

      <KepalaHalaman
        judul="Dashboard Growth"
        deskripsi="Channel mana menghasilkan customer, campaign mana menghasilkan revenue, halaman mana paling efektif."
        tanpaBreadcrumb
        className="mb-6"
      />

      <BarisFilterGrowth filter={filter} pilihan={pilihan} />

      {alert.length > 0 ? <DaftarAlert alert={alert} /> : null}

      <section className="mt-6">
        <h2 className="mb-3 text-sm font-medium text-foreground">KPI Utama</h2>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {tersedia.map((satu) => (
            <KartuKpi key={satu.Kunci} kpi={satu} />
          ))}
        </div>

        {belum.length > 0 ? (
          <div className="mt-3 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            {belum.length} KPI belum dapat dihitung karena sumbernya belum ada:{' '}
            {belum.map((satu) => satu.Nama).join(', ')}. Angkanya sengaja dikosongkan, bukan ditampilkan
            sebagai nol.
          </div>
        ) : null}
      </section>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Funnel funnel={funnel} />
        <RevenueChannel revenue={revenuePerChannel} attribution={attribution} />
      </div>

      <section className="mt-6">
        <PerbandinganModel attribution={attribution} />
      </section>

      <section className="mt-6">
        <CacChannel baris={cacPerChannel} takTerpecah={cacTakTerpecah} />
      </section>

      <Tabs defaultValue="kampanye" className="mt-6">
        <TabsList>
          <TabsTrigger value="kampanye">Kampanye</TabsTrigger>
          <TabsTrigger value="halaman">Landing Page</TabsTrigger>
          <TabsTrigger value="definisi">Definisi KPI</TabsTrigger>
        </TabsList>

        <TabsContent value="kampanye" className="mt-4">
          <TabelKampanye kampanye={kampanye} />
        </TabsContent>

        <TabsContent value="halaman" className="mt-4">
          <TabelHalaman halaman={halaman} />
        </TabsContent>

        <TabsContent value="definisi" className="mt-4">
          <TabelDefinisi kpi={kpi} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}
