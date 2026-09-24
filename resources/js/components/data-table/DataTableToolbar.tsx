import { ReactNode } from 'react';
import { Table } from '@tanstack/react-table';
import { Search, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DataTableFacetedFilter } from '@/components/data-table/DataTableFacetedFilter';
import { DataTableViewOptions } from '@/components/data-table/DataTableViewOptions';

export interface FilterFasetKolom {
  columnId: string;
  title: string;
  options: Array<{ label: string; value: string }>;
}

interface DataTableToolbarProps<TData> {
  table: Table<TData>;
  pencarianPlaceholder?: string;
  facetedFilters?: FilterFasetKolom[];
  aksi?: ReactNode;
}

export function DataTableToolbar<TData>({
  table,
  pencarianPlaceholder,
  facetedFilters,
  aksi,
}: DataTableToolbarProps<TData>) {
  const adaFilterAktif = table.getState().columnFilters.length > 0 || !!table.getState().globalFilter;

  // Di layar sempit dua kelompok ini bertumpuk dan boleh membungkus. Tanpa itu
  // kelompok kanan -- berisi aksi utama halaman seperti "Tambah" -- tidak dapat
  // menyusut dan terdorong keluar layar, sehingga tidak terjangkau sama sekali
  // di ponsel dan ikut melebarkan seluruh kartu tabel.
  return (
    <div className="flex flex-col gap-2 border-b border-border p-3 sm:flex-row sm:items-center sm:justify-between sm:px-4">
      <div className="flex min-w-0 flex-1 flex-wrap items-center gap-2">
        <div className="relative w-full sm:w-64">
          <Search
            aria-hidden="true"
            className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
          />
          <Input
            placeholder={pencarianPlaceholder ?? 'Cari...'}
            value={(table.getState().globalFilter as string) ?? ''}
            onChange={(e) => table.setGlobalFilter(e.target.value)}
            className="h-10 w-full pl-8 sm:h-8"
          />
        </div>
        {facetedFilters?.map((filter) => (
          <DataTableFacetedFilter
            key={filter.columnId}
            column={table.getColumn(filter.columnId)}
            title={filter.title}
            options={filter.options}
          />
        ))}
        {adaFilterAktif && (
          <Button
            variant="ghost"
            size="sm"
            className="h-8 px-2"
            onClick={() => {
              table.resetColumnFilters();
              table.setGlobalFilter('');
            }}
          >
            Atur Ulang <X className="ml-1 size-3.5" />
          </Button>
        )}
      </div>
      <div className="flex flex-wrap items-center gap-2">
        {aksi}
        <DataTableViewOptions table={table} />
      </div>
    </div>
  );
}
