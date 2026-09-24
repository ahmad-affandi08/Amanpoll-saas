import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Badge } from '@/components/ui/badge';
import { varianStatus } from '@/features/Pemasaran/status';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { BlokDisunting, HalamanDetail, PilihanHalaman, VersiHalaman } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { KartuBlok } from '@/features/Pemasaran/components/KartuBlok';
import { PanelPenerbitan } from '@/features/Pemasaran/components/PanelPenerbitan';
import { DaftarVersi } from '@/features/Pemasaran/components/DaftarVersi';

interface Props {
  halaman: HalamanDetail | null;
  versi: VersiHalaman[];
  pilihan: PilihanHalaman;
}

let penghitungKunci = 0;

function kunciBaru(): string {
  penghitungKunci += 1;

  return `blok-${penghitungKunci}`;
}

export default function PemasaranHalamanEditor({ halaman, versi, pilihan }: Props) {
  const form = useForm({
    Slug: halaman?.Slug ?? '/',
    Tipe: halaman?.Tipe ?? pilihan.Tipe[0],
    Judul: halaman?.Judul ?? '',
    Segmen: halaman?.Segmen ?? '',
    KampanyeId: halaman?.KampanyeId ?? '',
    NoIndex: halaman?.NoIndex ?? false,
    MetaJudul: halaman?.MetaJudul ?? '',
    MetaDeskripsi: halaman?.MetaDeskripsi ?? '',
    Kanonik: halaman?.Kanonik ?? '',
    OgJudul: halaman?.OgJudul ?? '',
    OgDeskripsi: halaman?.OgDeskripsi ?? '',
    OgGambar: halaman?.OgGambar ?? '',
    SkemaTipe: halaman?.SkemaTipe ?? '',
    Catatan: '',
  });

  /* Daftar blok dipegang terpisah dari form dan baru disatukan saat dikirim. */
  const [blok, setBlok] = useState<BlokDisunting[]>(() =>
    (halaman?.Blok ?? []).map((satu): BlokDisunting => ({ ...satu, Kunci: kunciBaru() })),
  );

  const simpan = (e: FormEvent) => {
    e.preventDefault();

    form.transform((data) => ({
      ...data,
      Blok: blok.map(({ Kunci: _kunci, ...sisa }) => sisa),
    }));

    if (halaman) {
      form.put(rutePemasaran.halamanDetail(halaman.Id), { preserveScroll: true });
    } else {
      form.post(rutePemasaran.halaman);
    }
  };

  const ubahBlok = (urutan: number, ubahan: Partial<BlokDisunting>) => {
    setBlok((sebelum) => sebelum.map((satu, ke) => (ke === urutan ? { ...satu, ...ubahan } : satu)));
  };

  const tambahBlok = () => {
    setBlok((sebelum) => [
      ...sebelum,
      { Jenis: pilihan.Blok[0], Isi: {}, FormulirKode: null, Kunci: kunciBaru() },
    ]);
  };

  const hapusBlok = (urutan: number) => {
    setBlok((sebelum) => sebelum.filter((_, ke) => ke !== urutan));
  };

  const pindahBlok = (urutan: number, arah: -1 | 1) => {
    setBlok((sebelum) => {
      const tujuan = urutan + arah;

      if (tujuan < 0 || tujuan >= sebelum.length) {
        return sebelum;
      }

      const daftar = [...sebelum];
      [daftar[urutan], daftar[tujuan]] = [daftar[tujuan], daftar[urutan]];

      return daftar;
    });
  };

  return (
    <KerangkaPlatform>
      <Head title={halaman ? halaman.Judul : 'Halaman Baru'} />

      <KepalaHalaman
        judul={halaman ? halaman.Judul : 'Halaman Baru'}
        deskripsi={halaman ? halaman.Slug : 'Setiap penyimpanan melahirkan versi baru.'}
        tanpaBreadcrumb
        lencana={halaman ? <Badge variant={varianStatus(halaman.Status)}>{halaman.Status}</Badge> : undefined}
        aksi={
          <div className="flex flex-wrap gap-2">
            <Button variant="ghost" asChild>
              <Link href={rutePemasaran.halaman}>Kembali</Link>
            </Button>
            <Button onClick={simpan} disabled={form.processing}>
              Simpan Draf
            </Button>
          </div>
        }
        className="mb-5"
      />

      <Tabs defaultValue="isi">
        <TabsList>
          <TabsTrigger value="isi">Isi</TabsTrigger>
          <TabsTrigger value="seo">SEO</TabsTrigger>
          <TabsTrigger value="terbit">Penerbitan</TabsTrigger>
          <TabsTrigger value="versi">Versi</TabsTrigger>
        </TabsList>

        <TabsContent value="isi" className="grid gap-4 pt-4">
          <Card>
            <CardHeader>
              <CardTitle>Identitas</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <div className="grid content-start gap-2">
                <Label htmlFor="Judul">Judul</Label>
                <Input
                  id="Judul"
                  value={form.data.Judul}
                  onChange={(e) => form.setData('Judul', e.target.value)}
                />
                {form.errors.Judul ? <p className="text-sm text-destructive">{form.errors.Judul}</p> : null}
              </div>

              <div className="grid content-start gap-2">
                <Label htmlFor="Slug">Slug</Label>
                <Input
                  id="Slug"
                  value={form.data.Slug}
                  onChange={(e) => form.setData('Slug', e.target.value)}
                  placeholder="/industri/manufaktur"
                />
                {form.errors.Slug ? <p className="text-sm text-destructive">{form.errors.Slug}</p> : null}
              </div>

              <div className="grid content-start gap-2">
                <Label htmlFor="Tipe">Tipe</Label>
                <Select value={form.data.Tipe} onValueChange={(v) => form.setData('Tipe', v)}>
                  <SelectTrigger id="Tipe">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {pilihan.Tipe.map((satu) => (
                      <SelectItem key={satu} value={satu}>
                        {satu}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid content-start gap-2">
                <Label htmlFor="Segmen">Segmen</Label>
                <Input
                  id="Segmen"
                  value={form.data.Segmen}
                  onChange={(e) => form.setData('Segmen', e.target.value)}
                  list="segmen-bawaan"
                />
                <datalist id="segmen-bawaan">
                  {Object.entries(pilihan.Segmen).map(([kode, nama]) => (
                    <option key={kode} value={kode}>
                      {nama}
                    </option>
                  ))}
                </datalist>
              </div>
            </CardContent>
          </Card>

          <div className="flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Blok</h2>
            <Button variant="outline" size="sm" onClick={tambahBlok}>
              Tambah Blok
            </Button>
          </div>

          {blok.length === 0 ? (
            <KeadaanKosong
              judul="Belum ada blok"
              deskripsi="Halaman kosong tidak dapat diterbitkan. Tambahkan setidaknya satu blok hero."
            />
          ) : (
            blok.map((satu, urutan) => (
              <KartuBlok
                key={satu.Kunci}
                blok={satu}
                urutan={urutan}
                total={blok.length}
                pilihan={pilihan}
                ubah={(ubahan) => ubahBlok(urutan, ubahan)}
                hapus={() => hapusBlok(urutan)}
                pindah={(arah) => pindahBlok(urutan, arah)}
              />
            ))
          )}
        </TabsContent>

        <TabsContent value="seo" className="pt-4">
          <Card>
            <CardHeader>
              <CardTitle>Metadata</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <div className="grid content-start gap-2">
                <Label htmlFor="MetaJudul">Meta title</Label>
                <Input
                  id="MetaJudul"
                  value={form.data.MetaJudul}
                  onChange={(e) => form.setData('MetaJudul', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor="Kanonik">Canonical</Label>
                <Input
                  id="Kanonik"
                  value={form.data.Kanonik}
                  onChange={(e) => form.setData('Kanonik', e.target.value)}
                />
                {form.errors.Kanonik ? (
                  <p className="text-sm text-destructive">{form.errors.Kanonik}</p>
                ) : null}
              </div>
              <div className="grid gap-2 sm:col-span-2">
                <Label htmlFor="MetaDeskripsi">Meta description</Label>
                <Textarea
                  id="MetaDeskripsi"
                  rows={3}
                  value={form.data.MetaDeskripsi}
                  onChange={(e) => form.setData('MetaDeskripsi', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor="OgJudul">Open Graph title</Label>
                <Input
                  id="OgJudul"
                  value={form.data.OgJudul}
                  onChange={(e) => form.setData('OgJudul', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor="OgGambar">Open Graph image</Label>
                <Input
                  id="OgGambar"
                  value={form.data.OgGambar}
                  onChange={(e) => form.setData('OgGambar', e.target.value)}
                />
              </div>
              <div className="grid gap-2 sm:col-span-2">
                <Label htmlFor="OgDeskripsi">Open Graph description</Label>
                <Textarea
                  id="OgDeskripsi"
                  rows={2}
                  value={form.data.OgDeskripsi}
                  onChange={(e) => form.setData('OgDeskripsi', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor="SkemaTipe">Schema type</Label>
                <Input
                  id="SkemaTipe"
                  value={form.data.SkemaTipe}
                  onChange={(e) => form.setData('SkemaTipe', e.target.value)}
                  placeholder="WebPage"
                />
              </div>
              <label className="flex items-center gap-2 self-end text-sm">
                <Checkbox
                  checked={form.data.NoIndex}
                  onCheckedChange={(nilai) => form.setData('NoIndex', nilai === true)}
                />
                Larang pengindeksan (noindex)
              </label>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="terbit" className="pt-4">
          {halaman === null ? (
            <KeadaanKosong
              judul="Simpan dulu"
              deskripsi="Penerbitan tersedia setelah halaman punya versi pertamanya."
            />
          ) : (
            <PanelPenerbitan halaman={halaman} pilihan={pilihan} />
          )}
        </TabsContent>

        <TabsContent value="versi" className="pt-4">
          {halaman === null || versi.length === 0 ? (
            <KeadaanKosong
              judul="Belum ada versi"
              deskripsi="Setiap penyimpanan draf melahirkan satu versi."
            />
          ) : (
            <DaftarVersi halaman={halaman} versi={versi} />
          )}
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}
