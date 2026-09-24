import { useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Trash2 } from 'lucide-react';
import type { JenisKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogFormJenis } from '@/features/Kalibrasi/components/DialogFormJenis';
import { DialogTitikUkur } from '@/features/Kalibrasi/components/DialogTitikUkur';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  jenisKalibrasi: Paginasi<JenisKalibrasi>;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function KalibrasiJenisIndex({ jenisKalibrasi, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();

  const hapusJenis = async (jenis: JenisKalibrasi) => {
    if (
      await konfirmasi({
        judul: `Hapus jenis kalibrasi "${jenis.Nama}"?`,
        deskripsi: 'Jenis yang masih dipakai rencana atau pelaksanaan kalibrasi tidak dapat dihapus.',
        ragam: 'bahaya',
      })
    ) {
      router.delete(ruteKalibrasi.jenisDetail(jenis.Id), { preserveScroll: true });
    }
  };

  const columns = useMemo<ColumnDef<JenisKalibrasi>[]>(
    () => [
      {
        id: 'Kode',
        accessorFn: (row) => row.Kode,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => (
          <span className="font-mono font-semibold text-foreground">{row.original.Kode}</span>
        ),
        meta: { label: 'Kode' },
      },
      {
        id: 'Nama',
        accessorFn: (row) => row.Nama,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Metode / Jenis" />,
        cell: ({ row }) => <span className="font-medium text-foreground">{row.original.Nama}</span>,
        meta: { label: 'Nama Metode / Jenis' },
      },
      {
        id: 'Deskripsi',
        accessorFn: (row) => row.Deskripsi ?? '',
        header: 'Deskripsi & Standar Acuan',
        cell: ({ row }) =>
          row.original.Deskripsi ? (
            <span className="block max-w-xs truncate text-muted-foreground">{row.original.Deskripsi}</span>
          ) : (
            <span className="italic text-muted-foreground">Tidak ada deskripsi</span>
          ),
        // Isinya panjang dan tidak dipakai mengurutkan; kunci urutnya pun tidak didaftarkan server.
        enableSorting: false,
        meta: { label: 'Deskripsi & Standar Acuan' },
      },
      {
        id: 'titikUkur',
        header: 'Titik Ukur Default',
        cell: ({ row }) => <DialogTitikUkur jenis={row.original} wajib={wajib.titikUkur} />,
        // Jumlah titik ukur dihitung dari relasi, bukan kolom yang dapat diurutkan server.
        enableSorting: false,
        meta: { label: 'Titik Ukur Default' },
      },
      {
        id: 'Aktif',
        accessorFn: (row) => (row.Aktif ? '1' : '0'),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Aktif ? 'sukses' : 'netral'}>
            {row.original.Aktif ? 'Aktif' : 'Nonaktif'}
          </Badge>
        ),
        meta: { label: 'Status' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex items-center justify-end gap-1">
            <DialogFormJenis jenis={row.original} wajib={wajib.jenis} />
            <Button
              variant="ghost"
              size="icon"
              onClick={() => hapusJenis(row.original)}
              className="sm:size-7 text-bahaya-600 hover:bg-bahaya-600/10 hover:text-bahaya-700"
            >
              <Trash2 className="size-3.5" />
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi' },
      },
    ],
    [wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Jenis Kalibrasi & Titik Ukur Standar" />

      <KepalaHalaman
        judul="Jenis Kalibrasi"
        deskripsi="Atur metode, spesifikasi unit, dan template titik ukur standar untuk instrumen dan alat uji."
        aksi={<DialogFormJenis jenis={null} wajib={wajib.jenis} />}
        className="mb-5"
      />

      {jenisKalibrasi.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          judul="Belum ada jenis kalibrasi."
          deskripsi="Tambahkan jenis kalibrasi seperti Kalibrasi Suhu, Tekanan, Dimensi, atau Listrik."
        />
      ) : (
        <DataTable
          columns={columns}
          data={jenisKalibrasi.data}
          server={{ meta: jenisKalibrasi.meta, filter }}
          ekspor="/kalibrasi/jenis/ekspor"
          facetedFilters={[
            {
              columnId: 'Aktif',
              title: 'Status',
              options: [
                { label: 'Aktif', value: '1' },
                { label: 'Nonaktif', value: '0' },
              ],
            },
          ]}
          pencarianPlaceholder="Cari kode, metode, atau deskripsi kalibrasi..."
          pesanKosong="Tidak ada jenis kalibrasi yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
