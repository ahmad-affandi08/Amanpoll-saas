import { Head } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import type { Aset, KategoriAset, ModelAset } from '@/features/Aset/types';
import type { Lokasi } from '@/features/Lokasi/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import type { Penyedia } from '@/features/Penyedia/types';
import { VARIAN_BADGE_STATUS_ASET } from '@/features/Aset/status';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TabInfo } from '@/features/Aset/components/TabInfo';
import { TabLokasi } from '@/features/Aset/components/TabLokasi';
import { TabPenanggungJawab } from '@/features/Aset/components/TabPenanggungJawab';
import { TabRelasi } from '@/features/Aset/components/TabRelasi';
import { TabGaransi } from '@/features/Aset/components/TabGaransi';
import { TabNilai } from '@/features/Aset/components/TabNilai';
import { TabMeter } from '@/features/Aset/components/TabMeter';

interface Props {
  aset: Aset;
  kategoriAset: KategoriAset[];
  modelAset: ModelAset[];
  penyedia: Penyedia[];
  unitOrganisasi: UnitOrganisasi[];
  lokasi: Lokasi[];
}

export default function AsetShow({ aset, kategoriAset, modelAset, penyedia, unitOrganisasi, lokasi }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title={aset.Nama} />
      <KepalaHalaman
        className="mb-6"
        judul={aset.Nama}
        labelBreadcrumb={aset.KodeAset}
        lencana={<Badge variant={VARIAN_BADGE_STATUS_ASET[aset.Status]}>{aset.Status}</Badge>}
        deskripsi={
          <span className="font-mono">
            {aset.KodeAset}
            {aset.KodeQr && ` · QR: ${aset.KodeQr}`}
          </span>
        }
      />

      <Tabs defaultValue="info">
        <TabsList>
          <TabsTrigger value="info">Info</TabsTrigger>
          <TabsTrigger value="lokasi">Lokasi</TabsTrigger>
          <TabsTrigger value="penanggung-jawab">Penanggung Jawab</TabsTrigger>
          <TabsTrigger value="relasi">Relasi</TabsTrigger>
          <TabsTrigger value="garansi">Garansi</TabsTrigger>
          <TabsTrigger value="nilai">Nilai</TabsTrigger>
          <TabsTrigger value="meter">Meter</TabsTrigger>
          <TabsTrigger value="kolaborasi">Kolaborasi</TabsTrigger>
        </TabsList>
        <TabsContent value="info">
          <TabInfo
            aset={aset}
            kategoriAset={kategoriAset}
            modelAset={modelAset}
            penyedia={penyedia}
            unitOrganisasi={unitOrganisasi}
          />
        </TabsContent>
        <TabsContent value="lokasi">
          <TabLokasi aset={aset} lokasi={lokasi} />
        </TabsContent>
        <TabsContent value="penanggung-jawab">
          <TabPenanggungJawab aset={aset} unitOrganisasi={unitOrganisasi} />
        </TabsContent>
        <TabsContent value="relasi">
          <TabRelasi aset={aset} />
        </TabsContent>
        <TabsContent value="garansi">
          <TabGaransi aset={aset} penyedia={penyedia} />
        </TabsContent>
        <TabsContent value="nilai">
          <TabNilai aset={aset} />
        </TabsContent>
        <TabsContent value="meter">
          <TabMeter aset={aset} />
        </TabsContent>
        <TabsContent value="kolaborasi">
          <PanelKolaborasi jenisEntitas="Aset" entitasId={aset.Id} />
        </TabsContent>
      </Tabs>
    </KerangkaAplikasi>
  );
}
