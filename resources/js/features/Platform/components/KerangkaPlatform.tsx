import type { PropsWithChildren } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Boxes, LogOut, Package, ShieldCheck, TrendingUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { rutePlatform } from '@/features/Platform/api';

const MENU = [
  { label: 'Paket', href: '/admin-platform/paket', ikon: Package, kodeIzin: null },
  { label: 'Langganan', href: '/admin-platform/langganan', ikon: Boxes, kodeIzin: null },
  {
    label: 'Growth & Marketing',
    href: '/admin-platform/pemasaran',
    ikon: TrendingUp,
    kodeIzin: 'platform.pemasaran.lihat',
  },
] as const;

interface PropsPlatform {
  Nama?: string;
  SuperAdmin?: boolean;
  Izin?: string[];
}

/** Kerangka konsol platform. */
export function KerangkaPlatform({ children }: PropsWithChildren) {
  const { url, props } = usePage<{ platform?: PropsPlatform }>();
  const platform = props.platform ?? {};

  // Menu mengikuti izin yang sama dengan yang ditegakkan backend, sehingga
  // konsol tidak pernah menawarkan halaman yang akan ditolak saat dibuka.
  const menu = MENU.filter(
    (item) =>
      item.kodeIzin === null || platform.SuperAdmin === true || (platform.Izin ?? []).includes(item.kodeIzin),
  );

  return (
    <div className="min-h-screen bg-permukaan-100">
      <header className="border-b border-border bg-card">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3 sm:px-6">
          <span className="flex items-center gap-2 font-semibold text-foreground">
            <ShieldCheck aria-hidden="true" className="size-5 text-primary" />
            Konsol Platform
          </span>

          <nav aria-label="Navigasi platform" className="flex items-center gap-1">
            {menu.map(({ label, href, ikon: Ikon }) => {
              const aktif = url.startsWith(href);

              return (
                <Link
                  key={href}
                  href={href}
                  aria-current={aktif ? 'page' : undefined}
                  className={cn(
                    'flex items-center gap-2 rounded-[6px] px-3 py-1.5 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    aktif
                      ? 'bg-teknisi-100 font-medium text-teknisi-900'
                      : 'text-grafit-700 hover:bg-accent hover:text-accent-foreground',
                  )}
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
            onClick={() => router.post(rutePlatform.logout)}
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
