import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { ActivityFeed } from '@/components/shared/ActivityFeed';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { EntriTimeline, ProspekDetail, TahapRingkas } from '@/features/Pemasaran/types';

interface Props {
  prospek: ProspekDetail;
  timeline: EntriTimeline[];
  tahap: TahapRingkas[];
  jenisAktivitas: string[];
}

export default function Show({ prospek, timeline, tahap, jenisAktivitas }: Props) {
  return (
    <KerangkaPlatform>
      <Head title={prospek.Nama} />

      <PageHeader
        judul={prospek.Nama}
        deskripsi={prospek.Perusahaan ?? 'Tanpa perusahaan'}
        tanpaBreadcrumb
        lencana={
          <div className="flex flex-wrap gap-2">
            {prospek.Tahap ? <Badge variant="secondary">{prospek.Tahap}</Badge> : null}
            {prospek.Qualified ? <Badge>Qualified</Badge> : null}
          </div>
        }
        meta={
          <dl className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
            <div className="flex gap-1">
              <dt>Skor</dt>
              <dd className="font-medium text-foreground tabular-nums">{prospek.Skor}</dd>
            </div>
            <div className="flex gap-1">
              <dt>Sumber</dt>
              <dd className="text-foreground">{prospek.Sumber}</dd>
            </div>
            {prospek.Kampanye ? (
              <div className="flex gap-1">
                <dt>Kampanye</dt>
                <dd className="text-foreground">{prospek.Kampanye}</dd>
              </div>
            ) : null}
          </dl>
        }
        className="mb-6"
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="grid gap-6 lg:col-span-2">
          <KartuTahap prospek={prospek} tahap={tahap} />
          <KartuAktivitasBaru prospek={prospek} jenisAktivitas={jenisAktivitas} />

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Timeline</CardTitle>
            </CardHeader>
            <CardContent>
              <ActivityFeed
                butir={timeline.map((satu, urutan) => ({
                  id: `${satu.Sumber}-${satu.Pada}-${urutan}`,
                  pelaku: satu.Sumber === 'Peristiwa' ? 'Sistem' : 'Tim',
                  ringkasan: satu.Judul,
                  rincian: satu.Isi,
                  waktu: satu.Pada,
                }))}
                pesanKosong="Belum ada aktivitas pada prospek ini."
              />
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6">
          <KartuKontak prospek={prospek} />
          <KartuSkor prospek={prospek} />
        </div>
      </div>
    </KerangkaPlatform>
  );
}

function KartuTahap({ prospek, tahap }: { prospek: ProspekDetail; tahap: TahapRingkas[] }) {
  const [kode, setKode] = useState(prospek.KodeTahap ?? tahap[0]?.Kode ?? '');
  const [alasan, setAlasan] = useState('');

  const pindahkan = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      `/admin-platform/pemasaran/prospek/${prospek.Id}/tahap`,
      { Kode: kode, Alasan: alasan || null },
      { preserveScroll: true, onSuccess: () => setAlasan('') },
    );
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Pindahkan Tahap</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={pindahkan} className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto]">
          <div className="grid gap-2">
            <Label htmlFor="tahap">Tahap</Label>
            <Select value={kode} onValueChange={setKode}>
              <SelectTrigger id="tahap">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {tahap.map((satu) => (
                  <SelectItem key={satu.Kode} value={satu.Kode}>
                    {satu.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="alasan">Alasan</Label>
            <Input id="alasan" value={alasan} onChange={(e) => setAlasan(e.target.value)} />
          </div>
          <div className="flex items-end">
            <Button type="submit">Pindahkan</Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}

function KartuAktivitasBaru({
  prospek,
  jenisAktivitas,
}: {
  prospek: ProspekDetail;
  jenisAktivitas: string[];
}) {
  const form = useForm({ Jenis: jenisAktivitas[0] ?? 'Catatan', Judul: '', Isi: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/admin-platform/pemasaran/prospek/${prospek.Id}/aktivitas`, {
      preserveScroll: true,
      onSuccess: () => form.reset('Judul', 'Isi'),
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Catat Aktivitas</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={submit} className="grid gap-3">
          <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <div className="grid gap-2">
              <Label htmlFor="jenis">Jenis</Label>
              <Select value={form.data.Jenis} onValueChange={(v) => form.setData('Jenis', v)}>
                <SelectTrigger id="jenis">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {jenisAktivitas.map((satu) => (
                    <SelectItem key={satu} value={satu}>
                      {satu}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-2">
              <Label htmlFor="judul">Judul</Label>
              <Input
                id="judul"
                value={form.data.Judul}
                onChange={(e) => form.setData('Judul', e.target.value)}
                required
              />
              {form.errors.Judul ? <p className="text-sm text-destructive">{form.errors.Judul}</p> : null}
            </div>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="isi">Catatan</Label>
            <Textarea
              id="isi"
              rows={3}
              value={form.data.Isi}
              onChange={(e) => form.setData('Isi', e.target.value)}
            />
          </div>
          <div>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}

function KartuKontak({ prospek }: { prospek: ProspekDetail }) {
  const baris: Array<[string, string | null]> = [
    ['Email', prospek.Email],
    ['Telepon', prospek.Telepon],
    ['WhatsApp', prospek.WhatsApp],
    ['Jabatan', prospek.Jabatan],
    ['Industri', prospek.Industri],
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Kontak</CardTitle>
      </CardHeader>
      <CardContent>
        <dl className="grid gap-2 text-sm">
          {baris.map(([label, nilai]) => (
            <div key={label} className="flex justify-between gap-3">
              <dt className="text-muted-foreground">{label}</dt>
              <dd className="text-right">{nilai ?? '—'}</dd>
            </div>
          ))}
        </dl>
      </CardContent>
    </Card>
  );
}

function KartuSkor({ prospek }: { prospek: ProspekDetail }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Rincian Skor</CardTitle>
      </CardHeader>
      <CardContent>
        {prospek.RincianSkor.length === 0 ? (
          <p className="text-sm text-muted-foreground">Skor belum pernah dihitung.</p>
        ) : (
          <dl className="grid gap-2 text-sm">
            {prospek.RincianSkor.map((satu) => (
              <div key={satu.Peristiwa} className="flex justify-between gap-3">
                <dt className="text-muted-foreground">{satu.Peristiwa}</dt>
                <dd className="tabular-nums">{satu.Bobot > 0 ? `+${satu.Bobot}` : satu.Bobot}</dd>
              </div>
            ))}
          </dl>
        )}
      </CardContent>
    </Card>
  );
}
