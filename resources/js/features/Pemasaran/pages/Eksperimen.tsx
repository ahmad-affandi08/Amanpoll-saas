import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { HUE_UTAMA } from '@/components/grafik/palet';
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
import { rutePemasaran } from '@/features/Pemasaran/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Angka {
  Pembilang: number;
  Penyebut: number;
  Rasio: number;
}

interface Varian {
  Id: string;
  Kode: string;
  Nama: string;
  Bobot: number;
  Kontrol: boolean;
  Peserta: number;
  Hasil: Record<string, Angka>;
}

interface Eksperimen {
  Id: string;
  Kode: string;
  Nama: string;
  Target: string;
  MetrikUtama: string;
  LabelMetrik: string;
  Hipotesis: string | null;
  Status: string;
  TujuanStatus: string[];
  MinimumSampel: number;
  Pemenang: string | null;
  AlasanKeputusan: string | null;
  DiputuskanPada: string | null;
  Varian: Varian[];
  Penilaian: { BolehDinyatakan: boolean; Alasan: string | null; Pemenang: string | null };
}

interface Pilihan {
  Target: string[];
  Metrik: Array<{ Kunci: string; Label: string }>;
  MinimumSampelBawaan: number;
}

interface Props {
  eksperimen: Eksperimen[];
  pilihan: Pilihan;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const AKAR = rutePemasaran.eksperimen;

const persen = (nilai: number) => `${(nilai * 100).toFixed(1)}%`;

export default function PemasaranEksperimen({ eksperimen, pilihan, wajib }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Eksperimen A/B" />

      <KepalaHalaman
        judul="Eksperimen A/B"
        deskripsi="Pengunjung yang sama selalu melihat varian yang sama, dan pemenang tidak pernah dinyatakan sebelum sampel minimumnya tercapai."
        tanpaBreadcrumb
        aksi={<DialogFormEksperimen eksperimen={null} pilihan={pilihan} wajib={wajib.eksperimen} />}
        className="mb-6"
      />

      {eksperimen.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada eksperimen.</p>
      ) : (
        <div className="space-y-6">
          {eksperimen.map((satu) => (
            <KartuEksperimen key={satu.Id} eksperimen={satu} pilihan={pilihan} wajib={wajib.eksperimen} />
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}

function KartuEksperimen({
  eksperimen,
  pilihan,
  wajib,
}: {
  eksperimen: Eksperimen;
  pilihan: Pilihan;
  wajib: AturanWajib;
}) {
  const puncak = Math.max(
    ...eksperimen.Varian.map((satu) => satu.Hasil[eksperimen.MetrikUtama]?.Rasio ?? 0),
    0.0001,
  );

  return (
    <Card>
      <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <CardTitle className="text-base">{eksperimen.Nama}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">
            {eksperimen.Kode} · {eksperimen.Target} · metrik {eksperimen.LabelMetrik}
          </p>
        </div>
        <div className="flex shrink-0 flex-wrap items-center gap-2">
          <Badge variant={eksperimen.Status === 'Aktif' ? 'default' : 'secondary'}>{eksperimen.Status}</Badge>
          {eksperimen.Pemenang ? <Badge variant="outline">Pemenang {eksperimen.Pemenang}</Badge> : null}
          <DialogFormEksperimen eksperimen={eksperimen} pilihan={pilihan} wajib={wajib} />
          {eksperimen.TujuanStatus.map((tujuan) => (
            <Button
              key={tujuan}
              variant="ghost"
              size="sm"
              onClick={() =>
                router.post(`${AKAR}/${eksperimen.Id}/status`, { Status: tujuan }, { preserveScroll: true })
              }
            >
              → {tujuan}
            </Button>
          ))}
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.post(`${AKAR}/${eksperimen.Id}/hitung`, {}, { preserveScroll: true })}
          >
            Hitung ulang
          </Button>
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        {eksperimen.Hipotesis ? (
          <p className="text-sm text-muted-foreground">{eksperimen.Hipotesis}</p>
        ) : null}

        <div className="space-y-3">
          {eksperimen.Varian.map((satu) => {
            const angka = satu.Hasil[eksperimen.MetrikUtama];
            const cukup = satu.Peserta >= eksperimen.MinimumSampel;

            return (
              <div key={satu.Id}>
                <div className="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                  <span className="text-foreground">
                    {satu.Kode} · {satu.Nama}
                    {satu.Kontrol ? (
                      <span className="ml-2 text-xs text-muted-foreground">kontrol</span>
                    ) : null}
                  </span>
                  <span className="font-mono text-foreground">
                    {angka ? persen(angka.Rasio) : '—'}
                    <span className="ml-2 text-xs text-muted-foreground">
                      {angka ? `${angka.Pembilang}/${angka.Penyebut}` : 'belum dihitung'}
                    </span>
                  </span>
                </div>
                <div
                  className="mt-1 h-2 rounded-sm bg-muted"
                  role="img"
                  aria-label={`${satu.Kode}: ${angka ? persen(angka.Rasio) : 'belum dihitung'}`}
                >
                  <div
                    className="h-2 rounded-sm"
                    style={{
                      width: `${Math.min(((angka?.Rasio ?? 0) / puncak) * 100, 100)}%`,
                      backgroundColor: HUE_UTAMA,
                    }}
                  />
                </div>
                <p className="mt-0.5 text-xs text-muted-foreground">
                  {satu.Peserta} peserta dari minimum {eksperimen.MinimumSampel}
                  {cukup ? '' : ' — belum cukup'}
                </p>
              </div>
            );
          })}
        </div>

        <div className="rounded-lg border border-dashed p-3 text-sm">
          {eksperimen.Penilaian.BolehDinyatakan ? (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span className="text-foreground">
                Varian {eksperimen.Penilaian.Pemenang} unggul pada sampel yang sudah cukup.
              </span>
              <Button
                size="sm"
                onClick={() => router.post(`${AKAR}/${eksperimen.Id}/pemenang`, {}, { preserveScroll: true })}
              >
                Nyatakan pemenang
              </Button>
            </div>
          ) : (
            <p className="text-muted-foreground">
              {eksperimen.AlasanKeputusan ?? eksperimen.Penilaian.Alasan}
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

function DialogFormEksperimen({
  eksperimen,
  pilihan,
  wajib,
}: {
  eksperimen: Eksperimen | null;
  pilihan: Pilihan;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: eksperimen?.Kode ?? '',
    Nama: eksperimen?.Nama ?? '',
    Target: eksperimen?.Target ?? pilihan.Target[0],
    MetrikUtama: eksperimen?.MetrikUtama ?? pilihan.Metrik[0]?.Kunci,
    Hipotesis: eksperimen?.Hipotesis ?? '',
    MinimumSampel: String(eksperimen?.MinimumSampel ?? pilihan.MinimumSampelBawaan),
    Varian: eksperimen?.Varian.map((satu) => ({
      Kode: satu.Kode,
      Nama: satu.Nama,
      Bobot: String(satu.Bobot),
      Kontrol: satu.Kontrol,
    })) ?? [
      { Kode: 'A', Nama: 'Kontrol', Bobot: '1', Kontrol: true },
      { Kode: 'B', Nama: 'Varian', Bobot: '1', Kontrol: false },
    ],
  });

  const ubahVarian = (
    indeks: number,
    kunci: 'Kode' | 'Nama' | 'Bobot' | 'Kontrol',
    nilai: string | boolean,
  ) =>
    form.setData(
      'Varian',
      form.data.Varian.map((satu, ke) =>
        ke === indeks
          ? { ...satu, [kunci]: nilai, ...(kunci === 'Kontrol' && nilai === true ? {} : {}) }
          : kunci === 'Kontrol' && nilai === true
            ? { ...satu, Kontrol: false }
            : satu,
      ),
    );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const muatan = {
      ...form.data,
      Varian: form.data.Varian.map((satu) => ({ ...satu, Bobot: Number(satu.Bobot) })),
    };
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!eksperimen) form.reset();
      },
    };

    if (eksperimen) {
      router.put(`${AKAR}/${eksperimen.Id}`, muatan, opsi);
    } else {
      router.post(AKAR, muatan, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={eksperimen ? 'outline' : 'default'} size={eksperimen ? 'sm' : 'default'}>
          {eksperimen ? 'Ubah' : 'Tambah Eksperimen'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{eksperimen ? 'Ubah Eksperimen' : 'Tambah Eksperimen'}</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="grid gap-2">
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

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label nama="Target" htmlFor="Target">
                  Target uji
                </Label>
                <Select value={form.data.Target} onValueChange={(v) => form.setData('Target', v)}>
                  <SelectTrigger id="Target">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {pilihan.Target.map((satu) => (
                      <SelectItem key={satu} value={satu}>
                        {satu}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid gap-2">
                <Label nama="MetrikUtama" htmlFor="MetrikUtama">
                  Metrik utama
                </Label>
                <Select value={form.data.MetrikUtama} onValueChange={(v) => form.setData('MetrikUtama', v)}>
                  <SelectTrigger id="MetrikUtama">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {pilihan.Metrik.map((satu) => (
                      <SelectItem key={satu.Kunci} value={satu.Kunci}>
                        {satu.Label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid gap-2">
              <Label nama="Hipotesis" htmlFor="Hipotesis">
                Hipotesis
              </Label>
              <Textarea
                id="Hipotesis"
                rows={3}
                value={form.data.Hipotesis}
                onChange={(e) => form.setData('Hipotesis', e.target.value)}
              />
            </div>

            <div className="grid gap-2">
              <Label nama="MinimumSampel" htmlFor="MinimumSampel">
                Minimum sampel tiap varian
              </Label>
              <Input
                id="MinimumSampel"
                type="number"
                min="1"
                value={form.data.MinimumSampel}
                onChange={(e) => form.setData('MinimumSampel', e.target.value)}
                required
              />
              <p className="text-sm text-muted-foreground">
                Selama satu varian pun belum mencapai angka ini, pemenang tidak akan pernah dinyatakan,
                sebesar apa pun selisihnya.
              </p>
            </div>

            <fieldset className="grid gap-3">
              <legend className="text-sm font-medium">Varian</legend>
              {form.data.Varian.map((satu, indeks) => (
                <div key={indeks} className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[4rem_1fr_5rem]">
                  <div className="grid gap-1.5">
                    <Label htmlFor={`Kode-${indeks}`}>Kode</Label>
                    <Input
                      id={`Kode-${indeks}`}
                      value={satu.Kode}
                      onChange={(e) => ubahVarian(indeks, 'Kode', e.target.value)}
                      required
                    />
                  </div>
                  <div className="grid gap-1.5">
                    <Label htmlFor={`Nama-${indeks}`}>Nama</Label>
                    <Input
                      id={`Nama-${indeks}`}
                      value={satu.Nama}
                      onChange={(e) => ubahVarian(indeks, 'Nama', e.target.value)}
                      required
                    />
                  </div>
                  <div className="grid gap-1.5">
                    <Label htmlFor={`Bobot-${indeks}`}>Bobot</Label>
                    <Input
                      id={`Bobot-${indeks}`}
                      type="number"
                      min="0"
                      value={satu.Bobot}
                      onChange={(e) => ubahVarian(indeks, 'Bobot', e.target.value)}
                      required
                    />
                  </div>
                  <label className="flex items-center gap-2 text-sm sm:col-span-3">
                    <Checkbox
                      checked={satu.Kontrol}
                      onCheckedChange={(nilai) => ubahVarian(indeks, 'Kontrol', nilai === true)}
                    />
                    Varian kontrol
                  </label>
                </div>
              ))}

              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() =>
                  form.setData('Varian', [
                    ...form.data.Varian,
                    {
                      Kode: String.fromCharCode(65 + form.data.Varian.length),
                      Nama: '',
                      Bobot: '1',
                      Kontrol: false,
                    },
                  ])
                }
              >
                Tambah varian
              </Button>
              {form.errors.Varian ? <p className="text-sm text-destructive">{form.errors.Varian}</p> : null}
            </fieldset>

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
