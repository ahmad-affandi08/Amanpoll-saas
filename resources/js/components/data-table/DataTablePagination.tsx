import { Table } from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface DataTablePaginationProps<TData> {
  table: Table<TData>;
  /** Jumlah seluruh baris; dikirim halaman berpaginasi server karena tabel hanya memegang satu halaman. */
  totalBaris?: number;
  /**
   * Ukuran halaman ditentukan server, jadi pemilihnya tidak ditampilkan.
   *
   * Paginasi server hanya meneruskan nomor halaman; ukuran yang dipilih di sini
   * tidak pernah sampai ke server dan pilihannya langsung kembali ke semula.
   * Kontrol yang tidak melakukan apa-apa lebih buruk daripada tidak ada.
   */
  ukuranTetap?: boolean;
}

/** Tombol navigasi: 40px di ponsel supaya mudah disentuh, 32px di layar lebar. */
const KELAS_TOMBOL = 'size-10 sm:size-8';

export function DataTablePagination<TData>({
  table,
  totalBaris,
  ukuranTetap = false,
}: DataTablePaginationProps<TData>) {
  const jumlah = totalBaris ?? table.getFilteredRowModel().rows.length;
  const halaman = table.getState().pagination.pageIndex + 1;
  const jumlahHalaman = Math.max(table.getPageCount(), 1);

  return (
    <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-border px-3 py-3 sm:px-4">
      <p className="text-sm text-muted-foreground">
        {jumlah.toLocaleString('id-ID')} baris
        {/* Di ponsel nomor halamannya menumpang di sini, supaya tombol navigasi tetap muat satu baris. */}
        <span className="sm:hidden">
          {' '}
          · Hal. {halaman}/{jumlahHalaman}
        </span>
      </p>
      {/* ml-auto: bila di layar sempit baris ini turun, tombolnya tetap rata kanan di bawah jempol. */}
      <div className="ml-auto flex items-center gap-6">
        {!ukuranTetap && (
          <div className="hidden items-center gap-2 sm:flex">
            <p className="text-sm text-muted-foreground">Baris per halaman</p>
            <Select
              value={`${table.getState().pagination.pageSize}`}
              onValueChange={(v) => table.setPageSize(Number(v))}
            >
              <SelectTrigger className="h-8 w-16">
                <SelectValue />
              </SelectTrigger>
              <SelectContent side="top">
                {[10, 15, 25, 50, 100].map((ukuran) => (
                  <SelectItem key={ukuran} value={`${ukuran}`}>
                    {ukuran}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        )}
        <div className="hidden text-sm text-muted-foreground sm:block">
          Halaman {halaman} dari {jumlahHalaman}
        </div>
        <div className="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon"
            className={KELAS_TOMBOL}
            aria-label="Halaman pertama"
            onClick={() => table.setPageIndex(0)}
            disabled={!table.getCanPreviousPage()}
          >
            <ChevronsLeft className="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            className={KELAS_TOMBOL}
            aria-label="Halaman sebelumnya"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            <ChevronLeft className="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            className={KELAS_TOMBOL}
            aria-label="Halaman berikutnya"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            <ChevronRight className="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            className={KELAS_TOMBOL}
            aria-label="Halaman terakhir"
            onClick={() => table.setPageIndex(table.getPageCount() - 1)}
            disabled={!table.getCanNextPage()}
          >
            <ChevronsRight className="size-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}
