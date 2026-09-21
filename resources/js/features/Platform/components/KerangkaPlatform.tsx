import type { PropsWithChildren } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Boxes, LogOut, Package, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';

const MENU = [
  { label: 'Paket', href: '/admin-platform/paket', ikon: Package },
  { label: 'Langganan', href: '/admin-platform/langganan', ikon: Boxes },
] as const;

/**
 * Kerangka konsol platform.
 *
 * Sengaja tidak memakai AppLayout: layout tenant memuat navigasi modul, menu
 * organisasi, dan indikator sinkronisasi offline — semuanya bergantung pada
 * konteks tenant yang tidak ada di sini, dan semuanya menyesatkan bila muncul
 * pada konsol lintas tenant.
 */
export function KerangkaPlatform({ children }: PropsWithChildren) {
  const { url } = usePage();

  return (
    <div className="min-h-screen bg-permukaan-100">
      <header className="border-b border-border bg-card">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3 sm:px-6">
          <span className="flex items-center gap-2 font-semibold text-foreground">
            <ShieldCheck aria-hidden="true" className="size-5 text-primary" />
            Konsol Platform
          </span>

          <nav aria-label="Navigasi platform" className="flex items-center gap-1">
            {MENU.map(({ label, href, ikon: Ikon }) => {
              const aktif = url.startsWith(href);

              return (
                <Link
                  key={href}
                  href={href}
                  aria-current={aktif ? 'page' : undefined}
                  className={
                    aktif
                      ? 'flex items-center gap-2 rounded-[6px] bg-muted px-3 py-1.5 text-sm font-medium text-foreground'
                      : 'flex items-center gap-2 rounded-[6px] px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted/60'
                  }
                >
                  <Ikon aria-hidden="true" className="size-4" />
                  {label}
                </Link>
              );
            })}
          </nav>

          <Button
            variant="ghost"
            size="sm"
            className="ms-auto"
            onClick={() => router.post('/admin-platform/logout')}
          >
            <LogOut aria-hidden="true" className="size-4" />
            Keluar
          </Button>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-6 sm:px-6">{children}</main>
    </div>
  );
}
