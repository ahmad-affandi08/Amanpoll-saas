import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginasi } from '@/types/global';

interface PaginationProps {
  meta: Paginasi<unknown>['meta'];
  onNavigasi: (halaman: number) => void;
}

export function Pagination({ meta, onNavigasi }: PaginationProps) {
  if (meta.last_page <= 1) return null;

  return (
    <div className="flex items-center justify-between border-t border-border px-4 py-3 text-sm text-muted-foreground">
      <span>
        Halaman {meta.current_page} dari {meta.last_page} ({meta.total} data)
      </span>
      <div className="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          disabled={meta.current_page <= 1}
          onClick={() => onNavigasi(meta.current_page - 1)}
        >
          Sebelumnya
        </Button>
        <Button
          variant="outline"
          size="sm"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onNavigasi(meta.current_page + 1)}
        >
          Berikutnya
        </Button>
      </div>
    </div>
  );
}

export function navigasiHalaman(halaman: number, paramLain: Record<string, string> = {}) {
  router.get(window.location.pathname, { ...paramLain, page: halaman }, { preserveState: true, preserveScroll: true });
}
