import { ReactNode, useMemo, useState } from 'react';
import {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  Updater,
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
import { DaftarServer, useDaftarServer } from '@/components/data-table/daftar-server';
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
  /**
   * Mengalihkan paginasi, pengurutan, dan penyaringan ke server.
   *
   * Tanpa prop ini tabel bekerja seperti semula: seluruh baris dikirim sekaligus
   * lalu diolah di browser. Daftar yang dapat melampaui BATAS_DAFTAR memakai
   * prop ini supaya barisnya tidak terpotong diam-diam.
   */
  server?: DaftarServer;
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
  server,
}: DataTableProps<TData, TValue>) {
  return server ? (
    <TabelServer
      columns={columns}
      data={data}
      pencarianPlaceholder={pencarianPlaceholder}
      facetedFilters={facetedFilters}
      aksi={aksi}
      pesanKosong={pesanKosong}
      ilustrasiKosong={ilustrasiKosong}
      kartuDiPonsel={kartuDiPonsel}
      server={server}
    />
  ) : (
    <TabelKlien
      columns={columns}
      data={data}
      pencarianPlaceholder={pencarianPlaceholder}
      facetedFilters={facetedFilters}
      aksi={aksi}
      pesanKosong={pesanKosong}
      ilustrasiKosong={ilustrasiKosong}
      kartuDiPonsel={kartuDiPonsel}
    />
  );
}

function TabelKlien<TData, TValue>({
  columns,
  data,
  pencarianPlaceholder,
  facetedFilters,
  aksi,
  pesanKosong,
  ilustrasiKosong,
  kartuDiPonsel,
}: Omit<DataTableProps<TData, TValue>, 'server'>) {
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
    <Kerangka
      table={table}
      columns={columns}
      pencarianPlaceholder={pencarianPlaceholder}
      facetedFilters={facetedFilters}
      aksi={aksi}
      pesanKosong={pesanKosong}
      ilustrasiKosong={ilustrasiKosong}
      kartuDiPonsel={kartuDiPonsel}
      catatan={
        mungkinTerpotong
          ? `Daftar dibatasi ${BATAS_DAFTAR.toLocaleString('id-ID')} baris teratas. Pakai pencarian atau penyaring untuk mempersempit bila yang dicari belum tampak.`
          : undefined
      }
    />
  );
}

function TabelServer<TData, TValue>({
  columns,
  data,
  pencarianPlaceholder,
  facetedFilters,
  aksi,
  pesanKosong,
  ilustrasiKosong,
  kartuDiPonsel,
  server,
}: DataTableProps<TData, TValue> & { server: DaftarServer }) {
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});
  const daftar = useDaftarServer(server);

  // Faset server boleh menyaring kolom yang tidak ditampilkan; tanpa kolom bayangan ini
  // DataTableFacetedFilter memanggil setFilterValue pada undefined dan penyaringnya diam saja.
  const idBayangan = (facetedFilters ?? [])
    .filter((filter) => !columns.some((kolom) => kolom.id === filter.columnId))
    .map((filter) => filter.columnId);

  const kunciBayangan = idBayangan.join(',');

  const kolomLengkap = useMemo(
    () => [...columns, ...(kunciBayangan === '' ? [] : kunciBayangan.split(',')).map((id) => ({ id }))],
    [columns, kunciBayangan],
  );

  const sorting: SortingState = server.filter.urut
    ? [{ id: server.filter.urut, desc: server.filter.arah === 'desc' }]
    : [];

  const columnFilters: ColumnFiltersState = (facetedFilters ?? [])
    .filter((filter) => (server.filter[filter.columnId] ?? '') !== '')
    .map((filter) => ({ id: filter.columnId, value: server.filter[filter.columnId]?.split(',') }));

  const table = useReactTable({
    data,
    columns: kolomLengkap,
    pageCount: server.meta.last_page,
    state: {
      sorting,
      columnFilters,
      columnVisibility: {
        ...Object.fromEntries(idBayangan.map((id) => [id, false])),
        ...columnVisibility,
      },
      globalFilter: daftar.cari,
      pagination: { pageIndex: server.meta.current_page - 1, pageSize: server.meta.per_page },
    },
    // Baris yang ada di tangan hanya satu halaman, jadi seluruh pengolahan ada di server.
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    onColumnVisibilityChange: setColumnVisibility,
    onGlobalFilterChange: (pembaru: Updater<string>) =>
      daftar.setCari(typeof pembaru === 'function' ? pembaru(daftar.cari) : pembaru),
    onSortingChange: (pembaru: Updater<SortingState>) => {
      const berikut = typeof pembaru === 'function' ? pembaru(sorting) : pembaru;
      const kolom = berikut[0];
      daftar.ubahUrutan(kolom?.id, kolom === undefined ? undefined : kolom.desc ? 'desc' : 'asc');
    },
    onColumnFiltersChange: (pembaru: Updater<ColumnFiltersState>) => {
      const berikut = typeof pembaru === 'function' ? pembaru(columnFilters) : pembaru;
      const sesudah = new Map(berikut.map((satu) => [satu.id, satu.value as string[] | undefined]));
      const perubahan: Record<string, string | undefined> = {};

      // Seluruh faset diperiksa: "Atur Ulang" melepas beberapa sekaligus dalam satu panggilan.
      for (const filter of facetedFilters ?? []) {
        const teks = (sesudah.get(filter.columnId) ?? []).join(',');

        if (teks !== (server.filter[filter.columnId] ?? '')) {
          perubahan[filter.columnId] = teks === '' ? undefined : teks;
        }
      }

      if (Object.keys(perubahan).length > 0) {
        daftar.ubahFilter(perubahan);
      }
    },
    onPaginationChange: (pembaru) => {
      const kini = { pageIndex: server.meta.current_page - 1, pageSize: server.meta.per_page };
      const berikut = typeof pembaru === 'function' ? pembaru(kini) : pembaru;

      if (berikut.pageIndex !== kini.pageIndex) {
        daftar.ubahHalaman(berikut.pageIndex + 1);
      }
    },
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <Kerangka
      table={table}
      columns={columns}
      pencarianPlaceholder={pencarianPlaceholder}
      facetedFilters={facetedFilters}
      aksi={aksi}
      pesanKosong={pesanKosong}
      ilustrasiKosong={ilustrasiKosong}
      kartuDiPonsel={kartuDiPonsel}
      totalBaris={server.meta.total}
    />
  );
}

interface KerangkaProps<TData, TValue> {
  table: ReturnType<typeof useReactTable<TData>>;
  columns: ColumnDef<TData, TValue>[];
  pencarianPlaceholder?: string;
  facetedFilters?: FilterFasetKolom[];
  aksi?: ReactNode;
  pesanKosong?: string;
  ilustrasiKosong?: string;
  kartuDiPonsel?: boolean;
  catatan?: string;
  totalBaris?: number;
}

function Kerangka<TData, TValue>({
  table,
  columns,
  pencarianPlaceholder,
  facetedFilters,
  aksi,
  pesanKosong = 'Tidak ada data.',
  ilustrasiKosong,
  kartuDiPonsel = false,
  catatan,
  totalBaris,
}: KerangkaProps<TData, TValue>) {
  return (
    <div className="rounded-[9px] border border-border bg-card">
      {catatan && (
        <p className="border-b border-border bg-muted/40 px-4 py-2 text-xs text-muted-foreground">
          {catatan}
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
      <DataTablePagination table={table} totalBaris={totalBaris} />
    </div>
  );
}
