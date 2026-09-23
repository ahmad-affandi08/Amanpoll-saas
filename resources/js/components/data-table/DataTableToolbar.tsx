import { ReactNode } from 'react';
import { Table } from '@tanstack/react-table';
import { X } from 'lucide-react';
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
    <div className="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
      <div className="flex min-w-0 flex-1 flex-wrap items-center gap-2">
        <Input
          placeholder={pencarianPlaceholder ?? 'Cari...'}
          value={(table.getState().globalFilter as string) ?? ''}
          onChange={(e) => table.setGlobalFilter(e.target.value)}
          className="h-10 w-full sm:h-8 sm:w-56"
        />
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
