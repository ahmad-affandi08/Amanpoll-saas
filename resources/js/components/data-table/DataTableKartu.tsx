import { type Row, type Table as TabelTanstack, flexRender } from '@tanstack/react-table';
import { EmptyState } from '@/components/shared/EmptyState';

/**
 * Tampilan kartu untuk DataTable di layar sempit (DESIGN.md 9.3).
 *
 * Tabel operasional yang dibaca teknisi di lapangan tidak layak dipaksa
 * digeser mendatar di layar 360px: yang dicari adalah satu pekerjaan, bukan
 * perbandingan antar baris. Kolom pembanding pada tabel administratif tetap
 * lebih terbaca sebagai baris, jadi mode ini dinyalakan per tabel, bukan
 * dipaksakan ke semuanya.
 *
 * Kolom menentukan perannya sendiri lewat `meta.kartu`, sehingga kartu tidak
 * perlu menebak mana judul dan mana aksi.
 */
export function DataTableKartu<TData>({
  table,
  pesanKosong,
  ilustrasiKosong,
}: {
  table: TabelTanstack<TData>;
  pesanKosong: string;
  ilustrasiKosong?: string;
}) {
  const baris = table.getRowModel().rows;

  if (baris.length === 0) {
    return <EmptyState ilustrasi={ilustrasiKosong} judul={pesanKosong} />;
  }

  return (
    <ul className="divide-y divide-border">
      {baris.map((row) => (
        <li key={row.id} className="p-4">
          <KartuBaris row={row} />
        </li>
      ))}
    </ul>
  );
}

function KartuBaris<TData>({ row }: { row: Row<TData> }) {
  const sel = row.getVisibleCells();

  const peran = (id: string) => sel.find((c) => c.column.id === id)?.column.columnDef.meta?.kartu;

  const judul = sel.find((c) => peran(c.column.id) === 'judul');
  const aksi = sel.filter((c) => peran(c.column.id) === 'aksi');
  const rincian = sel.filter((c) => {
    const p = peran(c.column.id);

    return p !== 'judul' && p !== 'aksi' && p !== 'sembunyi';
  });

  return (
    <div className="space-y-2">
      {judul && (
        <div className="text-sm font-medium text-foreground">
          {flexRender(judul.column.columnDef.cell, judul.getContext())}
        </div>
      )}

      {rincian.length > 0 && (
        <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
          {rincian.map((cell) => (
            <div key={cell.id} className="contents">
              <dt className="truncate text-xs text-muted-foreground">
                {labelKolom(cell.column.columnDef.meta?.labelKartu, cell.column.id)}
              </dt>
              <dd className="min-w-0 text-foreground">
                {flexRender(cell.column.columnDef.cell, cell.getContext())}
              </dd>
            </div>
          ))}
        </dl>
      )}

      {aksi.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 pt-1">
          {aksi.map((cell) => (
            <div key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</div>
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * Header kolom sering berupa komponen pengurut, bukan teks, sehingga tidak
 * dapat dipakai sebagai label kartu. Kolom menyediakan `labelKartu`; bila tidak,
 * id kolom dirapikan seadanya.
 */
function labelKolom(label: string | undefined, id: string): string {
  if (label) return label;

  const kata = id.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/[_-]/g, ' ');

  return kata.charAt(0).toUpperCase() + kata.slice(1);
}
