import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Aset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';
import { cn } from '@/lib/utils';

/** QR aset yang benar-benar dapat dipindai, beserta jalan pintas mencetaknya. */
export function KartuQr({ aset, qr, className }: { aset: Aset; qr: string | null; className?: string }) {
  return (
    <div
      className={cn(
        'mb-5 flex flex-wrap items-center gap-4 rounded-md border border-border bg-card p-4',
        className,
      )}
    >
      {qr ? (
        <div
          className="size-24 shrink-0 [&>svg]:h-full [&>svg]:w-full"
          // QR dirender sebagai SVG di server; isinya hanya KodeQr milik aset ini.
          dangerouslySetInnerHTML={{ __html: qr }}
        />
      ) : (
        <div className="flex size-24 shrink-0 items-center justify-center rounded-sm border border-dashed border-border px-2 text-center text-xs text-muted-foreground">
          Belum ada kode QR
        </div>
      )}

      <div className="min-w-0 flex-1">
        <p className="text-sm font-medium text-foreground">Label Aset</p>
        <p className="mt-0.5 text-sm text-muted-foreground">
          {qr
            ? 'Tempelkan pada aset fisik. Memindainya membuka halaman ini.'
            : 'Aset ini belum punya kode QR, jadi tidak dapat dipindai.'}
        </p>
        {aset.KodeQr && (
          <p className="mt-1 font-mono text-xs break-all text-muted-foreground">{aset.KodeQr}</p>
        )}
      </div>

      <Button variant="outline" asChild>
        <Link href={ruteAset.label([aset.Id])}>Cetak Label</Link>
      </Button>
    </div>
  );
}
