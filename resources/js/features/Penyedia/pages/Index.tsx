import { useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { Penyedia, KategoriPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { DialogKelolaKategori } from '@/features/Penyedia/components/DialogKelolaKategori';
import { DialogKelolaPenyedia } from '@/features/Penyedia/components/DialogKelolaPenyedia';
import { DialogTambahPenyedia } from '@/features/Penyedia/components/DialogTambahPenyedia';

interface Props {
  penyedia: Paginasi<Penyedia>;
  kategoriPenyedia: KategoriPenyedia[];
  filter: FilterDaftar;
}

export default function PenyediaIndex({ penyedia, kategoriPenyedia, filter }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: Penyedia) => {
    if (
      !(await konfirmasi({
        judul: `Hapus penyedia "${item.Nama}"?`,
        deskripsi: 'Penyedia yang masih terhubung ke pengadaan atau kontrak tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(rutePenyedia.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Penyedia>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <Link href={rutePenyedia.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </Link>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'NamaKategoriPenyedia',
        accessorFn: (row) => row.NamaKategoriPenyedia,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => (
          <div className="flex flex-wrap gap-1">
            {row.original.NamaKategoriPenyedia.length === 0 && '—'}
            {row.original.NamaKategoriPenyedia.map((nama) => (
              <Badge key={nama} variant="secondary">
                {nama}
              </Badge>
            ))}
          </div>
        ),
        // Relasi banyak-ke-banyak; penyaringannya dijalankan server lewat faset KategoriPenyediaId.
        enableSorting: false,
        meta: { label: 'Kategori' },
      },
      {
        id: 'Kontak',
        accessorFn: (row) => `${row.Email ?? ''} ${row.Telepon ?? ''}`,
        header: 'Kontak',
        cell: ({ row }) => (
          <div className="text-sm">
            <div>{row.original.Email ?? '—'}</div>
            <div className="text-xs text-muted-foreground">{row.original.Telepon ?? '—'}</div>
          </div>
        ),
        meta: { label: 'Kontak' },
      },
      {
        accessorKey: 'Status',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Status === 'Aktif' ? 'default' : 'outline'}>
            {row.original.Status}
          </Badge>
        ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Status' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogKelolaPenyedia penyedia={row.original} kategoriPenyedia={kategoriPenyedia} />
            <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>
              Hapus
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi' },
      },
    ],
    [kategoriPenyedia],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Penyedia" />
      <KepalaHalaman
        judul="Penyedia"
        deskripsi="Kelola data vendor/supplier untuk pengadaan, kontrak, dan kalibrasi."
        aksi={
          <>
            <div className="flex gap-2">
              <DialogKelolaKategori kategoriPenyedia={kategoriPenyedia} />
              <DialogTambahPenyedia />
            </div>
          </>
        }
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={penyedia.data}
        server={{ meta: penyedia.meta, filter }}
        pencarianPlaceholder="Cari nama, kode, atau email penyedia..."
        facetedFilters={[
          {
            columnId: 'Status',
            title: 'Status',
            options: [
              { label: 'Aktif', value: 'Aktif' },
              { label: 'Nonaktif', value: 'Nonaktif' },
            ],
          },
          {
            columnId: 'KategoriPenyediaId',
            title: 'Kategori',
            options: kategoriPenyedia.map((k) => ({ label: k.Nama, value: k.Id })),
          },
        ]}
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada penyedia yang cocok.' : 'Belum ada penyedia.'}
        ilustrasiKosong="/assets/3d/penyedia-kontrak.webp"
      />
    </KerangkaAplikasi>
  );
}
