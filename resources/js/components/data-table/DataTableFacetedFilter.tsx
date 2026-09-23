import { Column } from '@tanstack/react-table';
import { CheckIcon, PlusCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Opsi {
  label: string;
  value: string;
}

interface DataTableFacetedFilterProps<TData, TValue> {
  column?: Column<TData, TValue>;
  title: string;
  options: Opsi[];
}

export function DataTableFacetedFilter<TData, TValue>({
  column,
  title,
  options,
}: DataTableFacetedFilterProps<TData, TValue>) {
  const nilaiTerpilih = new Set(column?.getFilterValue() as string[] | undefined);

  const toggle = (value: string) => {
    const baru = new Set(nilaiTerpilih);
    if (baru.has(value)) {
      baru.delete(value);
    } else {
      baru.add(value);
    }
    const daftar = Array.from(baru);
    column?.setFilterValue(daftar.length ? daftar : undefined);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm" className="h-8 border-dashed">
          <PlusCircle className="mr-1 size-3.5" />
          {title}
          {nilaiTerpilih.size > 0 && (
            <>
              <Separator orientation="vertical" className="mx-2 h-4" />
              <Badge variant="secondary" className="rounded-sm px-1 font-normal lg:hidden">
                {nilaiTerpilih.size}
              </Badge>
              <div className="hidden gap-1 lg:flex">
                {nilaiTerpilih.size > 2 ? (
                  <Badge variant="secondary" className="rounded-sm px-1 font-normal">
                    {nilaiTerpilih.size} dipilih
                  </Badge>
                ) : (
                  options
                    .filter((o) => nilaiTerpilih.has(o.value))
                    .map((o) => (
                      <Badge key={o.value} variant="secondary" className="rounded-sm px-1 font-normal">
                        {o.label}
                      </Badge>
                    ))
                )}
              </div>
            </>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-48 p-1">
        {/*
          Opsi adalah item menu, bukan <button> biasa: Radix menahan Tab di dalam
          menu, sehingga tombol biasa tidak pernah bisa dicapai dengan keyboard.
          onSelect dibatalkan agar menu tetap terbuka untuk memilih beberapa opsi.
        */}
        {options.map((opsi) => {
          const dipilih = nilaiTerpilih.has(opsi.value);
          return (
            <DropdownMenuItem
              key={opsi.value}
              role="menuitemcheckbox"
              aria-checked={dipilih}
              onSelect={(event) => {
                event.preventDefault();
                toggle(opsi.value);
              }}
            >
              <span
                aria-hidden="true"
                className={cn(
                  'flex size-4 items-center justify-center rounded-sm border',
                  dipilih ? 'border-primary bg-primary text-primary-foreground' : 'border-grafit-500 bg-card',
                )}
              >
                {dipilih && <CheckIcon className="size-3 text-primary-foreground" />}
              </span>
              {opsi.label}
            </DropdownMenuItem>
          );
        })}
        {nilaiTerpilih.size > 0 && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              className="justify-center"
              onSelect={(event) => {
                event.preventDefault();
                column?.setFilterValue(undefined);
              }}
            >
              Hapus Filter
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
