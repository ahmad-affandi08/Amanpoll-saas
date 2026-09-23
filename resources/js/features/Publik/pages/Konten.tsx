import { Head } from '@inertiajs/react';
import { KerangkaPublik } from '../components/KerangkaPublik';
import { Naskah } from '../components/Naskah';
import type { IsiKonten, PropsPublik } from '../types';

interface Props extends PropsPublik {
  konten: IsiKonten;
}

/** Satu konten CMS di situs publik (MARKETING.md 9). */
export default function PublikKonten({ konten, kanonik, urlMasuk, urlDaftar }: Props) {
  const meta = konten.Meta;
  const alamatKanonik = meta.Kanonik ?? kanonik;

  return (
    <>
      <Head>
        <title>{meta.Judul}</title>
        {meta.Deskripsi ? <meta name="description" content={meta.Deskripsi} /> : null}
        {/* Ditandai di dua tempat: di sini dan di peta situs. */}
        {konten.NoIndex ? <meta name="robots" content="noindex, nofollow" /> : null}
        {!konten.NoIndex && alamatKanonik ? <link rel="canonical" href={alamatKanonik} /> : null}
        <meta property="og:type" content="article" />
        {meta.OgJudul ? <meta property="og:title" content={meta.OgJudul} /> : null}
        {meta.OgDeskripsi ? <meta property="og:description" content={meta.OgDeskripsi} /> : null}
        {meta.OgGambar ? <meta property="og:image" content={meta.OgGambar} /> : null}
      </Head>

      <KerangkaPublik urlMasuk={urlMasuk} urlDaftar={urlDaftar}>
        {konten.Pratinjau ? (
          <div className="border-b border-safety-600/30 bg-safety-500/15 px-4 py-2 text-center text-sm text-safety-700">
            Pratinjau versi {konten.VersiNomor}. Konten ini belum tentu yang sedang terbit.
          </div>
        ) : null}

        <article className="mx-auto w-full max-w-3xl px-4 py-12">
          <p className="text-sm font-medium text-muted-foreground">{konten.Jenis}</p>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight">{konten.Judul}</h1>

          {konten.Ringkasan ? <p className="mt-4 text-lg text-muted-foreground">{konten.Ringkasan}</p> : null}

          {konten.PenulisNama ? (
            <p className="mt-4 text-sm text-muted-foreground">Ditulis oleh {konten.PenulisNama}</p>
          ) : null}

          <div className="mt-8">
            <Naskah isi={konten.IsiMarkdown} />
          </div>
        </article>
      </KerangkaPublik>
    </>
  );
}
