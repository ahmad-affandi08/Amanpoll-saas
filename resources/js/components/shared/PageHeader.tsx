import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { Breadcrumb, type JejakBreadcrumb } from '@/components/ui/breadcrumb';
import { breadcrumbUntuk } from '@/layouts/breadcrumb-otomatis';
import { cn } from '@/lib/utils';

interface Props {
  judul: ReactNode;
  deskripsi?: ReactNode;
  /** Ditempel di samping judul, mis. lencana status pada halaman detail. */
  lencana?: ReactNode;
  /** Aksi utama halaman; di ponsel turun ke baris sendiri selebar penuh. */
  aksi?: ReactNode;
  /** Baris metadata di bawah judul pada halaman detail. */
  meta?: ReactNode;
  /** Mengganti label ruas terakhir breadcrumb, mis. dengan nama entitas. */
  labelBreadcrumb?: string;
  /** Jejak breadcrumb khusus bila penurunan dari URL tidak tepat. */
  breadcrumb?: JejakBreadcrumb[];
  /** Menyembunyikan breadcrumb pada halaman yang tidak berada di hierarki menu. */
  tanpaBreadcrumb?: boolean;
  className?: string;
}

/** Kepala halaman baku (DESIGN.md 12). */
export function PageHeader({
  judul,
  deskripsi,
  lencana,
  aksi,
  meta,
  labelBreadcrumb,
  breadcrumb,
  tanpaBreadcrumb = false,
  className,
}: Props) {
  const { url } = usePage();
  const jejak = breadcrumb ?? breadcrumbUntuk(url, labelBreadcrumb);

  return (
    <header className={cn('space-y-3', className)}>
      {!tanpaBreadcrumb && <Breadcrumb jejak={jejak} />}

      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0 space-y-1">
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">{judul}</h1>
            {lencana}
          </div>

          {deskripsi && <p className="text-sm text-muted-foreground">{deskripsi}</p>}
          {meta && <div className="pt-0.5 text-sm text-muted-foreground">{meta}</div>}
        </div>

        {aksi && (
          <div className="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center [&>*]:w-full sm:[&>*]:w-auto">
            {aksi}
          </div>
        )}
      </div>
    </header>
  );
}
