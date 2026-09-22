import { FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { KerangkaPublik } from '../components/KerangkaPublik';
import type { HasilKeandalan, PropsPublik, ToolPublik } from '../types';
import { rutePublik } from '@/features/Publik/api';

interface Props extends PropsPublik {
  tool: ToolPublik;
}

interface Kartu {
  kunci: string;
  label: string;
  satuan: string;
  nilai: number | null;
  alasan: string | null;
}

const angka = (nilai: number) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(nilai);

function kartuDari(hasil: HasilKeandalan): Kartu[] {
  return [
    { kunci: 'TotalJam', label: 'Total Downtime', satuan: 'jam', nilai: hasil.TotalJam, alasan: null },
    { kunci: 'Mttr', label: 'MTTR', satuan: 'jam', nilai: hasil.Mttr, alasan: hasil.AlasanMttr },
    { kunci: 'Mtbf', label: 'MTBF', satuan: 'jam', nilai: hasil.Mtbf, alasan: hasil.AlasanMtbf },
    {
      kunci: 'Ketersediaan',
      label: 'Ketersediaan',
      satuan: '%',
      nilai: hasil.Ketersediaan,
      alasan: hasil.AlasanKetersediaan,
    },
  ];
}

/** Kalkulator keandalan publik; angkanya dihitung di server (MARKETING.md 10). */
export default function PublikKalkulator({ tool, kanonik, urlMasuk, urlDaftar }: Props) {
  const { props } = usePage<{ hasil?: HasilKeandalan }>();
  const hasil = props.hasil;

  const form = useForm({
    JumlahAset: '10',
    HariRentang: '30',
    JumlahKegagalan: '4',
    MenitDowntime: '480',
    [tool.FieldPerangkap]: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePublik.toolsKalkulator, { preserveScroll: true });
  };

  return (
    <>
      <Head>
        <title>{tool.Judul}</title>
        <meta
          name="description"
          content={`${tool.Judul} gratis: hitung MTTR, MTBF, downtime, dan ketersediaan aset dengan rumus yang sama dengan dasbor Amanpoll.`}
        />
        {kanonik ? <link rel="canonical" href={kanonik} /> : null}
      </Head>

      <KerangkaPublik urlMasuk={urlMasuk} urlDaftar={urlDaftar}>
        <div className="mx-auto w-full max-w-3xl px-4 py-12">
          <h1 className="text-3xl font-semibold tracking-tight">{tool.Judul}</h1>
          <p className="mt-3 text-muted-foreground">
            Angka di bawah dihitung dengan rumus yang sama persis dengan KPI keandalan di dalam Amanpoll,
            bukan salinannya.
          </p>

          <form onSubmit={submit} className="mt-8 grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="JumlahAset">Jumlah aset dipantau</Label>
              <Input
                id="JumlahAset"
                type="number"
                min="0"
                value={form.data.JumlahAset}
                onChange={(e) => form.setData('JumlahAset', e.target.value)}
                required
              />
              {form.errors.JumlahAset ? (
                <p className="text-sm text-destructive">{form.errors.JumlahAset}</p>
              ) : null}
            </div>

            <div className="grid gap-2">
              <Label htmlFor="HariRentang">Panjang rentang (hari)</Label>
              <Input
                id="HariRentang"
                type="number"
                min="0"
                value={form.data.HariRentang}
                onChange={(e) => form.setData('HariRentang', e.target.value)}
                required
              />
              {form.errors.HariRentang ? (
                <p className="text-sm text-destructive">{form.errors.HariRentang}</p>
              ) : null}
            </div>

            <div className="grid gap-2">
              <Label htmlFor="JumlahKegagalan">Jumlah kegagalan</Label>
              <Input
                id="JumlahKegagalan"
                type="number"
                min="0"
                value={form.data.JumlahKegagalan}
                onChange={(e) => form.setData('JumlahKegagalan', e.target.value)}
                required
              />
              {form.errors.JumlahKegagalan ? (
                <p className="text-sm text-destructive">{form.errors.JumlahKegagalan}</p>
              ) : null}
            </div>

            <div className="grid gap-2">
              <Label htmlFor="MenitDowntime">Total downtime (menit)</Label>
              <Input
                id="MenitDowntime"
                type="number"
                min="0"
                value={form.data.MenitDowntime}
                onChange={(e) => form.setData('MenitDowntime', e.target.value)}
                required
              />
              {form.errors.MenitDowntime ? (
                <p className="text-sm text-destructive">{form.errors.MenitDowntime}</p>
              ) : null}
            </div>

            {/* Perangkap honeypot yang sama dengan formulir pemasaran. */}
            <div className="absolute left-[-9999px]" aria-hidden="true">
              <label htmlFor={tool.FieldPerangkap}>Situs perusahaan</label>
              <input
                id={tool.FieldPerangkap}
                name={tool.FieldPerangkap}
                type="text"
                tabIndex={-1}
                autoComplete="off"
                value={String(form.data[tool.FieldPerangkap] ?? '')}
                onChange={(e) => form.setData(tool.FieldPerangkap, e.target.value)}
              />
            </div>

            <div className="sm:col-span-2">
              <Button type="submit" disabled={form.processing}>
                Hitung
              </Button>
            </div>
          </form>

          {hasil ? (
            <div className="mt-10">
              {hasil.Peringatan ? (
                <p className="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                  {hasil.Peringatan}
                </p>
              ) : null}

              <div className="grid gap-4 sm:grid-cols-2">
                {kartuDari(hasil).map((kartu) => (
                  <div
                    key={kartu.kunci}
                    className={`rounded-lg border p-4 ${kartu.kunci === tool.MetrikSorotan ? 'border-foreground' : ''}`}
                  >
                    <p className="text-sm text-muted-foreground">{kartu.label}</p>
                    {kartu.nilai === null ? (
                      <p className="mt-2 text-sm text-muted-foreground">{kartu.alasan}</p>
                    ) : (
                      <p className="mt-1 text-2xl font-semibold tabular-nums">
                        {angka(kartu.nilai)} <span className="text-base font-normal">{kartu.satuan}</span>
                      </p>
                    )}
                  </div>
                ))}
              </div>
            </div>
          ) : null}
        </div>
      </KerangkaPublik>
    </>
  );
}
