import { Head } from '@inertiajs/react';
import { LogoMark } from '@/components/shared/LogoMark';
import { Button } from '@/components/ui/button';

interface Props {
  kanonik: string | null;
  urlMasuk: string;
  urlDaftar: string;
}

/**
 * Beranda situs publik (MARKETING.md 34.1).
 *
 * Kerangka: isinya baru dapat disusun dari dashboard pada FASE 32. Yang sudah
 * berlaku di sini adalah aturan hostnya — halaman anonim, tanpa data tenant,
 * dan aksinya menyeberang ke host dashboard lewat URL absolut dari server.
 */
export default function Beranda({ kanonik, urlMasuk, urlDaftar }: Props) {
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

      <div className="flex min-h-screen flex-col bg-background">
        <header className="border-b">
          <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4">
            <LogoMark />
            <nav className="flex items-center gap-2">
              <Button variant="ghost" size="sm" asChild>
                <a href={urlMasuk}>Masuk</a>
              </Button>
              <Button size="sm" asChild>
                <a href={urlDaftar}>Coba Gratis</a>
              </Button>
            </nav>
          </div>
        </header>

        <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center gap-6 px-4 py-16">
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
        </main>

        <footer className="border-t">
          <div className="mx-auto w-full max-w-6xl px-4 py-6 text-sm text-muted-foreground">
            © {new Date().getFullYear()} Amanpoll
          </div>
        </footer>
      </div>
    </>
  );
}
