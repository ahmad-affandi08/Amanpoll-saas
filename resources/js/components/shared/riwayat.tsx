import type { ReactNode } from 'react';

/** Bagian tampilan yang dipakai bersama tab Pemeliharaan dan tab Kalibrasi. */

export function KartuAngka({ label, nilai, catatan }: { label: string; nilai: ReactNode; catatan?: string }) {
  return (
    <div className="rounded-md border border-border bg-card px-4 py-3">
      <p className="text-xs text-muted-foreground">{label}</p>
      <p className="mt-1 text-lg font-semibold tabular-nums text-foreground">{nilai}</p>
      {catatan && <p className="mt-0.5 text-xs text-muted-foreground">{catatan}</p>}
    </div>
  );
}

/** Judul bagian beserta keterangan bila barisnya dipotong. */
export function KepalaBagian({
  judul,
  ditampilkan,
  total,
}: {
  judul: string;
  ditampilkan: number;
  total: number;
}) {
  return (
    <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-2">
      <h3 className="text-sm font-semibold text-foreground">{judul}</h3>
      <span className="text-xs text-muted-foreground">
        {ditampilkan < total ? `Menampilkan ${ditampilkan} terbaru dari ${total}` : `${total} entri`}
      </span>
    </div>
  );
}

export function BarisKosong({ teks }: { teks: string }) {
  return <p className="py-3 text-sm text-muted-foreground">{teks}</p>;
}

/** Tanggal saja; jam tidak menambah apa pun pada daftar riwayat. */
export function tanggal(nilai: string | null): string {
  if (!nilai) {
    return '—';
  }

  return new Date(nilai).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

export function durasi(menit: number | null): string {
  if (menit === null || menit === 0) {
    return '—';
  }

  const jam = Math.floor(menit / 60);
  const sisa = menit % 60;

  if (jam === 0) {
    return `${sisa} menit`;
  }

  return sisa === 0 ? `${jam} jam` : `${jam} jam ${sisa} menit`;
}
