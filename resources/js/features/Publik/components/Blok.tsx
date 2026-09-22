import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { FormulirPemasaran } from './FormulirPemasaran';
import { daftarObjek, daftarTeks, teks, teksOpsional, urlAman } from './isi';
import type { BlokHalaman } from '../types';

/**
 * Perender satu blok halaman pemasaran (MARKETING.md 8).
 *
 * Jenis yang tidak dikenal tidak merender apa pun, dan itu disengaja: daftar
 * jenis di server tertutup, jadi blok asing hanya muncul saat versi frontend
 * tertinggal di belakang — dan halaman yang kehilangan satu bagian jauh lebih
 * baik daripada halaman yang gagal dirender seluruhnya.
 */
export function Blok({ blok }: { blok: BlokHalaman }) {
  switch (blok.Jenis) {
    case 'Hero':
      return <Hero isi={blok.Isi} />;
    case 'TrustLogo':
      return <TrustLogo isi={blok.Isi} />;
    case 'Masalah':
    case 'Manfaat':
    case 'Fitur':
      return <DaftarButir isi={blok.Isi} />;
    case 'Tangkapan':
      return <Tangkapan isi={blok.Isi} />;
    case 'Video':
      return <Video isi={blok.Isi} />;
    case 'Metrik':
      return <Metrik isi={blok.Isi} />;
    case 'Testimoni':
      return <Testimoni isi={blok.Isi} />;
    case 'StudiKasus':
    case 'DaftarArtikel':
      return <DaftarTautan isi={blok.Isi} />;
    case 'Perbandingan':
      return <Perbandingan isi={blok.Isi} />;
    case 'Faq':
      return <Faq isi={blok.Isi} />;
    case 'Harga':
      return <Harga isi={blok.Isi} />;
    case 'Cta':
      return <Cta isi={blok.Isi} />;
    case 'Formulir':
      return <BlokFormulir blok={blok} />;
    case 'ToolEmbed':
      return <ToolEmbed isi={blok.Isi} />;
    case 'Footer':
      return <FooterBlok isi={blok.Isi} />;
    default:
      return null;
  }
}

type Isi = Record<string, unknown>;

function Bagian({ children, rapat = false }: { children: React.ReactNode; rapat?: boolean }) {
  return (
    <section className={rapat ? 'px-4 py-8' : 'px-4 py-12 sm:py-16'}>
      <div className="mx-auto w-full max-w-6xl">{children}</div>
    </section>
  );
}

function JudulBagian({ isi }: { isi: Isi }) {
  const judul = teksOpsional(isi, 'judul');
  const subjudul = teksOpsional(isi, 'subjudul');

  if (judul === null && subjudul === null) {
    return null;
  }

  return (
    <div className="mb-8 grid gap-2">
      {judul ? <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">{judul}</h2> : null}
      {subjudul ? <p className="max-w-2xl text-muted-foreground">{subjudul}</p> : null}
    </div>
  );
}

function TombolCta({ isi }: { isi: Isi }) {
  const url = urlAman(teksOpsional(isi, 'ctaUrl'));
  const label = teksOpsional(isi, 'ctaTeks');

  if (url === null || label === null) {
    return null;
  }

  return (
    <Button size="lg" asChild>
      <a href={url}>{label}</a>
    </Button>
  );
}

function Hero({ isi }: { isi: Isi }) {
  const gambar = urlAman(teksOpsional(isi, 'gambar'));

  return (
    <Bagian>
      <div className="grid items-center gap-8 lg:grid-cols-2">
        <div className="grid gap-5">
          {teksOpsional(isi, 'label') ? <Badge variant="secondary">{teks(isi, 'label')}</Badge> : null}
          <h1 className="text-3xl font-semibold tracking-tight sm:text-5xl">{teks(isi, 'judul')}</h1>
          {teksOpsional(isi, 'subjudul') ? (
            <p className="max-w-2xl text-base text-muted-foreground sm:text-lg">{teks(isi, 'subjudul')}</p>
          ) : null}
          <div className="flex flex-wrap gap-3">
            <TombolCta isi={isi} />
          </div>
        </div>
        {gambar ? (
          <img src={gambar} alt={teks(isi, 'judul')} className="w-full rounded-lg border" loading="lazy" />
        ) : null}
      </div>
    </Bagian>
  );
}

function TrustLogo({ isi }: { isi: Isi }) {
  const logo = daftarObjek(isi, 'logo');

  if (logo.length === 0) {
    return null;
  }

  return (
    <Bagian rapat>
      {teksOpsional(isi, 'judul') ? (
        <p className="mb-6 text-center text-sm text-muted-foreground">{teks(isi, 'judul')}</p>
      ) : null}
      <div className="flex flex-wrap items-center justify-center gap-8">
        {logo.map((satu, urutan) => {
          const gambar = urlAman(teksOpsional(satu, 'gambar'));
          const nama = teks(satu, 'nama');

          return gambar ? (
            <img key={urutan} src={gambar} alt={nama} className="h-8 w-auto opacity-70" loading="lazy" />
          ) : (
            <span key={urutan} className="text-sm font-medium text-muted-foreground">
              {nama}
            </span>
          );
        })}
      </div>
    </Bagian>
  );
}

function DaftarButir({ isi }: { isi: Isi }) {
  const butir = daftarObjek(isi, 'butir');

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {butir.map((satu, urutan) => (
          <Card key={urutan}>
            <CardHeader>
              <CardTitle className="text-base">{teks(satu, 'judul')}</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">{teks(satu, 'deskripsi')}</CardContent>
          </Card>
        ))}
      </div>
    </Bagian>
  );
}

function Tangkapan({ isi }: { isi: Isi }) {
  const gambar = urlAman(teksOpsional(isi, 'gambar'));

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      {gambar ? (
        <figure className="grid gap-3">
          <img src={gambar} alt={teks(isi, 'judul')} className="w-full rounded-lg border" loading="lazy" />
          {teksOpsional(isi, 'keterangan') ? (
            <figcaption className="text-sm text-muted-foreground">{teks(isi, 'keterangan')}</figcaption>
          ) : null}
        </figure>
      ) : null}
    </Bagian>
  );
}

function Video({ isi }: { isi: Isi }) {
  const url = urlAman(teksOpsional(isi, 'url'));

  if (url === null) {
    return null;
  }

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="aspect-video w-full overflow-hidden rounded-lg border">
        <iframe
          src={url}
          title={teks(isi, 'judul', 'Video')}
          className="h-full w-full"
          loading="lazy"
          allowFullScreen
        />
      </div>
    </Bagian>
  );
}

function Metrik({ isi }: { isi: Isi }) {
  const butir = daftarObjek(isi, 'butir');

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {butir.map((satu, urutan) => (
          <div key={urutan} className="grid gap-1">
            <span className="font-mono text-3xl font-semibold tracking-tight">{teks(satu, 'angka')}</span>
            <span className="text-sm text-muted-foreground">{teks(satu, 'label')}</span>
          </div>
        ))}
      </div>
    </Bagian>
  );
}

function Testimoni({ isi }: { isi: Isi }) {
  const butir = daftarObjek(isi, 'butir');

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="grid gap-4 lg:grid-cols-2">
        {butir.map((satu, urutan) => (
          <Card key={urutan}>
            <CardContent className="grid gap-4 pt-6">
              <blockquote className="text-base">“{teks(satu, 'kutipan')}”</blockquote>
              <div className="text-sm text-muted-foreground">
                <div className="font-medium text-foreground">{teks(satu, 'nama')}</div>
                <div>
                  {[teksOpsional(satu, 'jabatan'), teksOpsional(satu, 'perusahaan')]
                    .filter((bagian): bagian is string => bagian !== null)
                    .join(' · ')}
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </Bagian>
  );
}

function DaftarTautan({ isi }: { isi: Isi }) {
  const butir = daftarObjek(isi, 'butir');

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {butir.map((satu, urutan) => {
          const url = urlAman(teksOpsional(satu, 'url'));

          return (
            <Card key={urutan}>
              <CardHeader>
                <CardTitle className="text-base">
                  {url ? (
                    <a href={url} className="hover:underline">
                      {teks(satu, 'judul')}
                    </a>
                  ) : (
                    teks(satu, 'judul')
                  )}
                </CardTitle>
              </CardHeader>
              <CardContent className="text-sm text-muted-foreground">{teks(satu, 'ringkasan')}</CardContent>
            </Card>
          );
        })}
      </div>
    </Bagian>
  );
}

function Perbandingan({ isi }: { isi: Isi }) {
  const kolom = daftarTeks(isi, 'kolom');
  const baris = daftarObjek(isi, 'baris');

  if (kolom.length === 0) {
    return null;
  }

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-sm">
          <thead className="bg-muted/50">
            <tr>
              <th className="p-3 text-left font-medium" />
              {kolom.map((satu) => (
                <th key={satu} className="p-3 text-left font-medium">
                  {satu}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {baris.map((satu, urutan) => (
              <tr key={urutan} className="border-t">
                <td className="p-3 font-medium">{teks(satu, 'label')}</td>
                {daftarTeks(satu, 'nilai').map((nilai, kolomKe) => (
                  <td key={kolomKe} className="p-3 text-muted-foreground">
                    {nilai}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Bagian>
  );
}

function Faq({ isi }: { isi: Isi }) {
  const butir = daftarObjek(isi, 'butir');

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="grid gap-4">
        {butir.map((satu, urutan) => (
          <details key={urutan} className="rounded-lg border p-4">
            <summary className="cursor-pointer font-medium">{teks(satu, 'tanya')}</summary>
            <p className="mt-3 text-sm text-muted-foreground">{teks(satu, 'jawab')}</p>
          </details>
        ))}
      </div>
    </Bagian>
  );
}

function Harga({ isi }: { isi: Isi }) {
  const paket = daftarObjek(isi, 'paket');

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      <div className="grid gap-4 lg:grid-cols-3">
        {paket.map((satu, urutan) => {
          const url = urlAman(teksOpsional(satu, 'ctaUrl'));

          return (
            <Card key={urutan}>
              <CardHeader className="grid gap-1">
                <CardTitle className="text-base">{teks(satu, 'nama')}</CardTitle>
                <span className="font-mono text-2xl font-semibold">{teks(satu, 'harga')}</span>
              </CardHeader>
              <CardContent className="grid gap-4">
                <ul className="grid gap-2 text-sm text-muted-foreground">
                  {daftarTeks(satu, 'fitur').map((fitur) => (
                    <li key={fitur}>{fitur}</li>
                  ))}
                </ul>
                {url ? (
                  <Button asChild>
                    <a href={url}>{teks(satu, 'ctaTeks', 'Pilih paket')}</a>
                  </Button>
                ) : null}
              </CardContent>
            </Card>
          );
        })}
      </div>
    </Bagian>
  );
}

function Cta({ isi }: { isi: Isi }) {
  return (
    <Bagian>
      <div className="grid gap-4 rounded-lg border bg-muted/30 p-8 text-center">
        <h2 className="text-2xl font-semibold tracking-tight">{teks(isi, 'judul')}</h2>
        {teksOpsional(isi, 'deskripsi') ? (
          <p className="mx-auto max-w-2xl text-muted-foreground">{teks(isi, 'deskripsi')}</p>
        ) : null}
        <div className="flex justify-center">
          <TombolCta isi={isi} />
        </div>
      </div>
    </Bagian>
  );
}

function BlokFormulir({ blok }: { blok: BlokHalaman }) {
  if (blok.Formulir === null) {
    return null;
  }

  return (
    <Bagian>
      <div className="mx-auto w-full max-w-xl rounded-lg border p-6">
        <FormulirPemasaran
          formulir={blok.Formulir}
          judul={teksOpsional(blok.Isi, 'judul') ?? undefined}
          deskripsi={teksOpsional(blok.Isi, 'deskripsi') ?? undefined}
        />
      </div>
    </Bagian>
  );
}

function ToolEmbed({ isi }: { isi: Isi }) {
  const url = urlAman(teksOpsional(isi, 'url'));

  if (url === null) {
    return null;
  }

  return (
    <Bagian>
      <JudulBagian isi={isi} />
      {/*
        Disandboxkan. Yang ditanam di sini adalah kalkulator dan perkakas dari
        luar; tanpa sandbox ia berjalan dengan hak penuh atas domain utama —
        termasuk cookienya.
      */}
      <iframe
        src={url}
        title={teks(isi, 'judul', 'Perkakas')}
        className="h-[32rem] w-full rounded-lg border"
        loading="lazy"
        sandbox="allow-scripts allow-forms allow-popups"
        referrerPolicy="no-referrer"
      />
    </Bagian>
  );
}

function FooterBlok({ isi }: { isi: Isi }) {
  const tautan = daftarObjek(isi, 'tautan');

  return (
    <Bagian rapat>
      <Separator className="mb-6" />
      <div className="flex flex-wrap items-center justify-between gap-4 text-sm text-muted-foreground">
        <span>{teks(isi, 'teks')}</span>
        <nav className="flex flex-wrap gap-4">
          {tautan.map((satu, urutan) => {
            const url = urlAman(teksOpsional(satu, 'url'));

            return url ? (
              <a key={urutan} href={url} className="hover:underline">
                {teks(satu, 'label')}
              </a>
            ) : null;
          })}
        </nav>
      </div>
    </Bagian>
  );
}
