import { ReactNode, useState } from 'react';
import {
  ColumnDef, ColumnFiltersState, SortingState, VisibilityState,
  flexRender, getCoreRowModel, getFacetedRowModel, getFacetedUniqueValues,
  getFilteredRowModel, getPaginationRowModel, getSortedRowModel, useReactTable,
} from '@tanstack/react-table';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DataTableToolbar, FilterFasetKolom } from '@/components/data-table/DataTableToolbar';
import { DataTablePagination } from '@/components/data-table/DataTablePagination';
import { EmptyState } from '@/components/shared/EmptyState';

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  pencarianPlaceholder?: string;
  facetedFilters?: FilterFasetKolom[];
  aksi?: ReactNode;
  pesanKosong?: string;
  ilustrasiKosong?: string;
}

export function DataTable<TData, TValue>({
  columns, data, pencarianPlaceholder, facetedFilters, aksi, pesanKosong = 'Tidak ada data.', ilustrasiKosong,
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

  return (
    <div className="rounded-[9px] border border-border bg-card">
      <DataTableToolbar table={table} pencarianPlaceholder={pencarianPlaceholder} facetedFilters={facetedFilters} aksi={aksi} />
      <Table>
        <TableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <TableRow key={headerGroup.id}>
              {headerGroup.headers.map((header) => (
                <TableHead key={header.id}>
                  {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                </TableHead>
              ))}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {table.getRowModel().rows.length
            ? table.getRowModel().rows.map((row) => (
                <TableRow key={row.id}>
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                  ))}
                </TableRow>
              ))
            : (
                <TableRow>
                  <TableCell colSpan={columns.length} className="p-0">
                    <EmptyState ilustrasi={ilustrasiKosong} judul={pesanKosong} />
                  </TableCell>
                </TableRow>
              )}
        </TableBody>
      </Table>
      <DataTablePagination table={table} />
    </div>
  );
}
