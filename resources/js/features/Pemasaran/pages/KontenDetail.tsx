import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { KeywordSeo, KontenPemasaran, PilihanKonten, VersiKonten } from '../types';

interface Props {
  konten: KontenPemasaran;
  versi: VersiKonten[];
  keyword: KeywordSeo[];
  pilihan: PilihanKonten;
}

const AKAR = '/admin-platform/pemasaran/konten';

export default function KontenDetailHalaman({ konten, versi, keyword, pilihan }: Props) {
  const form = useForm({
    Slug: konten.Ruas,
    Jenis: konten.Jenis,
    Judul: konten.Judul,
    PenulisNama: konten.PenulisNama ?? '',
    KampanyeId: konten.KampanyeId ?? '',
    NoIndex: konten.NoIndex,
    Ringkasan: konten.Ringkasan ?? '',
    IsiMarkdown: konten.IsiMarkdown ?? '',
    MetaJudul: konten.MetaJudul ?? '',
    MetaDeskripsi: konten.MetaDeskripsi ?? '',
    Kanonik: konten.Kanonik ?? '',
    OgJudul: konten.OgJudul ?? '',
    OgDeskripsi: konten.OgDeskripsi ?? '',
    OgGambar: konten.OgGambar ?? '',
    SkemaTipe: konten.SkemaTipe ?? '',
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(`${AKAR}/${konten.Id}`, { preserveScroll: true });
  };

  return (
    <KerangkaPlatform>
      <Head title={konten.Judul} />

      <PageHeader
        judul={konten.Judul}
        deskripsi={`${konten.Slug} · ${konten.Jenis}`}
        className="mb-6"
        aksi={
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={konten.Status === 'Terbit' ? 'default' : 'secondary'}>{konten.Status}</Badge>
            <Badge variant="outline">{konten.DiSitemap ? 'Di sitemap' : 'Tidak di sitemap'}</Badge>
            <Button
              size="sm"
              onClick={() => router.post(`${AKAR}/${konten.Id}/terbitkan`, {}, { preserveScroll: true })}
              disabled={konten.VersiDrafId === null}
            >
              Terbitkan draf
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() =>
                router.post(`${AKAR}/${konten.Id}/status`, { Status: 'Diarsipkan' }, { preserveScroll: true })
              }
            >
              Arsipkan
            </Button>
          </div>
        }
      />

      <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Draf</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={submit} className="grid gap-4">
              <div className="grid gap-4 sm:grid-cols-2">
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
                  {form.errors.Slug ? <p className="text-sm text-destructive">{form.errors.Slug}</p> : null}
                </div>
              </div>

              <p className="text-xs text-muted-foreground">
                Alamatnya menjadi {pilihan.AwalanJalur[form.data.Jenis] ?? ''}/{form.data.Slug}. Memindahkan
                alamat konten yang pernah terbit membuat redirect 301 dari alamat lamanya.
              </p>

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
                  rows={16}
                  className="font-mono text-sm"
                  value={form.data.IsiMarkdown}
                  onChange={(e) => form.setData('IsiMarkdown', e.target.value)}
                  required
                />
              </div>

              <fieldset className="grid gap-4 rounded-lg border p-4">
                <legend className="px-1 text-sm font-medium">Metadata SEO</legend>

                <div className="grid gap-2">
                  <Label htmlFor="MetaJudul">Meta title</Label>
                  <Input
                    id="MetaJudul"
                    value={form.data.MetaJudul}
                    onChange={(e) => form.setData('MetaJudul', e.target.value)}
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="MetaDeskripsi">Meta description</Label>
                  <Textarea
                    id="MetaDeskripsi"
                    rows={2}
                    value={form.data.MetaDeskripsi}
                    onChange={(e) => form.setData('MetaDeskripsi', e.target.value)}
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
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="grid gap-2">
                    <Label htmlFor="OgJudul">Open Graph title</Label>
                    <Input
                      id="OgJudul"
                      value={form.data.OgJudul}
                      onChange={(e) => form.setData('OgJudul', e.target.value)}
                    />
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="SkemaTipe">Schema type</Label>
                    <Input
                      id="SkemaTipe"
                      value={form.data.SkemaTipe}
                      onChange={(e) => form.setData('SkemaTipe', e.target.value)}
                    />
                  </div>
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="OgDeskripsi">Open Graph description</Label>
                  <Textarea
                    id="OgDeskripsi"
                    rows={2}
                    value={form.data.OgDeskripsi}
                    onChange={(e) => form.setData('OgDeskripsi', e.target.value)}
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="OgGambar">Open Graph image</Label>
                  <Input
                    id="OgGambar"
                    value={form.data.OgGambar}
                    onChange={(e) => form.setData('OgGambar', e.target.value)}
                  />
                  {form.errors.OgGambar ? (
                    <p className="text-sm text-destructive">{form.errors.OgGambar}</p>
                  ) : null}
                </div>

                <label className="flex items-center gap-2 text-sm">
                  <Checkbox
                    checked={form.data.NoIndex}
                    onCheckedChange={(nilai) => form.setData('NoIndex', nilai === true)}
                  />
                  Tandai noindex — konten ini tidak akan pernah masuk peta situs
                </label>
              </fieldset>

              <div className="grid gap-2">
                <Label htmlFor="Catatan">Catatan versi</Label>
                <Input
                  id="Catatan"
                  value={form.data.Catatan}
                  onChange={(e) => form.setData('Catatan', e.target.value)}
                />
              </div>

              <div>
                <Button type="submit" disabled={form.processing}>
                  Simpan sebagai versi baru
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <div className="grid gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Keyword</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-3">
              {konten.Keyword.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum ada keyword yang ditautkan.</p>
              ) : (
                konten.Keyword.map((satu) => (
                  <div key={satu.Id} className="flex items-center justify-between gap-2">
                    <span className="truncate text-sm">
                      {satu.Keyword}
                      {satu.Utama ? ' · utama' : ''}
                    </span>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() =>
                        router.delete(`${AKAR}/${konten.Id}/keyword/${satu.Id}`, { preserveScroll: true })
                      }
                    >
                      Lepas
                    </Button>
                  </div>
                ))
              )}

              <TautkanKeyword kontenId={konten.Id} keyword={keyword} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Riwayat versi</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2">
              {versi.map((satu) => (
                <div key={satu.Id} className="flex items-center justify-between gap-2 text-sm">
                  <span className="truncate">
                    #{satu.Nomor} {satu.Catatan ? `· ${satu.Catatan}` : ''}
                  </span>
                  <span className="flex shrink-0 items-center gap-2">
                    {satu.Terbit ? <Badge>tayang</Badge> : null}
                    {satu.Draf ? <Badge variant="secondary">draf</Badge> : null}
                    <a
                      className="text-xs underline"
                      href={`${AKAR}/${konten.Id}/pratinjau/${satu.Id}`}
                      target="_blank"
                      rel="noreferrer"
                    >
                      Pratinjau
                    </a>
                  </span>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </KerangkaPlatform>
  );
}

function TautkanKeyword({ kontenId, keyword }: { kontenId: string; keyword: KeywordSeo[] }) {
  const form = useForm({ KeywordSeoId: '', Utama: false });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`${AKAR}/${kontenId}/keyword`, { preserveScroll: true, onSuccess: () => form.reset() });
  };

  return (
    <form onSubmit={submit} className="grid gap-2 border-t pt-3">
      <Label htmlFor="KeywordSeoId">Tautkan keyword</Label>
      <Select value={form.data.KeywordSeoId} onValueChange={(v) => form.setData('KeywordSeoId', v)}>
        <SelectTrigger id="KeywordSeoId">
          <SelectValue placeholder="Pilih keyword" />
        </SelectTrigger>
        <SelectContent>
          {keyword.map((satu) => (
            <SelectItem key={satu.Id} value={satu.Id}>
              {satu.Keyword}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      <label className="flex items-center gap-2 text-sm">
        <Checkbox
          checked={form.data.Utama}
          onCheckedChange={(nilai) => form.setData('Utama', nilai === true)}
        />
        Jadikan keyword utama
      </label>
      <Button type="submit" size="sm" variant="outline" disabled={form.data.KeywordSeoId === ''}>
        Tautkan
      </Button>
    </form>
  );
}
