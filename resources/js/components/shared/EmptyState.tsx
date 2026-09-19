import type { ReactNode } from 'react';

interface Props {
  ilustrasi?: string;
  judul: string;
  deskripsi?: string;
  aksi?: ReactNode;
}

/* EmptyState umum (DESIGN.md 13.6): jelaskan apa yang kosong + aksi berikutnya. */
export function EmptyState({ ilustrasi, judul, deskripsi, aksi }: Props) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center">
      {ilustrasi && <img src={ilustrasi} alt="" className="size-16 shrink-0" />}
      <div className="space-y-1">
        <p className="text-sm font-medium text-foreground">{judul}</p>
        {deskripsi && <p className="text-sm text-muted-foreground">{deskripsi}</p>}
      </div>
      {aksi}
    </div>
  );
}
