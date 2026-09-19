import { Column } from '@tanstack/react-table';
import { CheckIcon, PlusCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuTrigger,
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

export function DataTableFacetedFilter<TData, TValue>({ column, title, options }: DataTableFacetedFilterProps<TData, TValue>) {
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
              <Badge variant="secondary" className="rounded-sm px-1 font-normal lg:hidden">{nilaiTerpilih.size}</Badge>
              <div className="hidden gap-1 lg:flex">
                {nilaiTerpilih.size > 2
                  ? <Badge variant="secondary" className="rounded-sm px-1 font-normal">{nilaiTerpilih.size} dipilih</Badge>
                  : options.filter((o) => nilaiTerpilih.has(o.value)).map((o) => (
                      <Badge key={o.value} variant="secondary" className="rounded-sm px-1 font-normal">{o.label}</Badge>
                    ))}
              </div>
            </>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-48 p-1">
        {options.map((opsi) => {
          const dipilih = nilaiTerpilih.has(opsi.value);
          return (
            <button
              key={opsi.value}
              type="button"
              onClick={() => toggle(opsi.value)}
              className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
            >
              <span className={cn(
                'flex size-4 items-center justify-center rounded-sm border border-primary',
                dipilih ? 'bg-primary text-primary-foreground' : 'opacity-50',
              )}>
                {dipilih && <CheckIcon className="size-3" />}
              </span>
              {opsi.label}
            </button>
          );
        })}
        {nilaiTerpilih.size > 0 && (
          <>
            <div className="my-1 h-px bg-border" />
            <button
              type="button"
              onClick={() => column?.setFilterValue(undefined)}
              className="w-full rounded-sm px-2 py-1.5 text-center text-sm hover:bg-accent hover:text-accent-foreground"
            >
              Hapus Filter
            </button>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
