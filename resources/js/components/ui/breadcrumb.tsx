import type { ComponentProps } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface JejakBreadcrumb {
  label: string;
  /** Tanpa href berarti posisi sekarang; tidak dijadikan tautan. */
  href?: string;
}

/**
 * Breadcrumb (DESIGN.md 12).
 *
 * Ruas terakhir dirender sebagai teks ber-`aria-current`, bukan tautan ke
 * halaman yang sedang dibuka — tautan yang tidak ke mana-mana membingungkan
 * pembaca layar sekaligus pengguna tetikus.
 *
 * Di layar sempit ruas tengah disembunyikan dan disisakan induk terdekat saja,
 * supaya breadcrumb tidak memakan dua baris di ponsel.
 */
export function Breadcrumb({
  jejak,
  className,
  ...props
}: { jejak: JejakBreadcrumb[] } & ComponentProps<'nav'>) {
  if (jejak.length === 0) {
    return null;
  }

  return (
    <nav aria-label="Breadcrumb" className={cn('min-w-0', className)} {...props}>
      <ol className="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-muted-foreground">
        {jejak.map((ruas, indeks) => {
          const terakhir = indeks === jejak.length - 1;
          // Ruas tengah disembunyikan di ponsel; yang pertama dan dua terakhir
          // sudah cukup untuk menjelaskan posisi.
          const sembunyiDiPonsel = !terakhir && indeks !== 0 && indeks < jejak.length - 2;

          return (
            <li
              key={`${ruas.label}-${indeks}`}
              className={cn('flex min-w-0 items-center gap-1.5', sembunyiDiPonsel && 'hidden sm:flex')}
            >
              {indeks > 0 && (
                <ChevronRight aria-hidden="true" className="size-3 shrink-0 text-muted-foreground/60" />
              )}

              {terakhir || !ruas.href ? (
                <span
                  aria-current={terakhir ? 'page' : undefined}
                  className={cn('truncate', terakhir && 'font-medium text-foreground')}
                >
                  {ruas.label}
                </span>
              ) : (
                <Link
                  href={ruas.href}
                  className="truncate rounded-[3px] hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                  {ruas.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
