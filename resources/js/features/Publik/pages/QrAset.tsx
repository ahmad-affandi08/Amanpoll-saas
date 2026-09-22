import { FormEvent, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { KerangkaPublik } from '../components/KerangkaPublik';
import type { LabelQr, PropsPublik, ToolPublik } from '../types';

interface Props extends PropsPublik {
  tool: ToolPublik;
  batas: { MaksKode: number; MaksPanjangKode: number };
}

/** Generator QR aset publik; QR dirender di server (MARKETING.md 10). */
export default function PublikQrAset({ tool, batas, kanonik, urlMasuk, urlDaftar }: Props) {
  const { props } = usePage<{ qr?: LabelQr[]; errors: Record<string, string> }>();
  const label = props.qr ?? [];

  const [teks, setTeks] = useState('AST-0001\nAST-0002\nAST-0003');
  const [perangkap, setPerangkap] = useState('');
  const [mengirim, setMengirim] = useState(false);

  const kode = teks
    .split('\n')
    .map((baris) => baris.trim())
    .filter((baris) => baris !== '');

  const submit = (e: FormEvent) => {
    e.preventDefault();
    setMengirim(true);
    router.post(
      '/tools/qr',
      { Kode: kode, [tool.FieldPerangkap]: perangkap },
      { preserveScroll: true, onFinish: () => setMengirim(false) },
    );
  };

  return (
    <>
      <Head>
        <title>{tool.Judul}</title>
        <meta
          name="description"
          content="Generator QR aset gratis: tempel daftar kode aset, dapatkan label QR siap cetak."
        />
        {kanonik ? <link rel="canonical" href={kanonik} /> : null}
      </Head>

      <KerangkaPublik urlMasuk={urlMasuk} urlDaftar={urlDaftar}>
        <div className="mx-auto w-full max-w-4xl px-4 py-12">
          <h1 className="text-3xl font-semibold tracking-tight">{tool.Judul}</h1>
          <p className="mt-3 text-muted-foreground">
            Tempel kode aset Anda, satu per baris. Paling banyak {batas.MaksKode} kode sekali cetak.
          </p>

          <form onSubmit={submit} className="mt-8 grid gap-4">
            <div className="grid gap-2">
              <Label htmlFor="Kode">Kode aset</Label>
              <Textarea
                id="Kode"
                rows={8}
                className="font-mono text-sm"
                value={teks}
                onChange={(e) => setTeks(e.target.value)}
              />
              <p className="text-xs text-muted-foreground">
                {kode.length} kode terbaca · maksimum {batas.MaksPanjangKode} karakter tiap kode
              </p>
              {props.errors?.Kode ? <p className="text-sm text-destructive">{props.errors.Kode}</p> : null}
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
                value={perangkap}
                onChange={(e) => setPerangkap(e.target.value)}
              />
            </div>

            <div className="flex gap-2">
              <Button type="submit" disabled={mengirim || kode.length === 0}>
                Buat QR
              </Button>
              {label.length > 0 ? (
                <Button type="button" variant="outline" onClick={() => window.print()}>
                  Cetak
                </Button>
              ) : null}
            </div>
          </form>

          {label.length > 0 ? (
            <div className="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
              {label.map((satu) => (
                <figure key={satu.Kode} className="rounded-lg border p-3 text-center">
                  <div
                    className="mx-auto aspect-square w-full [&>svg]:h-full [&>svg]:w-full"
                    // QR dirender sebagai SVG di server; tidak ada masukan pengguna di luar teks kodenya.
                    dangerouslySetInnerHTML={{ __html: satu.Svg }}
                  />
                  <figcaption className="mt-2 font-mono text-xs break-all">{satu.Kode}</figcaption>
                </figure>
              ))}
            </div>
          ) : null}
        </div>
      </KerangkaPublik>
    </>
  );
}
