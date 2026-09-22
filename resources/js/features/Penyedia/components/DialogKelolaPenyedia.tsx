import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import type { Penyedia, KategoriPenyedia } from '@/features/Penyedia/types';
import { FormInfoPenyedia } from '@/features/Penyedia/components/FormInfoPenyedia';
import { TabKategori } from '@/features/Penyedia/components/TabKategori';
import { TabKontak } from '@/features/Penyedia/components/TabKontak';
import { TabPenilaian } from '@/features/Penyedia/components/TabPenilaian';

export function DialogKelolaPenyedia({
  penyedia,
  kategoriPenyedia,
}: {
  penyedia: Penyedia;
  kategoriPenyedia: KategoriPenyedia[];
}) {
  const [buka, setBuka] = useState(false);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          Kelola
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{penyedia.Nama}</DialogTitle>
        </DialogHeader>
        <Tabs defaultValue="info">
          <TabsList>
            <TabsTrigger value="info">Info</TabsTrigger>
            <TabsTrigger value="kategori">Kategori</TabsTrigger>
            <TabsTrigger value="kontak">Kontak</TabsTrigger>
            <TabsTrigger value="penilaian">Penilaian</TabsTrigger>
            <TabsTrigger value="kolaborasi">Kolaborasi</TabsTrigger>
          </TabsList>
          <TabsContent value="info">
            <FormInfoPenyedia penyedia={penyedia} />
          </TabsContent>
          <TabsContent value="kategori">
            <TabKategori penyedia={penyedia} kategoriPenyedia={kategoriPenyedia} />
          </TabsContent>
          <TabsContent value="kontak">
            <TabKontak penyedia={penyedia} />
          </TabsContent>
          <TabsContent value="penilaian">
            <TabPenilaian penyedia={penyedia} />
          </TabsContent>
          <TabsContent value="kolaborasi">
            <PanelKolaborasi jenisEntitas="Penyedia" entitasId={penyedia.Id} />
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}
