import { useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { HalamanRingkas, PilihanHalaman } from '@/features/Pemasaran/types';

interface Props {
  halaman: HalamanRingkas[];
  pilihan: PilihanHalaman;
}

const RAGAM_STATUS: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
  Terbit: 'default',
  Terjadwal: 'secondary',
  Review: 'secondary',
  Draf: 'outline',
  Diarsipkan: 'destructive',
};

export default function Index({ halaman }: Props) {
  const columns = useMemo<ColumnDef<HalamanRingkas>[]>(
    () => [
      {
        id: 'Judul',
        accessorFn: (row) => `${row.Judul} ${row.Slug}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Halaman" />,
        cell: ({ row }) => (
          <div>
            <Link
              href={`/admin-platform/pemasaran/halaman/${row.original.Id}`}
              className="font-medium text-foreground hover:underline"
            >
              {row.original.Judul}
            </Link>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Slug}</div>
          </div>
        ),
        meta: { label: 'Halaman', kartu: 'judul' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <div className="flex flex-wrap gap-1">
            <Badge variant={RAGAM_STATUS[row.original.Status] ?? 'outline'}>{row.original.Status}</Badge>
            {row.original.NoIndex ? <Badge variant="outline">noindex</Badge> : null}
          </div>
        ),
        meta: { label: 'Status' },
      },
      {
        id: 'Tipe',
        accessorFn: (row) => row.Tipe,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tipe" />,
        meta: { label: 'Tipe' },
      },
      {
        id: 'Versi',
        header: 'Versi',
        cell: ({ row }) => (
          <span className="font-mono text-xs text-muted-foreground">
            terbit {row.original.VersiTerbitNomor ?? '—'} · draf {row.original.VersiDrafNomor ?? '—'}
          </span>
        ),
        enableSorting: false,
        meta: { label: 'Versi' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            {row.original.Status === 'Terbit' && row.original.UrlPublik ? (
              <Button variant="ghost" size="sm" asChild>
                <a href={row.original.UrlPublik} target="_blank" rel="noreferrer">
                  Lihat
                </a>
              </Button>
            ) : null}
            <Button variant="outline" size="sm" asChild>
              <Link href={`/admin-platform/pemasaran/halaman/${row.original.Id}`}>Sunting</Link>
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    [],
  );

  return (
    <KerangkaPlatform>
      <Head title="Halaman Pemasaran" />

      <PageHeader
        judul="Halaman Pemasaran"
        deskripsi="Landing page disusun dan diterbitkan dari sini, tanpa deploy."
        tanpaBreadcrumb
        aksi={
          <Button asChild>
            <Link href="/admin-platform/pemasaran/halaman/baru">Halaman Baru</Link>
          </Button>
        }
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={halaman}
        kartuDiPonsel
        pencarianPlaceholder="Cari judul atau slug..."
        pesanKosong="Belum ada halaman pemasaran."
      />
    </KerangkaPlatform>
  );
}
