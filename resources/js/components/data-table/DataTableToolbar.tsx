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

export function DataTableToolbar<TData>({ table, pencarianPlaceholder, facetedFilters, aksi }: DataTableToolbarProps<TData>) {
  const adaFilterAktif = table.getState().columnFilters.length > 0 || !!table.getState().globalFilter;

  return (
    <div className="flex items-center justify-between gap-2 p-4">
      <div className="flex flex-1 flex-wrap items-center gap-2">
        <Input
          placeholder={pencarianPlaceholder ?? 'Cari...'}
          value={(table.getState().globalFilter as string) ?? ''}
          onChange={(e) => table.setGlobalFilter(e.target.value)}
          className="h-8 w-56"
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
            onClick={() => { table.resetColumnFilters(); table.setGlobalFilter(''); }}
          >
            Atur Ulang <X className="ml-1 size-3.5" />
          </Button>
        )}
      </div>
      <div className="flex items-center gap-2">
        {aksi}
        <DataTableViewOptions table={table} />
      </div>
    </div>
  );
}
