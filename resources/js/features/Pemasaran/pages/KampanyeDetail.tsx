import { Head, Link } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type {
  BiayaKampanye,
  Kampanye,
  KontenKampanye,
  PilihanKampanye,
  TargetKampanye,
} from '@/features/Pemasaran/types';
import { RingkasanKampanye } from '@/features/Pemasaran/components/RingkasanKampanye';
import { KonsolBiaya } from '@/features/Pemasaran/components/KonsolBiaya';
import { KonsolTarget } from '@/features/Pemasaran/components/KonsolTarget';
import { KonsolKonten } from '@/features/Pemasaran/components/KonsolKonten';

interface Props {
  kampanye: Kampanye;
  biaya: BiayaKampanye[];
  target: TargetKampanye[];
  konten: KontenKampanye[];
  pilihan: PilihanKampanye;
}

export default function PemasaranKampanyeDetail({ kampanye, biaya, target, konten, pilihan }: Props) {
  const akar = rutePemasaran.kampanyeDetail(kampanye.Id);
  const totalBiaya = biaya.reduce((jumlah, satu) => jumlah + satu.Jumlah, 0);

  return (
    <KerangkaPlatform>
      <Head title={`Kampanye ${kampanye.Kode}`} />

      <KepalaHalaman
        judul={kampanye.Nama}
        deskripsi={`Kode ${kampanye.Kode} dipakai sebagai utm_campaign pada tautan iklannya.`}
        tanpaBreadcrumb
        aksi={
          <Button variant="outline" asChild>
            <Link href={rutePemasaran.kampanye}>Kembali ke daftar</Link>
          </Button>
        }
        className="mb-6"
      />

      <RingkasanKampanye kampanye={kampanye} pilihan={pilihan} totalBiaya={totalBiaya} />

      <Tabs defaultValue="biaya" className="mt-6">
        <TabsList>
          <TabsTrigger value="biaya">Biaya</TabsTrigger>
          <TabsTrigger value="target">Target</TabsTrigger>
          <TabsTrigger value="konten">Konten</TabsTrigger>
        </TabsList>

        <TabsContent value="biaya" className="mt-4">
          <KonsolBiaya akar={akar} biaya={biaya} channel={kampanye.Channel} total={totalBiaya} />
        </TabsContent>

        <TabsContent value="target" className="mt-4">
          <KonsolTarget akar={akar} target={target} metrik={pilihan.Metrik} metrikUang={pilihan.MetrikUang} />
        </TabsContent>

        <TabsContent value="konten" className="mt-4">
          <KonsolKonten akar={akar} konten={konten} jenis={pilihan.JenisKonten} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}
