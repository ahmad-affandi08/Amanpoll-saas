import { ReactNode, useState } from 'react';
import {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DataTableToolbar, FilterFasetKolom } from '@/components/data-table/DataTableToolbar';
import { DataTablePagination } from '@/components/data-table/DataTablePagination';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { DataTableCard } from '@/components/data-table/DataTableCard';
import { BATAS_DAFTAR } from '@/lib/batas';

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  pencarianPlaceholder?: string;
  facetedFilters?: FilterFasetKolom[];
  aksi?: ReactNode;
  pesanKosong?: string;
  ilustrasiKosong?: string;
  /** Menyalakan tampilan kartu di bawah 640px. */
  kartuDiPonsel?: boolean;
}

export function DataTable<TData, TValue>({
  columns,
  data,
  pencarianPlaceholder,
  facetedFilters,
  aksi,
  pesanKosong = 'Tidak ada data.',
  ilustrasiKosong,
  kartuDiPonsel = false,
}: DataTableProps<TData, TValue>) {
  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});
  const [globalFilter, setGlobalFilter] = useState('');

  const table = useReactTable({
    data,
    columns,
    state: { sorting, columnFilters, columnVisibility, globalFilter },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    onGlobalFilterChange: setGlobalFilter,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
    initialState: { pagination: { pageSize: 15 } },
  });

  // Daftar yang tepat menyentuh batas server hampir pasti masih ada sisanya di belakang.
  const mungkinTerpotong = data.length >= BATAS_DAFTAR;

  return (
    <div className="rounded-[9px] border border-border bg-card">
      {mungkinTerpotong && (
        <p className="border-b border-border bg-muted/40 px-4 py-2 text-xs text-muted-foreground">
          Daftar dibatasi {BATAS_DAFTAR.toLocaleString('id-ID')} baris teratas. Pakai pencarian atau
          penyaring untuk mempersempit bila yang dicari belum tampak.
        </p>
      )}
      <DataTableToolbar
        table={table}
        pencarianPlaceholder={pencarianPlaceholder}
        facetedFilters={facetedFilters}
        aksi={aksi}
      />
      {kartuDiPonsel && (
        <div className="sm:hidden">
          <DataTableCard table={table} pesanKosong={pesanKosong} ilustrasiKosong={ilustrasiKosong} />
        </div>
      )}

      <Table className={kartuDiPonsel ? 'hidden sm:table' : undefined}>
        <TableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <TableRow key={headerGroup.id}>
              {headerGroup.headers.map((header) => (
                <TableHead key={header.id}>
                  {header.isPlaceholder
                    ? null
                    : flexRender(header.column.columnDef.header, header.getContext())}
                </TableHead>
              ))}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {table.getRowModel().rows.length ? (
            table.getRowModel().rows.map((row) => (
              <TableRow key={row.id}>
                {row.getVisibleCells().map((cell) => (
                  <TableCell key={cell.id}>
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </TableCell>
                ))}
              </TableRow>
            ))
          ) : (
            <TableRow>
              <TableCell colSpan={columns.length} className="p-0">
                <KeadaanKosong ilustrasi={ilustrasiKosong} judul={pesanKosong} />
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
      <DataTablePagination table={table} />
    </div>
  );
}
