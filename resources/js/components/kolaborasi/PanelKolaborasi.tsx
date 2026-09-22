import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { LampiranTab } from './LampiranTab';
import { TagTab } from './TagTab';
import { KolomKustomTab } from './KolomKustomTab';
import { KomentarTab } from './KomentarTab';

interface Props {
  jenisEntitas: string;
  entitasId: string;
}

/** Panel kolaborasi generik: lampiran, komentar, dan riwayat. */
export function PanelKolaborasi({ jenisEntitas, entitasId }: Props) {
  return (
    <div className="rounded-lg border border-border p-4">
      <Tabs defaultValue="lampiran">
        <TabsList>
          <TabsTrigger value="lampiran">Lampiran</TabsTrigger>
          <TabsTrigger value="tag">Tag</TabsTrigger>
          <TabsTrigger value="kolom-kustom">Kolom Kustom</TabsTrigger>
          <TabsTrigger value="komentar">Komentar</TabsTrigger>
        </TabsList>
        <TabsContent value="lampiran">
          <LampiranTab jenisEntitas={jenisEntitas} entitasId={entitasId} />
        </TabsContent>
        <TabsContent value="tag">
          <TagTab jenisEntitas={jenisEntitas} entitasId={entitasId} />
        </TabsContent>
        <TabsContent value="kolom-kustom">
          <KolomKustomTab jenisEntitas={jenisEntitas} entitasId={entitasId} />
        </TabsContent>
        <TabsContent value="komentar">
          <KomentarTab jenisEntitas={jenisEntitas} entitasId={entitasId} />
        </TabsContent>
      </Tabs>
    </div>
  );
}
