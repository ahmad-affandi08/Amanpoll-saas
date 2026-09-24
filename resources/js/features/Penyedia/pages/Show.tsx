import { Head } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { formatUang } from '@/lib/uang';
import type { KategoriPenyedia, Penyedia, RingkasanPenyedia } from '@/features/Penyedia/types';
import type { AturanWajib } from '@/lib/aturan-wajib';
import { FormInfoPenyedia } from '@/features/Penyedia/components/FormInfoPenyedia';
import { TabKategori } from '@/features/Penyedia/components/TabKategori';
import { TabKontak } from '@/features/Penyedia/components/TabKontak';
import { TabPenilaian } from '@/features/Penyedia/components/TabPenilaian';
import { TabPengadaan } from '@/features/Penyedia/components/TabPengadaan';
import { TabLayanan } from '@/features/Penyedia/components/TabLayanan';

interface Props {
  penyedia: Penyedia;
  kategoriPenyedia: KategoriPenyedia[];
  ringkasan: RingkasanPenyedia;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function PenyediaShow({ penyedia, kategoriPenyedia, ringkasan, wajib }: Props) {
  const alamat = [penyedia.Kota, penyedia.Provinsi, penyedia.Negara].filter(Boolean).join(', ');

  return (
    <KerangkaAplikasi>
      <Head title={penyedia.Nama} />
      <KepalaHalaman
        className="mb-5"
        judul={penyedia.Nama}
        labelBreadcrumb={penyedia.Nama}
        lencana={<Badge variant={penyedia.Status === 'Aktif' ? 'sukses' : 'netral'}>{penyedia.Status}</Badge>}
        deskripsi={
          <>
            <span className="font-mono">{penyedia.Kode}</span>
            {penyedia.NamaLegal && ` · ${penyedia.NamaLegal}`}
            {alamat && ` · ${alamat}`}
          </>
        }
        meta={
          penyedia.NamaKategoriPenyedia.length > 0 ? (
            <div className="flex flex-wrap gap-1.5">
              {penyedia.NamaKategoriPenyedia.map((nama) => (
                <Badge key={nama} variant="outline">
                  {nama}
                </Badge>
              ))}
            </div>
          ) : undefined
        }
      />

      <DeretStatistik kolom={4} className="mb-5">
        <KartuStatistik menyatu label="Pesanan Pembelian" nilai={ringkasan.JumlahPesanan} />
        <KartuStatistik menyatu label="Nilai Pesanan" nilai={formatUang(ringkasan.NilaiPesanan)} />
        <KartuStatistik
          menyatu
          label="Sisa Tagihan"
          nilai={formatUang(ringkasan.SisaTagihan)}
          keterangan={ringkasan.SisaTagihan > 0 ? 'masih ada yang belum lunas' : 'tidak ada tunggakan'}
        />
        <KartuStatistik
          menyatu
          label="Kontrak Aktif"
          nilai={ringkasan.JumlahKontrakAktif}
          keterangan={`${ringkasan.JumlahAset} aset dipasok`}
        />
      </DeretStatistik>

      <Tabs defaultValue="pengadaan">
        <TabsList>
          <TabsTrigger value="pengadaan">Pengadaan</TabsTrigger>
          <TabsTrigger value="layanan">Kontrak &amp; Layanan</TabsTrigger>
          <TabsTrigger value="kontak">Kontak</TabsTrigger>
          <TabsTrigger value="penilaian">Penilaian</TabsTrigger>
          <TabsTrigger value="kategori">Kategori</TabsTrigger>
          <TabsTrigger value="info">Info</TabsTrigger>
          <TabsTrigger value="kolaborasi">Kolaborasi</TabsTrigger>
        </TabsList>
        <TabsContent value="pengadaan">
          <TabPengadaan penyedia={penyedia} />
        </TabsContent>
        <TabsContent value="layanan">
          <TabLayanan penyedia={penyedia} />
        </TabsContent>
        <TabsContent value="kontak">
          <TabKontak penyedia={penyedia} />
        </TabsContent>
        <TabsContent value="penilaian">
          <TabPenilaian penyedia={penyedia} wajib={wajib.penilaian} />
        </TabsContent>
        <TabsContent value="kategori">
          <TabKategori penyedia={penyedia} kategoriPenyedia={kategoriPenyedia} />
        </TabsContent>
        <TabsContent value="info">
          <FormInfoPenyedia penyedia={penyedia} wajib={wajib.penyedia} />
        </TabsContent>
        <TabsContent value="kolaborasi">
          <PanelKolaborasi jenisEntitas="Penyedia" entitasId={penyedia.Id} />
        </TabsContent>
      </Tabs>
    </KerangkaAplikasi>
  );
}
