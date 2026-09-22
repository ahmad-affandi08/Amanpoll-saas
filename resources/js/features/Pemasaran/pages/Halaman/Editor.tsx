import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { EmptyState } from '@/components/shared/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { BlokDisunting, HalamanDetail, PilihanHalaman, VersiHalaman } from '@/features/Pemasaran/types';

interface Props {
  halaman: HalamanDetail | null;
  versi: VersiHalaman[];
  pilihan: PilihanHalaman;
}

const AKAR = '/admin-platform/pemasaran/halaman';

let penghitungKunci = 0;

function kunciBaru(): string {
  penghitungKunci += 1;

  return `blok-${penghitungKunci}`;
}

export default function Editor({ halaman, versi, pilihan }: Props) {
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
      form.put(`${AKAR}/${halaman.Id}`, { preserveScroll: true });
    } else {
      form.post(AKAR);
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

      <PageHeader
        judul={halaman ? halaman.Judul : 'Halaman Baru'}
        deskripsi={halaman ? halaman.Slug : 'Setiap penyimpanan melahirkan versi baru.'}
        tanpaBreadcrumb
        lencana={halaman ? <Badge variant="secondary">{halaman.Status}</Badge> : undefined}
        aksi={
          <div className="flex flex-wrap gap-2">
            <Button variant="ghost" asChild>
              <Link href={AKAR}>Kembali</Link>
            </Button>
            <Button onClick={simpan} disabled={form.processing}>
              Simpan Draf
            </Button>
          </div>
        }
        className="mb-6"
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
              <CardTitle className="text-base">Identitas</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label htmlFor="Judul">Judul</Label>
                <Input
                  id="Judul"
                  value={form.data.Judul}
                  onChange={(e) => form.setData('Judul', e.target.value)}
                />
                {form.errors.Judul ? <p className="text-sm text-destructive">{form.errors.Judul}</p> : null}
              </div>

              <div className="grid gap-2">
                <Label htmlFor="Slug">Slug</Label>
                <Input
                  id="Slug"
                  value={form.data.Slug}
                  onChange={(e) => form.setData('Slug', e.target.value)}
                  placeholder="/industri/manufaktur"
                />
                {form.errors.Slug ? <p className="text-sm text-destructive">{form.errors.Slug}</p> : null}
              </div>

              <div className="grid gap-2">
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

              <div className="grid gap-2">
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
            <h2 className="text-lg font-semibold tracking-tight">Blok</h2>
            <Button variant="outline" size="sm" onClick={tambahBlok}>
              Tambah Blok
            </Button>
          </div>

          {blok.length === 0 ? (
            <EmptyState
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
              <CardTitle className="text-base">Metadata</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label htmlFor="MetaJudul">Meta title</Label>
                <Input
                  id="MetaJudul"
                  value={form.data.MetaJudul}
                  onChange={(e) => form.setData('MetaJudul', e.target.value)}
                />
              </div>
              <div className="grid gap-2">
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
              <div className="grid gap-2">
                <Label htmlFor="OgJudul">Open Graph title</Label>
                <Input
                  id="OgJudul"
                  value={form.data.OgJudul}
                  onChange={(e) => form.setData('OgJudul', e.target.value)}
                />
              </div>
              <div className="grid gap-2">
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
              <div className="grid gap-2">
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
            <EmptyState
              judul="Simpan dulu"
              deskripsi="Penerbitan tersedia setelah halaman punya versi pertamanya."
            />
          ) : (
            <PanelPenerbitan halaman={halaman} pilihan={pilihan} />
          )}
        </TabsContent>

        <TabsContent value="versi" className="pt-4">
          {halaman === null || versi.length === 0 ? (
            <EmptyState judul="Belum ada versi" deskripsi="Setiap penyimpanan draf melahirkan satu versi." />
          ) : (
            <DaftarVersi halaman={halaman} versi={versi} />
          )}
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function KartuBlok({
  blok,
  urutan,
  total,
  pilihan,
  ubah,
  hapus,
  pindah,
}: {
  blok: BlokDisunting;
  urutan: number;
  total: number;
  pilihan: PilihanHalaman;
  ubah: (ubahan: Partial<BlokDisunting>) => void;
  hapus: () => void;
  pindah: (arah: -1 | 1) => void;
}) {
  /* Isi blok disunting sebagai JSON. */
  const [naskah, setNaskah] = useState(() => JSON.stringify(blok.Isi ?? {}, null, 2));
  const [galat, setGalat] = useState<string | null>(null);

  const ubahNaskah = (nilai: string) => {
    setNaskah(nilai);

    try {
      const terurai: unknown = JSON.parse(nilai === '' ? '{}' : nilai);

      if (typeof terurai !== 'object' || terurai === null || Array.isArray(terurai)) {
        setGalat('Isi blok harus berupa objek JSON.');

        return;
      }

      setGalat(null);
      ubah({ Isi: terurai as BlokDisunting['Isi'] });
    } catch {
      setGalat('JSON belum sah, perubahan terakhir belum disimpan ke draf.');
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
        <CardTitle className="text-base">
          {urutan + 1}. {blok.Jenis}
        </CardTitle>
        <div className="flex gap-1">
          <Button variant="ghost" size="sm" disabled={urutan === 0} onClick={() => pindah(-1)}>
            Naik
          </Button>
          <Button variant="ghost" size="sm" disabled={urutan === total - 1} onClick={() => pindah(1)}>
            Turun
          </Button>
          <Button variant="ghost" size="sm" onClick={hapus}>
            Hapus
          </Button>
        </div>
      </CardHeader>
      <CardContent className="grid gap-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="grid gap-2">
            <Label>Jenis</Label>
            <Select value={blok.Jenis} onValueChange={(v) => ubah({ Jenis: v })}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Blok.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {blok.Jenis === 'Formulir' ? (
            <div className="grid gap-2">
              <Label>Formulir</Label>
              <Select
                value={blok.FormulirKode ?? ''}
                onValueChange={(v) => ubah({ FormulirKode: v })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Pilih formulir" />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.Formulir.map((satu) => (
                    <SelectItem key={satu.Kode} value={satu.Kode}>
                      {satu.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          ) : null}
        </div>

        <div className="grid gap-2">
          <Label>Isi (JSON)</Label>
          <Textarea
            rows={8}
            className="font-mono text-xs"
            value={naskah}
            onChange={(e) => ubahNaskah(e.target.value)}
          />
          {galat ? <p className="text-sm text-destructive">{galat}</p> : null}
        </div>
      </CardContent>
    </Card>
  );
}

function PanelPenerbitan({ halaman, pilihan }: { halaman: HalamanDetail; pilihan: PilihanHalaman }) {
  const [status, setStatus] = useState(halaman.Status);
  const [terbitPada, setTerbitPada] = useState(halaman.TerbitPada?.slice(0, 16) ?? '');
  const [tarikPada, setTarikPada] = useState(halaman.TarikPada?.slice(0, 16) ?? '');

  const akar = `${AKAR}/${halaman.Id}`;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Penerbitan</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-6">
        <div className="flex flex-wrap items-center gap-3">
          <Button onClick={() => router.post(`${akar}/terbitkan`, {}, { preserveScroll: true })}>
            Terbitkan Draf Sekarang
          </Button>
          {halaman.VersiDrafId ? (
            <Button variant="outline" asChild>
              <a href={`${akar}/pratinjau/${halaman.VersiDrafId}`} target="_blank" rel="noreferrer">
                Pratinjau Draf
              </a>
            </Button>
          ) : null}
        </div>

        <Separator />

        <div className="grid gap-4 sm:grid-cols-3">
          <div className="grid gap-2">
            <Label>Status</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Status.filter((satu) => satu !== 'Terbit').map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="TerbitPada">Terbit pada</Label>
            <Input
              id="TerbitPada"
              type="datetime-local"
              value={terbitPada}
              onChange={(e) => setTerbitPada(e.target.value)}
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="TarikPada">Tarik pada</Label>
            <Input
              id="TarikPada"
              type="datetime-local"
              value={tarikPada}
              onChange={(e) => setTarikPada(e.target.value)}
            />
          </div>
        </div>

        <div>
          <Button
            variant="outline"
            onClick={() =>
              router.post(
                `${akar}/status`,
                {
                  Status: status,
                  TerbitPada: terbitPada === '' ? null : terbitPada,
                  TarikPada: tarikPada === '' ? null : tarikPada,
                },
                { preserveScroll: true },
              )
            }
          >
            Simpan Status
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function DaftarVersi({ halaman, versi }: { halaman: HalamanDetail; versi: VersiHalaman[] }) {
  return (
    <div className="grid gap-3">
      {versi.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-4 pt-6">
            <div className="grid gap-1">
              <div className="flex items-center gap-2">
                <span className="font-medium">Versi {satu.Nomor}</span>
                {satu.Terbit ? <Badge>Terbit</Badge> : null}
                {satu.Draf ? <Badge variant="secondary">Draf</Badge> : null}
              </div>
              <span className="text-sm text-muted-foreground">
                {satu.Judul}
                {satu.Catatan ? ` · ${satu.Catatan}` : ''}
              </span>
              <span className="font-mono text-xs text-muted-foreground">
                {new Date(satu.DibuatPada).toLocaleString('id-ID')}
              </span>
            </div>

            <div className="flex gap-2">
              <Button variant="ghost" size="sm" asChild>
                <a
                  href={`${AKAR}/${halaman.Id}/pratinjau/${satu.Id}`}
                  target="_blank"
                  rel="noreferrer"
                >
                  Pratinjau
                </a>
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={satu.Terbit}
                onClick={() =>
                  router.post(`${AKAR}/${halaman.Id}/kembalikan/${satu.Id}`, {}, { preserveScroll: true })
                }
              >
                Kembalikan
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
