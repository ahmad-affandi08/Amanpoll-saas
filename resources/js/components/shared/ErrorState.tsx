import type { ReactNode } from 'react';
import { AlertTriangle, RotateCw } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props {
  judul?: string;
  deskripsi?: ReactNode;
  /** Pesan teknis; ditampilkan apa adanya sebagai teks, tidak pernah sebagai HTML. */
  rincian?: string | null;
  /** Mengganti aksi bawaan "Coba lagi". */
  aksi?: ReactNode;
}

/**
 * Keadaan gagal memuat (DESIGN.md 13.6).
 *
 * Dipisahkan dari EmptyState karena tindakan penggunanya berbeda: membuat data
 * versus mencoba lagi.
 */
export function ErrorState({
  judul = 'Data gagal dimuat.',
  deskripsi = 'Periksa koneksi Anda, lalu coba lagi. Jika terus berulang, hubungi administrator.',
  rincian,
  aksi,
}: Props) {
  return (
    <div role="alert" className="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center">
      <AlertTriangle aria-hidden="true" className="size-8 shrink-0 text-destructive" />

      <div className="space-y-1">
        <p className="text-sm font-medium text-foreground">{judul}</p>
        {deskripsi && <p className="max-w-prose text-sm text-muted-foreground">{deskripsi}</p>}
      </div>

      {rincian && (
        <p className="max-w-prose break-words rounded-[5px] bg-muted px-3 py-2 font-mono text-xs text-muted-foreground">
          {rincian}
        </p>
      )}

      {aksi ?? (
        <Button variant="outline" size="sm" onClick={() => router.reload()}>
          <RotateCw aria-hidden="true" className="size-4" />
          Coba lagi
        </Button>
      )}
    </div>
  );
}
