import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { rutePemasaran } from '@/features/Pemasaran/api';

interface Distribusi {
  Id: string;
  Channel: string;
  Caption: string;
  MediaUrl: string | null;
  Cta: string | null;
  TautanTujuan: string | null;
  UtmSource: string | null;
  UtmMedium: string | null;
  UtmTerm: string | null;
  UtmContent: string | null;
  Status: string;
  TujuanStatus: string[];
  TautanBerUtm: string | null;
  UrlTerbit: string | null;
  Galat: string | null;
  Percobaan: number;
  TerbitPada: string | null;
  JadwalPada: string | null;
}

interface Konten {
  Id: string;
  Kode: string;
  Judul: string;
  Ringkasan: string | null;
  MediaUrl: string | null;
  HalamanId: string | null;
  KampanyeId: string | null;
  KampanyeKode: string | null;
  HalamanSlug: string | null;
  Distribusi: Distribusi[];
}

interface Pilihan {
  Channel: string[];
  ChannelWajibMedia: string[];
  Kampanye: Record<string, string>;
  Halaman: Record<string, string>;
}

interface Props {
  konten: Konten[];
  pilihan: Pilihan;
}

const AKAR = rutePemasaran.sosial;

const WARNA_STATUS: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
  Draf: 'outline',
  Review: 'secondary',
  Terjadwal: 'secondary',
  Diproses: 'secondary',
  Terbit: 'default',
  Gagal: 'destructive',
};

export default function PemasaranSosial({ konten, pilihan }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Konten Sosial" />

      <KepalaHalaman
        judul="Konten Sosial"
        deskripsi="Satu konten utama, banyak distribusi. Tiap distribusi punya caption, media, CTA, dan UTM sendiri."
        tanpaBreadcrumb
        aksi={<DialogFormKonten konten={null} pilihan={pilihan} />}
        className="mb-6"
      />

      {konten.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada konten sosial.</p>
      ) : (
        <div className="space-y-6">
          {konten.map((satu) => (
            <KartuKonten key={satu.Id} konten={satu} pilihan={pilihan} />
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}

function KartuKonten({ konten, pilihan }: { konten: Konten; pilihan: Pilihan }) {
  return (
    <Card>
      <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <CardTitle className="text-base">{konten.Judul}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">
            {konten.Kode}
            {konten.KampanyeKode ? ` · kampanye ${konten.KampanyeKode}` : ' · tanpa kampanye'}
            {konten.HalamanSlug ? ` · ${konten.HalamanSlug}` : ''}
          </p>
        </div>
        <div className="flex shrink-0 gap-2">
          <DialogFormKonten konten={konten} pilihan={pilihan} />
          <DialogFormDistribusi konten={konten} distribusi={null} pilihan={pilihan} />
        </div>
      </CardHeader>

      <CardContent className="space-y-3">
        {konten.Ringkasan ? (
          <p className="text-sm text-muted-foreground">{konten.Ringkasan}</p>
        ) : null}

        {konten.Distribusi.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Belum ada distribusi. Tambahkan channel agar konten ini punya tempat terbit.
          </p>
        ) : (
          konten.Distribusi.map((satu) => (
            <BarisDistribusi key={satu.Id} konten={konten} distribusi={satu} pilihan={pilihan} />
          ))
        )}
      </CardContent>
    </Card>
  );
}

function BarisDistribusi({
  konten,
  distribusi,
  pilihan,
}: {
  konten: Konten;
  distribusi: Distribusi;
  pilihan: Pilihan;
}) {
  const akar = `${AKAR}/${konten.Id}/distribusi/${distribusi.Id}`;

  return (
    <div className="rounded-lg border p-3">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="flex items-center gap-2">
          <Badge variant="outline">{distribusi.Channel}</Badge>
          <Badge variant={WARNA_STATUS[distribusi.Status] ?? 'secondary'}>{distribusi.Status}</Badge>
          {distribusi.JadwalPada ? (
            <span className="text-xs text-muted-foreground">Terjadwal {distribusi.JadwalPada}</span>
          ) : null}
        </div>

        <div className="flex flex-wrap gap-1">
          <DialogFormDistribusi konten={konten} distribusi={distribusi} pilihan={pilihan} />
          {distribusi.TujuanStatus.map((tujuan) => (
            <Button
              key={tujuan}
              variant="ghost"
              size="sm"
              onClick={() =>
                router.post(`${akar}/status`, { Status: tujuan }, { preserveScroll: true })
              }
            >
              → {tujuan}
            </Button>
          ))}
          <DialogJadwal akar={akar} />
          {distribusi.JadwalPada ? (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => router.delete(`${akar}/jadwal`, { preserveScroll: true })}
            >
              Batalkan
            </Button>
          ) : null}
          {distribusi.Status !== 'Terbit' ? (
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.post(`${akar}/terbitkan`, {}, { preserveScroll: true })}
            >
              Terbitkan sekarang
            </Button>
          ) : null}
        </div>
      </div>

      <p className="mt-2 whitespace-pre-wrap text-sm text-foreground">{distribusi.Caption}</p>

      {distribusi.TautanBerUtm ? (
        <p className="mt-2 break-all font-mono text-xs text-muted-foreground">
          {distribusi.TautanBerUtm}
        </p>
      ) : (
        <p className="mt-2 text-xs text-muted-foreground">
          Belum ada tautan tujuan, jadi trafiknya tidak akan terbaca di attribution.
        </p>
      )}

      {distribusi.Galat ? <p className="mt-1 text-xs text-destructive">{distribusi.Galat}</p> : null}
      {distribusi.UrlTerbit ? (
        <p className="mt-1 break-all text-xs text-muted-foreground">Terbit di {distribusi.UrlTerbit}</p>
      ) : null}
    </div>
  );
}

function DialogFormKonten({ konten, pilihan }: { konten: Konten | null; pilihan: Pilihan }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: konten?.Kode ?? '',
    Judul: konten?.Judul ?? '',
    Ringkasan: konten?.Ringkasan ?? '',
    MediaUrl: konten?.MediaUrl ?? '',
    HalamanId: konten?.HalamanId ?? '',
    KampanyeId: konten?.KampanyeId ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!konten) form.reset();
      },
    };

    if (konten) {
      router.put(`${AKAR}/${konten.Id}`, form.data, opsi);
    } else {
      router.post(AKAR, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={konten ? 'outline' : 'default'} size={konten ? 'sm' : 'default'}>
          {konten ? 'Ubah konten' : 'Tambah Konten'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{konten ? 'Ubah Konten' : 'Tambah Konten'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Kode">Kode</Label>
            <Input
              id="Kode"
              value={form.data.Kode}
              onChange={(e) => form.setData('Kode', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Dipakai sebagai <code className="font-mono">utm_content</code> bawaan tiap distribusinya.
            </p>
            {form.errors.Kode ? <p className="text-sm text-destructive">{form.errors.Kode}</p> : null}
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
              rows={3}
              value={form.data.Ringkasan}
              onChange={(e) => form.setData('Ringkasan', e.target.value)}
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <PilihRelasi
              id="KampanyeId"
              label="Kampanye"
              nilai={form.data.KampanyeId}
              opsi={pilihan.Kampanye}
              ubah={(v) => form.setData('KampanyeId', v)}
            />
            <PilihRelasi
              id="HalamanId"
              label="Halaman yang dipromosikan"
              nilai={form.data.HalamanId}
              opsi={pilihan.Halaman}
              ubah={(v) => form.setData('HalamanId', v)}
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

function PilihRelasi({
  id,
  label,
  nilai,
  opsi,
  ubah,
}: {
  id: string;
  label: string;
  nilai: string;
  opsi: Record<string, string>;
  ubah: (nilai: string) => void;
}) {
  return (
    <div className="grid gap-2">
      <Label htmlFor={id}>{label}</Label>
      <Select value={nilai === '' ? 'kosong' : nilai} onValueChange={(v) => ubah(v === 'kosong' ? '' : v)}>
        <SelectTrigger id={id}>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="kosong">Belum ditentukan</SelectItem>
          {Object.entries(opsi).map(([kunci, teks]) => (
            <SelectItem key={kunci} value={kunci}>
              {teks}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
}

function DialogFormDistribusi({
  konten,
  distribusi,
  pilihan,
}: {
  konten: Konten;
  distribusi: Distribusi | null;
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Channel: distribusi?.Channel ?? pilihan.Channel[0],
    Caption: distribusi?.Caption ?? '',
    MediaUrl: distribusi?.MediaUrl ?? '',
    Cta: distribusi?.Cta ?? '',
    TautanTujuan: distribusi?.TautanTujuan ?? '',
    UtmSource: distribusi?.UtmSource ?? '',
    UtmMedium: distribusi?.UtmMedium ?? '',
    UtmTerm: distribusi?.UtmTerm ?? '',
    UtmContent: distribusi?.UtmContent ?? '',
  });

  const wajibMedia = pilihan.ChannelWajibMedia.includes(form.data.Channel);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!distribusi) form.reset();
      },
    };

    if (distribusi) {
      router.put(`${AKAR}/${konten.Id}/distribusi/${distribusi.Id}`, form.data, opsi);
    } else {
      router.post(`${AKAR}/${konten.Id}/distribusi`, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          {distribusi ? 'Ubah' : 'Tambah distribusi'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{distribusi ? 'Ubah Distribusi' : 'Tambah Distribusi'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Channel">Channel</Label>
            <Select value={form.data.Channel} onValueChange={(v) => form.setData('Channel', v)}>
              <SelectTrigger id="Channel">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Channel.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.Channel ? (
              <p className="text-sm text-destructive">{form.errors.Channel}</p>
            ) : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Caption">Caption</Label>
            <Textarea
              id="Caption"
              rows={5}
              value={form.data.Caption}
              onChange={(e) => form.setData('Caption', e.target.value)}
              required
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="MediaUrl">Media</Label>
            <Input
              id="MediaUrl"
              type="url"
              value={form.data.MediaUrl}
              onChange={(e) => form.setData('MediaUrl', e.target.value)}
              required={wajibMedia}
            />
            {wajibMedia ? (
              <p className="text-sm text-muted-foreground">
                Channel ini menuntut media; caption saja akan ditolak penyedianya.
              </p>
            ) : null}
            {form.errors.MediaUrl ? (
              <p className="text-sm text-destructive">{form.errors.MediaUrl}</p>
            ) : null}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Cta">CTA</Label>
              <Input
                id="Cta"
                value={form.data.Cta}
                onChange={(e) => form.setData('Cta', e.target.value)}
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="TautanTujuan">Tautan tujuan</Label>
              <Input
                id="TautanTujuan"
                type="url"
                value={form.data.TautanTujuan}
                onChange={(e) => form.setData('TautanTujuan', e.target.value)}
              />
            </div>
          </div>

          <fieldset className="grid gap-2">
            <legend className="text-sm font-medium">UTM distribusi</legend>
            <p className="text-sm text-muted-foreground">
              Dikosongkan berarti memakai bawaan: channel sebagai <code className="font-mono">utm_source</code>,{' '}
              <code className="font-mono">social</code> sebagai medium, kode kampanye sebagai campaign.
            </p>
            <div className="grid gap-3 sm:grid-cols-2">
              {(
                [
                  ['UtmSource', 'utm_source'],
                  ['UtmMedium', 'utm_medium'],
                  ['UtmTerm', 'utm_term'],
                  ['UtmContent', 'utm_content'],
                ] as const
              ).map(([kunci, label]) => (
                <div key={kunci} className="grid gap-2">
                  <Label htmlFor={kunci}>{label}</Label>
                  <Input
                    id={kunci}
                    value={form.data[kunci]}
                    onChange={(e) => form.setData(kunci, e.target.value)}
                  />
                </div>
              ))}
            </div>
          </fieldset>

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

function DialogJadwal({ akar }: { akar: string }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ JadwalPada: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`${akar}/jadwal`, form.data, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm">
          Jadwalkan
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Jadwalkan Penerbitan</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="JadwalPada">Terbit pada</Label>
            <Input
              id="JadwalPada"
              type="datetime-local"
              value={form.data.JadwalPada}
              onChange={(e) => form.setData('JadwalPada', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Menjadwalkan ulang membatalkan rencana sebelumnya, sehingga tidak ada posting ganda.
            </p>
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Jadwalkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
