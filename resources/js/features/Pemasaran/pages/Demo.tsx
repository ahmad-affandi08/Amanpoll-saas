import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { HUE_UTAMA } from '@/components/grafik/palet';
import { Badge } from '@/components/ui/badge';
import { varianAktif } from '@/features/Pemasaran/status';
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
import { rutePemasaran } from '@/features/Pemasaran/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Demo {
  Id: string;
  Kode: string;
  Nama: string;
  Aktif: boolean;
  Dataset: string;
  ResetIntervalMenit: number;
  ModulTampil: string[];
  FiturDibatasi: string[];
  CtaLabel: string | null;
  CtaUrl: string | null;
  MaksDurasiMenit: number;
  MaksSesiSerentak: number;
  TerakhirResetPada: string | null;
  TenantDemo: { Kode: string; Demo: boolean } | null;
  SesiBerjalan: number;
  SesiSelesai: number;
}

interface Pilihan {
  Dataset: Record<string, string>;
  Modul: string[];
  Fitur: string[];
}

interface Props {
  demo: Demo[];
  peristiwa: Record<string, number>;
  pilihan: Pilihan;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const AKAR = rutePemasaran.demo;

export default function PemasaranDemo({ demo, peristiwa, pilihan, wajib }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Demo Produk" />

      <KepalaHalaman
        judul="Demo Produk"
        deskripsi="Sandbox yang dicoba calon pelanggan. Datasetnya dibangun ulang berkala dan tidak pernah menyentuh tenant sungguhan."
        tanpaBreadcrumb
        aksi={<DialogFormDemo demo={null} pilihan={pilihan} wajib={wajib.demo} />}
        className="mb-5"
      />

      <div className="grid gap-5 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          {demo.length === 0 ? (
            <p className="text-sm text-muted-foreground">Belum ada demo yang disiapkan.</p>
          ) : (
            demo.map((satu) => <KartuDemo key={satu.Id} demo={satu} pilihan={pilihan} wajib={wajib.demo} />)
          )}
        </div>

        <PeristiwaDemo peristiwa={peristiwa} />
      </div>
    </KerangkaPlatform>
  );
}

function KartuDemo({ demo, pilihan, wajib }: { demo: Demo; pilihan: Pilihan; wajib: AturanWajib }) {
  return (
    <Card>
      <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <CardTitle>{demo.Nama}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">{demo.Kode}</p>
        </div>
        <div className="flex shrink-0 items-center gap-2">
          <Badge variant={varianAktif(demo.Aktif)}>{demo.Aktif ? 'Aktif' : 'Dimatikan'}</Badge>
          <DialogFormDemo demo={demo} pilihan={pilihan} wajib={wajib} />
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.post(`${AKAR}/${demo.Id}/reset`, {}, { preserveScroll: true })}
          >
            Reset sekarang
          </Button>
        </div>
      </CardHeader>

      <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
        <Butir label="Dataset" isi={pilihan.Dataset[demo.Dataset] ?? demo.Dataset} />
        <Butir
          label="Tenant sandbox"
          isi={
            demo.TenantDemo === null ? (
              <span className="text-destructive">Belum ditunjuk; reset tidak akan berjalan.</span>
            ) : (
              <span className="font-mono text-xs">{demo.TenantDemo.Kode}</span>
            )
          }
        />
        <Butir label="Interval reset" isi={`${demo.ResetIntervalMenit} menit`} />
        <Butir label="Terakhir direset" isi={demo.TerakhirResetPada ?? 'Belum pernah'} />
        <Butir label="Batas durasi sesi" isi={`${demo.MaksDurasiMenit} menit`} />
        <Butir
          label="Sesi"
          isi={`${demo.SesiBerjalan} berjalan dari maksimal ${demo.MaksSesiSerentak}, ${demo.SesiSelesai} selesai`}
        />
        <Butir
          label="Modul tampil"
          isi={<DaftarBadge nilai={demo.ModulTampil} kosong="Belum ada modul dipilih" />}
        />
        <Butir label="Fitur dibatasi" isi={<DaftarBadge nilai={demo.FiturDibatasi} kosong="Tidak ada" />} />
        {demo.CtaUrl ? (
          <Butir label="CTA" isi={`${demo.CtaLabel ?? 'Tanpa label'} → ${demo.CtaUrl}`} />
        ) : null}
      </CardContent>
    </Card>
  );
}

function DaftarBadge({ nilai, kosong }: { nilai: string[]; kosong: string }) {
  if (nilai.length === 0) {
    return <span className="text-muted-foreground">{kosong}</span>;
  }

  return (
    <span className="flex flex-wrap gap-1">
      {nilai.map((satu) => (
        <Badge key={satu} variant="outline">
          {satu}
        </Badge>
      ))}
    </span>
  );
}

function Butir({ label, isi }: { label: string; isi: React.ReactNode }) {
  return (
    <div>
      <p className="text-xs text-muted-foreground">{label}</p>
      <div className="mt-0.5 text-foreground">{isi}</div>
    </div>
  );
}

/** Magnitudo per jenis peristiwa: satu deret, satu hue, tanpa legenda. */
function PeristiwaDemo({ peristiwa }: { peristiwa: Record<string, number> }) {
  const baris = Object.entries(peristiwa);
  const puncak = Math.max(...baris.map(([, nilai]) => nilai), 1);

  return (
    <Card className="h-fit">
      <CardHeader>
        <CardTitle>Peristiwa Demo</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {baris.map(([jenis, jumlah]) => (
          <div key={jenis}>
            <div className="flex items-baseline justify-between gap-2 text-sm">
              <span className="text-foreground">{jenis}</span>
              <span className="font-mono text-foreground">{jumlah.toLocaleString('id-ID')}</span>
            </div>
            <div className="mt-1 h-2 rounded-sm bg-muted" role="img" aria-label={`${jenis}: ${jumlah}`}>
              <div
                className="h-2 rounded-sm"
                style={{
                  width: `${Math.max((jumlah / puncak) * 100, jumlah > 0 ? 2 : 0)}%`,
                  backgroundColor: HUE_UTAMA,
                }}
              />
            </div>
          </div>
        ))}
        <p className="text-xs text-muted-foreground">
          DemoDimulai, DemoSelesai, dan CtaDiklik juga tercatat di funnel growth; sisanya hanya hidup di dalam
          demo.
        </p>
      </CardContent>
    </Card>
  );
}

function DialogFormDemo({
  demo,
  pilihan,
  wajib,
}: {
  demo: Demo | null;
  pilihan: Pilihan;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const dataset = Object.keys(pilihan.Dataset);
  const form = useForm({
    Kode: demo?.Kode ?? '',
    Nama: demo?.Nama ?? '',
    Aktif: demo?.Aktif ?? false,
    Dataset: demo?.Dataset ?? dataset[0] ?? '',
    ResetIntervalMenit: String(demo?.ResetIntervalMenit ?? 1440),
    ModulTampil: demo?.ModulTampil ?? [],
    FiturDibatasi: demo?.FiturDibatasi ?? [],
    CtaLabel: demo?.CtaLabel ?? '',
    CtaUrl: demo?.CtaUrl ?? '',
    MaksDurasiMenit: String(demo?.MaksDurasiMenit ?? 30),
    MaksSesiSerentak: String(demo?.MaksSesiSerentak ?? 5),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!demo) form.reset();
      },
    };

    if (demo) {
      router.put(`${AKAR}/${demo.Id}`, form.data, opsi);
    } else {
      router.post(AKAR, form.data, opsi);
    }
  };

  const ubahDaftar = (kunci: 'ModulTampil' | 'FiturDibatasi', nilai: string, dipilih: boolean) => {
    form.setData(
      kunci,
      dipilih ? [...form.data[kunci], nilai] : form.data[kunci].filter((satu) => satu !== nilai),
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={demo ? 'outline' : 'default'} size={demo ? 'sm' : 'default'}>
          {demo ? 'Setelan' : 'Tambah Demo'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{demo ? 'Setelan Demo' : 'Tambah Demo'}</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="grid content-start gap-2">
                <Label nama="Nama" htmlFor="Nama">
                  Nama
                </Label>
                <Input
                  id="Nama"
                  value={form.data.Nama}
                  onChange={(e) => form.setData('Nama', e.target.value)}
                  required
                />
              </div>
            </div>

            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.Aktif}
                onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
              />
              Demo aktif dan dapat dimulai pengunjung
            </label>

            <div className="grid content-start gap-2">
              <Label nama="Dataset" htmlFor="Dataset">
                Dataset
              </Label>
              <Select value={form.data.Dataset} onValueChange={(v) => form.setData('Dataset', v)}>
                <SelectTrigger id="Dataset">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {Object.entries(pilihan.Dataset).map(([kode, nama]) => (
                    <SelectItem key={kode} value={kode}>
                      {nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.Dataset ? <p className="text-sm text-destructive">{form.errors.Dataset}</p> : null}
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
              <div className="grid content-start gap-2">
                <Label nama="ResetIntervalMenit" htmlFor="ResetIntervalMenit">
                  Interval reset (menit)
                </Label>
                <Input
                  id="ResetIntervalMenit"
                  type="number"
                  min="15"
                  value={form.data.ResetIntervalMenit}
                  onChange={(e) => form.setData('ResetIntervalMenit', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label nama="MaksDurasiMenit" htmlFor="MaksDurasiMenit">
                  Durasi sesi (menit)
                </Label>
                <Input
                  id="MaksDurasiMenit"
                  type="number"
                  min="5"
                  value={form.data.MaksDurasiMenit}
                  onChange={(e) => form.setData('MaksDurasiMenit', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label nama="MaksSesiSerentak" htmlFor="MaksSesiSerentak">
                  Sesi serentak
                </Label>
                <Input
                  id="MaksSesiSerentak"
                  type="number"
                  min="1"
                  value={form.data.MaksSesiSerentak}
                  onChange={(e) => form.setData('MaksSesiSerentak', e.target.value)}
                />
              </div>
            </div>

            <fieldset className="grid content-start gap-2">
              <legend className="text-sm font-medium">Modul yang ditampilkan</legend>
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                {pilihan.Modul.map((satu) => (
                  <label key={satu} className="flex items-center gap-2 text-sm">
                    <Checkbox
                      checked={form.data.ModulTampil.includes(satu)}
                      onCheckedChange={(nilai) => ubahDaftar('ModulTampil', satu, nilai === true)}
                    />
                    {satu}
                  </label>
                ))}
              </div>
              {form.errors.ModulTampil ? (
                <p className="text-sm text-destructive">{form.errors.ModulTampil}</p>
              ) : null}
            </fieldset>

            <fieldset className="grid content-start gap-2">
              <legend className="text-sm font-medium">Fitur yang dibatasi</legend>
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                {pilihan.Fitur.map((satu) => (
                  <label key={satu} className="flex items-center gap-2 text-sm">
                    <Checkbox
                      checked={form.data.FiturDibatasi.includes(satu)}
                      onCheckedChange={(nilai) => ubahDaftar('FiturDibatasi', satu, nilai === true)}
                    />
                    {satu}
                  </label>
                ))}
              </div>
            </fieldset>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid content-start gap-2">
                <Label nama="CtaLabel" htmlFor="CtaLabel">
                  Label CTA
                </Label>
                <Input
                  id="CtaLabel"
                  value={form.data.CtaLabel}
                  onChange={(e) => form.setData('CtaLabel', e.target.value)}
                />
              </div>
              <div className="grid content-start gap-2">
                <Label nama="CtaUrl" htmlFor="CtaUrl">
                  Tautan CTA
                </Label>
                <Input
                  id="CtaUrl"
                  type="url"
                  value={form.data.CtaUrl}
                  onChange={(e) => form.setData('CtaUrl', e.target.value)}
                />
                {form.errors.CtaUrl ? <p className="text-sm text-destructive">{form.errors.CtaUrl}</p> : null}
              </div>
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
