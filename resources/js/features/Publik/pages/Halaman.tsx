import { Head } from '@inertiajs/react';
import { Blok } from '../components/Blok';
import { KerangkaPublik } from '../components/KerangkaPublik';
import type { IsiHalaman, PropsPublik } from '../types';

interface Props extends PropsPublik {
  halaman: IsiHalaman;
}

/** Satu halaman pemasaran yang disusun dari dashboard (MARKETING.md 8). */
export default function PublikHalaman({ halaman, kanonik, urlMasuk, urlDaftar }: Props) {
  const meta = halaman.Meta;
  const alamatKanonik = meta.Kanonik ?? kanonik;

  return (
    <>
      <Head>
        <title>{meta.Judul}</title>
        {meta.Deskripsi ? <meta name="description" content={meta.Deskripsi} /> : null}
        {/* Ditandai di dua tempat. */}
        {halaman.NoIndex ? <meta name="robots" content="noindex, nofollow" /> : null}
        {!halaman.NoIndex && alamatKanonik ? <link rel="canonical" href={alamatKanonik} /> : null}
        {meta.OgJudul ? <meta property="og:title" content={meta.OgJudul} /> : null}
        {meta.OgDeskripsi ? <meta property="og:description" content={meta.OgDeskripsi} /> : null}
        {meta.OgGambar ? <meta property="og:image" content={meta.OgGambar} /> : null}
      </Head>

      <KerangkaPublik urlMasuk={urlMasuk} urlDaftar={urlDaftar}>
        {halaman.Pratinjau ? (
          <div className="border-b border-safety-600/30 bg-safety-500/15 px-4 py-2 text-center text-sm text-safety-700">
            Pratinjau versi {halaman.VersiNomor}. Halaman ini belum tentu yang sedang terbit.
          </div>
        ) : null}

        {halaman.Blok.map((blok) => (
          <Blok key={blok.Id} blok={blok} />
        ))}
      </KerangkaPublik>
    </>
  );
}
