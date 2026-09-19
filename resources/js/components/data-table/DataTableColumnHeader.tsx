import { Column } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown, EyeOff } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface DataTableColumnHeaderProps<TData, TValue> extends React.HTMLAttributes<HTMLDivElement> {
  column: Column<TData, TValue>;
  title: string;
}

export function DataTableColumnHeader<TData, TValue>({ column, title, className }: DataTableColumnHeaderProps<TData, TValue>) {
  if (!column.getCanSort() && !column.getCanHide()) {
    return <div className={cn('text-sm font-medium', className)}>{title}</div>;
  }

  const arah = column.getIsSorted();

  return (
    <div className={cn('flex items-center gap-1', className)}>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="sm" className="-ml-2 h-8 gap-1 data-[state=open]:bg-accent">
            <span>{title}</span>
            {arah === 'desc' && <ArrowDown className="size-3.5" />}
            {arah === 'asc' && <ArrowUp className="size-3.5" />}
            {!arah && column.getCanSort() && <ChevronsUpDown className="size-3.5 text-muted-foreground" />}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start">
          {column.getCanSort() && (
            <>
              <DropdownMenuItem onClick={() => column.toggleSorting(false)}>
                <ArrowUp className="mr-2 size-3.5 text-muted-foreground" /> Urutkan Naik
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => column.toggleSorting(true)}>
                <ArrowDown className="mr-2 size-3.5 text-muted-foreground" /> Urutkan Turun
              </DropdownMenuItem>
              {arah && (
                <DropdownMenuItem onClick={() => column.clearSorting()}>
                  <ChevronsUpDown className="mr-2 size-3.5 text-muted-foreground" /> Hapus Urutan
                </DropdownMenuItem>
              )}
            </>
          )}
          {column.getCanSort() && column.getCanHide() && <DropdownMenuSeparator />}
          {column.getCanHide() && (
            <DropdownMenuItem onClick={() => column.toggleVisibility(false)}>
              <EyeOff className="mr-2 size-3.5 text-muted-foreground" /> Sembunyikan Kolom
            </DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
