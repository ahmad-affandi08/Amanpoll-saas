'use client';

import type { ReactNode } from 'react';
import { RotateCcw, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface Props {
  kueri: string;
  onKueri: (nilai: string) => void;
  placeholder?: string;
  /** Kontrol filter; ditempatkan setelah kolom pencarian pada satu baris. */
  filter?: ReactNode;
  /** Ditampilkan hanya bila ada filter yang sedang aktif. */
  onReset?: () => void;
  adaFilterAktif?: boolean;
  /** Aksi di ujung baris, mis. tombol ekspor. */
  aksi?: ReactNode;
  className?: string;
}

/**
 * Baris pencarian dan filter baku (DESIGN.md 9, 12).
 *
 * Seluruh filter berada pada satu baris di atas isi, dan tombol reset hanya
 * muncul ketika ada yang perlu direset — tombol yang selalu ada tetapi sering
 * tidak berguna melatih pengguna untuk mengabaikannya.
 *
 * Di ponsel kolom pencarian melebar penuh dan filter membungkus ke bawahnya,
 * bukan menyusut menjadi kotak-kotak sempit yang sulit disentuh.
 */
export function SearchFilterBar({
  kueri,
  onKueri,
  placeholder = 'Cari…',
  filter,
  onReset,
  adaFilterAktif = false,
  aksi,
  className,
}: Props) {
  return (
    <div className={cn('flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center', className)}>
      <div className="relative min-w-0 flex-1 sm:max-w-xs">
        <Search
          aria-hidden="true"
          className="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
          type="search"
          value={kueri}
          onChange={(e) => onKueri(e.target.value)}
          placeholder={placeholder}
          aria-label={placeholder}
          className="ps-8"
        />
      </div>

      {filter && <div className="flex flex-wrap items-center gap-2">{filter}</div>}

      {onReset && adaFilterAktif && (
        <Button type="button" variant="ghost" size="sm" onClick={onReset}>
          <RotateCcw aria-hidden="true" className="size-4" />
          Reset filter
        </Button>
      )}

      {aksi && <div className="flex flex-wrap items-center gap-2 sm:ms-auto">{aksi}</div>}
    </div>
  );
}
