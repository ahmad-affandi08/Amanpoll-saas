import { ReactNode } from 'react';
import { LogoMark } from '@/components/shared/LogoMark';
import { Button } from '@/components/ui/button';

interface Props {
  urlMasuk: string;
  urlDaftar: string;
  children: ReactNode;
}

/** Kerangka setiap halaman situs publik (MARKETING.md 34.1). */
export function KerangkaPublik({ urlMasuk, urlDaftar, children }: Props) {
  return (
    <div className="flex min-h-screen flex-col bg-background">
      <header className="border-b">
        <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4">
          <a href="/" aria-label="Beranda Amanpoll">
            <LogoMark />
          </a>
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

      <main className="flex-1">{children}</main>

      <footer className="border-t">
        <div className="mx-auto w-full max-w-6xl px-4 py-6 text-sm text-muted-foreground">
          © {new Date().getFullYear()} Amanpoll
        </div>
      </footer>
    </div>
  );
}
