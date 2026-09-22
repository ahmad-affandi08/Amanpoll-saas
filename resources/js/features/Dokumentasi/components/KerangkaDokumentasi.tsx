import { useEffect, useState, type ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Menu, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { LogoMendatar } from '@/components/shared/Logo';
import { ruteDokumentasi } from '@/features/Dokumentasi/api';
import { grupDokumentasi, halamanKe, semuaHalaman } from '@/features/Dokumentasi/daftar-halaman';

export interface ButirDaftarIsi {
  id: string;
  judul: string;
}

interface Props {
  slug: string;
  judul: string;
  ringkas: string;
  daftarIsi: ButirDaftarIsi[];
  children: ReactNode;
}

function NavIsi({ slug, onPilih }: { slug: string; onPilih?: () => void }) {
  return (
    <nav aria-label="Daftar halaman dokumentasi" className="space-y-6">
      {grupDokumentasi.map((grup) => (
        <div key={grup.label} className="space-y-1">
          <p className="px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            {grup.label}
          </p>
          {grup.halaman.map((satu) => (
            <Link
              key={satu.slug}
              href={ruteDokumentasi.halaman(satu.slug)}
              onClick={onPilih}
              aria-current={satu.slug === slug ? 'page' : undefined}
              className={cn(
                'block rounded-[6px] px-2 py-1.5 text-sm transition-colors',
                satu.slug === slug
                  ? 'bg-permukaan-100 font-medium text-foreground'
                  : 'text-grafit-700 hover:bg-permukaan-100 hover:text-foreground',
              )}
            >
              {satu.judul}
            </Link>
          ))}
        </div>
      ))}
    </nav>
  );
}

/** Menyorot bagian yang sedang dibaca, ditentukan dari judul teratas yang masih di atas layar. */
function useBagianAktif(daftarIsi: ButirDaftarIsi[]): string {
  const [aktif, setAktif] = useState(daftarIsi[0]?.id ?? '');

  useEffect(() => {
    setAktif(daftarIsi[0]?.id ?? '');

    const hitung = () => {
      const terlihat = daftarIsi
        .map((satu) => {
          const elemen = document.getElementById(satu.id);

          return elemen ? { id: satu.id, atas: elemen.getBoundingClientRect().top } : null;
        })
        .filter((satu): satu is { id: string; atas: number } => satu !== null);

      const lewat = terlihat.filter((satu) => satu.atas <= 120);
      const pilih = lewat.length > 0 ? lewat[lewat.length - 1] : terlihat[0];

      if (pilih) {
        setAktif(pilih.id);
      }
    };

    hitung();
    window.addEventListener('scroll', hitung, { passive: true });

    return () => window.removeEventListener('scroll', hitung);
  }, [daftarIsi]);

  return aktif;
}

export function KerangkaDokumentasi({ slug, judul, ringkas, daftarIsi, children }: Props) {
  const [navPonsel, setNavPonsel] = useState(false);
  const aktif = useBagianAktif(daftarIsi);
  const ke = halamanKe(slug);
  const sebelum = ke > 0 ? semuaHalaman[ke - 1] : null;
  const sesudah = ke >= 0 && ke < semuaHalaman.length - 1 ? semuaHalaman[ke + 1] : null;

  return (
    <div className="min-h-screen bg-background">
      <header className="sticky top-0 z-30 border-b border-border bg-card/95 backdrop-blur">
        <div className="mx-auto flex h-14 max-w-[90rem] items-center gap-3 px-4 sm:px-6">
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden"
            aria-label={navPonsel ? 'Tutup daftar halaman' : 'Buka daftar halaman'}
            onClick={() => setNavPonsel((buka) => !buka)}
          >
            {navPonsel ? <X className="size-4" /> : <Menu className="size-4" />}
          </Button>

          <Link href={ruteDokumentasi.index} className="flex items-center gap-2">
            <LogoMendatar className="h-6 w-auto" />
            <span className="text-sm font-semibold text-foreground">Dokumentasi</span>
          </Link>

          <div className="ml-auto">
            <Button variant="outline" size="sm" asChild>
              <Link href="/">
                <ArrowLeft aria-hidden="true" className="size-3.5" />
                Kembali ke aplikasi
              </Link>
            </Button>
          </div>
        </div>
      </header>

      {navPonsel && (
        <div className="border-b border-border bg-card px-4 py-4 lg:hidden">
          <div className="mb-3 flex justify-end">
            <Button variant="ghost" size="icon" aria-label="Tutup" onClick={() => setNavPonsel(false)}>
              <X className="size-4" />
            </Button>
          </div>
          <NavIsi slug={slug} onPilih={() => setNavPonsel(false)} />
        </div>
      )}

      <div className="mx-auto flex max-w-[90rem] gap-8 px-4 sm:px-6">
        <aside className="hidden w-56 shrink-0 lg:block">
          <div className="sticky top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto py-8 pr-2">
            <NavIsi slug={slug} />
          </div>
        </aside>

        <main className="min-w-0 flex-1 py-8 xl:py-10">
          <article className="max-w-3xl">
            <h1 className="text-3xl font-semibold tracking-tight text-foreground">{judul}</h1>
            <p className="mt-3 text-base leading-7 text-muted-foreground">{ringkas}</p>

            <div className="mt-10">{children}</div>

            <nav
              aria-label="Halaman sebelum dan sesudah"
              className="mt-16 flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:justify-between"
            >
              {sebelum ? (
                <Link
                  href={ruteDokumentasi.halaman(sebelum.slug)}
                  className="group flex flex-1 items-center gap-3 rounded-[9px] border border-border p-4 transition-colors hover:border-primary/40"
                >
                  <ArrowLeft aria-hidden="true" className="size-4 shrink-0 text-muted-foreground" />
                  <span className="min-w-0">
                    <span className="block text-xs text-muted-foreground">Sebelumnya</span>
                    <span className="block truncate text-sm font-medium text-foreground">
                      {sebelum.judul}
                    </span>
                  </span>
                </Link>
              ) : (
                <span className="flex-1" />
              )}

              {sesudah ? (
                <Link
                  href={ruteDokumentasi.halaman(sesudah.slug)}
                  className="group flex flex-1 items-center justify-end gap-3 rounded-[9px] border border-border p-4 text-right transition-colors hover:border-primary/40"
                >
                  <span className="min-w-0">
                    <span className="block text-xs text-muted-foreground">Berikutnya</span>
                    <span className="block truncate text-sm font-medium text-foreground">
                      {sesudah.judul}
                    </span>
                  </span>
                  <ArrowRight aria-hidden="true" className="size-4 shrink-0 text-muted-foreground" />
                </Link>
              ) : (
                <span className="flex-1" />
              )}
            </nav>
          </article>
        </main>

        {daftarIsi.length > 0 && (
          <aside className="hidden w-56 shrink-0 xl:block">
            <div className="sticky top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto py-10">
              <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Di halaman ini
              </p>
              <ul className="space-y-1 border-l border-border">
                {daftarIsi.map((satu) => (
                  <li key={satu.id}>
                    <a
                      href={`#${satu.id}`}
                      aria-current={satu.id === aktif ? 'true' : undefined}
                      className={cn(
                        '-ml-px block border-l py-1 pl-3 text-sm transition-colors',
                        satu.id === aktif
                          ? 'border-primary font-medium text-primary'
                          : 'border-transparent text-muted-foreground hover:text-foreground',
                      )}
                    >
                      {satu.judul}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          </aside>
        )}
      </div>
    </div>
  );
}
