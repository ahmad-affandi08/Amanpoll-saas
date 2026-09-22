import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { KerangkaPublik } from '../components/KerangkaPublik';
import type { PropsPublik } from '../types';

/**
 * Beranda bawaan situs publik (MARKETING.md 34.1).
 *
 * Tampil hanya selama belum ada halaman pemasaran yang terbit di `/`. Begitu
 * ada, akar situs dilayani halaman itu — situs publik tidak boleh kosong di
 * alamat utamanya hanya karena isinya belum disusun.
 */
export default function Beranda({ kanonik, urlMasuk, urlDaftar }: PropsPublik) {
  return (
    <>
      <Head>
        <title>Amanpoll — Manajemen Aset dan Pemeliharaan</title>
        <meta
          name="description"
          content="Amanpoll membantu organisasi mengelola aset, pemeliharaan, suku cadang, dan kalibrasi dalam satu sistem."
        />
        {kanonik ? <link rel="canonical" href={kanonik} /> : null}
      </Head>

      <KerangkaPublik urlMasuk={urlMasuk} urlDaftar={urlDaftar}>
        <div className="mx-auto flex w-full max-w-6xl flex-col justify-center gap-6 px-4 py-16">
          <h1 className="max-w-3xl text-3xl font-semibold tracking-tight sm:text-5xl">
            Aset, pemeliharaan, dan suku cadang dalam satu sistem
          </h1>
          <p className="max-w-2xl text-base text-muted-foreground sm:text-lg">
            Amanpoll mencatat setiap aset, menjadwalkan pemeliharaannya, dan menjaga
            ketersediaan suku cadang — sampai ke teknisi yang bekerja tanpa sinyal.
          </p>
          <div className="flex flex-wrap gap-3">
            <Button size="lg" asChild>
              <a href={urlDaftar}>Coba Gratis</a>
            </Button>
            <Button size="lg" variant="outline" asChild>
              <a href={urlMasuk}>Masuk ke Dashboard</a>
            </Button>
          </div>
        </div>
      </KerangkaPublik>
    </>
  );
}
