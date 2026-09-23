import { useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import { formatUang } from '@/lib/uang';
import { ruteAset } from '@/features/Aset/api';
import type { Paginasi } from '@/types/global';

interface BarisKelayakan {
  Id: string;
  KodeAset: string;
  Nama: string;
  NamaKategoriAset: string | null;
  NamaLokasi: string | null;
  Kondisi: string;
  UsiaPakaiTahun: number;
  SisaUsiaManfaatTahun: number;
  Aic: number;
  Mmel: number;
  BiayaPerbaikanKumulatif: number;
  LayakDiperbaiki: boolean;
  Alasan: string;
}

interface Props {
  aset: Paginasi<BarisKelayakan>;
  filter: FilterDaftar;
  parameter: { LajuInflasi: number; FaktorMel: number; PersenPemeliharaanAic: number };
}

export default function AsetKelayakan({ aset, filter, parameter }: Props) {
  const columns = useMemo<ColumnDef<BarisKelayakan>[]>(
    () => [
      {
        accessorKey: 'KodeAset',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => (
          <Link href={ruteAset.detail(row.original.Id)} className="font-mono text-sm hover:underline">
            {row.original.KodeAset}
          </Link>
        ),
        meta: { label: 'Kode' },
      },
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Aset" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="text-xs text-muted-foreground">
              {[row.original.NamaKategoriAset, row.original.NamaLokasi].filter(Boolean).join(' · ') || '—'}
            </div>
          </div>
        ),
        meta: { label: 'Aset' },
      },
      {
        accessorKey: 'SisaUsiaManfaatTahun',
        enableSorting: false,
        header: 'Sisa Usia',
        cell: ({ row }) => `${row.original.SisaUsiaManfaatTahun} th`,
        meta: { label: 'Sisa Usia' },
      },
      {
        accessorKey: 'Aic',
        enableSorting: false,
        header: 'AIC',
        cell: ({ row }) => formatUang(row.original.Aic),
        meta: { label: 'AIC' },
      },
      {
        accessorKey: 'Mmel',
        enableSorting: false,
        header: 'MMEL',
        cell: ({ row }) => formatUang(row.original.Mmel),
        meta: { label: 'MMEL' },
      },
      {
        accessorKey: 'BiayaPerbaikanKumulatif',
        enableSorting: false,
        header: 'Biaya Perbaikan',
        cell: ({ row }) => (
          <span
            className={
              row.original.BiayaPerbaikanKumulatif > row.original.Mmel ? 'font-medium text-destructive' : ''
            }
          >
            {formatUang(row.original.BiayaPerbaikanKumulatif)}
          </span>
        ),
        meta: { label: 'Biaya Perbaikan' },
      },
      {
        accessorKey: 'LayakDiperbaiki',
        enableSorting: false,
        header: 'Putusan',
        cell: ({ row }) => (
          <div className="max-w-xs">
            <Badge variant={row.original.LayakDiperbaiki ? 'sukses' : 'bahaya'}>
              {row.original.LayakDiperbaiki ? 'Layak diperbaiki' : 'Disarankan diganti'}
            </Badge>
            <div className="mt-1 text-xs text-muted-foreground">{row.original.Alasan}</div>
          </div>
        ),
        meta: { label: 'Putusan' },
      },
    ],
    [],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kelayakan Aset" />
      <KepalaHalaman
        judul="Kelayakan Aset"
        deskripsi="Perbandingan biaya perbaikan terhadap batas MMEL, sebagai dasar usulan penggantian."
        className="mb-6"
      />

      <p className="mb-4 rounded-lg border border-dashed p-3 text-sm text-muted-foreground">
        Dihitung dengan laju inflasi {(parameter.LajuInflasi * 100).toFixed(1)}%, faktor MEL{' '}
        {parameter.FaktorMel.toFixed(2)}, dan anggaran pemeliharaan{' '}
        {(parameter.PersenPemeliharaanAic * 100).toFixed(1)}% dari AIC. Ketiganya diatur per organisasi
        — sesuaikan dengan acuan yang berlaku sebelum angkanya dipakai memutuskan penggantian alat.
      </p>

      <DataTable
        columns={columns}
        data={aset.data}
        server={{ meta: aset.meta, filter }}
        ekspor="/aset/kelayakan/ekspor"
        pencarianPlaceholder="Cari kode, nama, atau nomor seri..."
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada aset yang cocok.' : 'Belum ada aset.'}
      />
    </KerangkaAplikasi>
  );
}
