import { type Cell, type Row, type Table as TabelTanstack, flexRender } from '@tanstack/react-table';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';

/** Tampilan kartu untuk DataTable di layar sempit (DESIGN.md 9.3). */
export function DataTableCard<TData>({
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
    return <KeadaanKosong ilustrasi={ilustrasiKosong} judul={pesanKosong} />;
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

  const adaJudulTersurat = sel.some((c) => c.column.columnDef.meta?.kartu === 'judul');
  const peran = (cell: Cell<TData, unknown>) =>
    peranKolom(cell.column.id, cell.column.columnDef.meta?.kartu, adaJudulTersurat);

  const judul = sel.find((c) => peran(c) === 'judul');
  const aksi = sel.filter((c) => peran(c) === 'aksi');
  const rincian = sel.filter((c) => {
    const p = peran(c);

    return p !== 'judul' && p !== 'aksi' && p !== 'sembunyi';
  });

  return (
    <div className="space-y-2">
      {judul && (
        <div className="text-sm font-medium break-words text-foreground">
          {flexRender(judul.column.columnDef.cell, judul.getContext())}
        </div>
      )}

      {rincian.length > 0 && (
        // Kolom nilai minmax(0,1fr): tanpa batas bawah nol, kode atau nomor
        // panjang tanpa spasi mendorong kartu lebih lebar dari layarnya.
        <dl className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1.5 text-sm">
          {rincian.map((cell) => (
            <div key={cell.id} className="contents">
              <dt className="max-w-32 truncate text-xs leading-5 text-muted-foreground">
                {labelKolom(cell.column.columnDef.meta, cell.column.id)}
              </dt>
              <dd className="min-w-0 break-words text-foreground">
                {flexRender(cell.column.columnDef.cell, cell.getContext())}
              </dd>
            </div>
          ))}
        </dl>
      )}

      {aksi.length > 0 && (
        // Tombol aksi di tabel dirancang untuk tetikus (28px). Di kartu ia
        // disentuh jari, jadi diberi ukuran minimum 36px tanpa mengubah
        // komponen halaman yang merendernya.
        <div className="flex flex-wrap items-center gap-2 pt-1 [&_button]:min-h-10 [&_button]:min-w-10">
          {aksi.map((cell) => (
            <div key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</div>
          ))}
        </div>
      )}
    </div>
  );
}

type PeranKartu = 'judul' | 'rincian' | 'aksi' | 'sembunyi';

/**
 * Peran kolom di kartu.
 *
 * `meta.kartu` yang ditulis halaman selalu menang. Tanpanya dipakai pola yang
 * memang dianut halaman-halaman di repo ini: kolom `aksi` berisi tombol, dan
 * kolom `Nama` adalah judul yang wajar untuk daftar master data. Dengan begitu
 * halaman yang belum pernah menyetel apa pun tetap mendapat kartu yang terbaca
 * di ponsel, bukan tabel yang harus digeser ke samping.
 */
function peranKolom(id: string, tersurat: PeranKartu | undefined, adaJudulTersurat: boolean): PeranKartu {
  if (tersurat) return tersurat;
  if (id === 'aksi') return 'aksi';
  if (id === 'Nama' && !adaJudulTersurat) return 'judul';

  return 'rincian';
}

/** Header kolom sering berupa komponen pengurut, bukan teks. */
function labelKolom(meta: { label?: string; labelKartu?: string } | undefined, id: string): string {
  const label = meta?.labelKartu ?? meta?.label;
  if (label) return label;

  const kata = id.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/[_-]/g, ' ');

  return kata.charAt(0).toUpperCase() + kata.slice(1);
}
