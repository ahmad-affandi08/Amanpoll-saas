import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { ClusterSeo, KeywordSeo, KontenPemasaran, PilihanKonten } from '../types';
import { rutePemasaran } from '@/features/Pemasaran/api';

interface Props {
  konten: KontenPemasaran[];
  keyword: KeywordSeo[];
  cluster: ClusterSeo[];
  pilihan: PilihanKonten;
}

const AKAR = rutePemasaran.konten;

export default function PemasaranKonten({ konten, keyword, cluster, pilihan }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Konten & SEO" />

      <KepalaHalaman
        judul="Konten & SEO"
        deskripsi="Konten berversi seperti halaman pemasaran: yang tayang adalah versi terkunci, dan yang ditandai noindex tidak pernah masuk peta situs."
        tanpaBreadcrumb
        aksi={<DialogKonten pilihan={pilihan} />}
        className="mb-6"
      />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Konten</CardTitle>
          </CardHeader>
          <CardContent>
            {konten.length === 0 ? (
              <p className="text-sm text-muted-foreground">Belum ada konten.</p>
            ) : (
              <div className="grid gap-3">
                {konten.map((satu) => (
                  <BarisKonten key={satu.Id} konten={satu} />
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3">
            <CardTitle className="text-base">Keyword</CardTitle>
            <div className="flex gap-2">
              <DialogCluster />
              <DialogKeyword keyword={null} cluster={cluster} pilihan={pilihan} />
            </div>
          </CardHeader>
          <CardContent>
            {keyword.length === 0 ? (
              <p className="text-sm text-muted-foreground">Belum ada keyword yang digarap.</p>
            ) : (
              <div className="grid gap-2">
                {[...keyword]
                  .sort((a, b) => a.Urutan - b.Urutan || a.Keyword.localeCompare(b.Keyword))
                  .map((satu) => (
                    <div
                      key={satu.Id}
                      className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3"
                    >
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium">{satu.Keyword}</p>
                        <p className="text-xs text-muted-foreground">
                          {satu.IntentLabel}
                          {satu.Cluster ? ` · ${satu.Cluster}` : ''}
                          {satu.TargetUrl ? ` · ${satu.TargetUrl}` : ''}
                        </p>
                      </div>
                      <div className="flex shrink-0 items-center gap-2">
                        <Badge variant={satu.Prioritas === 'Tinggi' ? 'default' : 'secondary'}>
                          {satu.Prioritas}
                        </Badge>
                        <Badge variant="outline">{satu.Status}</Badge>
                        <DialogKeyword keyword={satu} cluster={cluster} pilihan={pilihan} />
                      </div>
                    </div>
                  ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </KerangkaPlatform>
  );
}

function BarisKonten({ konten }: { konten: KontenPemasaran }) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
      <div className="min-w-0">
        <Link href={`${AKAR}/${konten.Id}`} className="truncate text-sm font-medium hover:underline">
          {konten.Judul}
        </Link>
        <p className="font-mono text-xs text-muted-foreground">
          {konten.Slug} · {konten.Jenis}
          {konten.VersiTerbitNomor ? ` · versi tayang ${konten.VersiTerbitNomor}` : ''}
        </p>
      </div>
      <div className="flex shrink-0 flex-wrap items-center gap-2">
        <Badge variant={konten.Status === 'Terbit' ? 'default' : 'secondary'}>{konten.Status}</Badge>
        {konten.NoIndex ? <Badge variant="outline">noindex</Badge> : null}
        <Badge variant="outline">{konten.DiSitemap ? 'Di sitemap' : 'Tidak di sitemap'}</Badge>
      </div>
    </div>
  );
}

function DialogKonten({ pilihan }: { pilihan: PilihanKonten }) {
  const [buka, setBuka] = useState(false);
  const jenisAwal = pilihan.Jenis[0] ?? 'Artikel';

  const form = useForm({
    Slug: '',
    Jenis: jenisAwal,
    Judul: '',
    PenulisNama: '',
    KampanyeId: '',
    NoIndex: false,
    Ringkasan: '',
    IsiMarkdown: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(AKAR, { preserveScroll: true, onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Konten</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Konten</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Jenis">Jenis</Label>
            <Select value={form.data.Jenis} onValueChange={(v) => form.setData('Jenis', v)}>
              <SelectTrigger id="Jenis">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Jenis.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Slug">Slug</Label>
            <Input
              id="Slug"
              value={form.data.Slug}
              onChange={(e) => form.setData('Slug', e.target.value)}
              required
            />
            <p className="text-xs text-muted-foreground">
              Alamatnya menjadi {pilihan.AwalanJalur[form.data.Jenis] ?? ''}/{form.data.Slug || 'slug'}
            </p>
            {form.errors.Slug ? <p className="text-sm text-destructive">{form.errors.Slug}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Judul">Judul</Label>
            <Input
              id="Judul"
              value={form.data.Judul}
              onChange={(e) => form.setData('Judul', e.target.value)}
              required
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Ringkasan">Ringkasan</Label>
            <Textarea
              id="Ringkasan"
              rows={2}
              value={form.data.Ringkasan}
              onChange={(e) => form.setData('Ringkasan', e.target.value)}
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IsiMarkdown">Naskah</Label>
            <Textarea
              id="IsiMarkdown"
              rows={8}
              value={form.data.IsiMarkdown}
              onChange={(e) => form.setData('IsiMarkdown', e.target.value)}
              required
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="PenulisNama">Penulis</Label>
            <Input
              id="PenulisNama"
              value={form.data.PenulisNama}
              onChange={(e) => form.setData('PenulisNama', e.target.value)}
            />
          </div>

          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.NoIndex}
              onCheckedChange={(nilai) => form.setData('NoIndex', nilai === true)}
            />
            Tandai noindex — konten ini tidak akan pernah masuk peta situs
          </label>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan draf
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogKeyword({
  keyword,
  cluster,
  pilihan,
}: {
  keyword: KeywordSeo | null;
  cluster: ClusterSeo[];
  pilihan: PilihanKonten;
}) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Keyword: keyword?.Keyword ?? '',
    ClusterSeoId: keyword?.ClusterSeoId ?? '',
    Intent: keyword?.Intent ?? (Object.keys(pilihan.Intent)[0] ?? 'INFORMATIONAL'),
    TargetUrl: keyword?.TargetUrl ?? '',
    Prioritas: keyword?.Prioritas ?? 'Sedang',
    Status: keyword?.Status ?? 'Ide',
    Catatan: keyword?.Catatan ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };

    if (keyword) {
      router.put(`${AKAR}/seo/keyword/${keyword.Id}`, form.data, opsi);
    } else {
      router.post(`${AKAR}/seo/keyword`, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={keyword ? 'outline' : 'default'} size="sm">
          {keyword ? 'Ubah' : 'Tambah Keyword'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{keyword ? 'Ubah Keyword' : 'Tambah Keyword'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Keyword">Keyword</Label>
            <Input
              id="Keyword"
              value={form.data.Keyword}
              onChange={(e) => form.setData('Keyword', e.target.value)}
              required
            />
            {form.errors.Keyword ? <p className="text-sm text-destructive">{form.errors.Keyword}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Intent">Niat pencarian</Label>
            <Select value={form.data.Intent} onValueChange={(v) => form.setData('Intent', v)}>
              <SelectTrigger id="Intent">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {Object.entries(pilihan.Intent).map(([kunci, label]) => (
                  <SelectItem key={kunci} value={kunci}>
                    {label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Prioritas">Prioritas</Label>
              <Select value={form.data.Prioritas} onValueChange={(v) => form.setData('Prioritas', v)}>
                <SelectTrigger id="Prioritas">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.Prioritas.map((satu) => (
                    <SelectItem key={satu} value={satu}>
                      {satu}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-2">
              <Label htmlFor="StatusKeyword">Status</Label>
              <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v)}>
                <SelectTrigger id="StatusKeyword">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.StatusKeyword.map((satu) => (
                    <SelectItem key={satu} value={satu}>
                      {satu}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="ClusterSeoId">Cluster</Label>
            <Select
              value={form.data.ClusterSeoId === '' ? 'tanpa' : form.data.ClusterSeoId}
              onValueChange={(v) => form.setData('ClusterSeoId', v === 'tanpa' ? '' : v)}
            >
              <SelectTrigger id="ClusterSeoId">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="tanpa">Tanpa cluster</SelectItem>
                {cluster.map((satu) => (
                  <SelectItem key={satu.Id} value={satu.Id}>
                    {satu.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="TargetUrl">Halaman target</Label>
            <Input
              id="TargetUrl"
              value={form.data.TargetUrl}
              onChange={(e) => form.setData('TargetUrl', e.target.value)}
            />
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogCluster() {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '', Keterangan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`${AKAR}/seo/cluster`, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          Tambah Cluster
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Tambah Cluster</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="KodeCluster">Kode</Label>
            <Input
              id="KodeCluster"
              value={form.data.Kode}
              onChange={(e) => form.setData('Kode', e.target.value)}
              required
            />
            {form.errors.Kode ? <p className="text-sm text-destructive">{form.errors.Kode}</p> : null}
          </div>
          <div className="grid gap-2">
            <Label htmlFor="NamaCluster">Nama</Label>
            <Input
              id="NamaCluster"
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
              required
            />
          </div>
          <div className="grid gap-2">
            <Label htmlFor="KeteranganCluster">Keterangan</Label>
            <Textarea
              id="KeteranganCluster"
              rows={2}
              value={form.data.Keterangan}
              onChange={(e) => form.setData('Keterangan', e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
