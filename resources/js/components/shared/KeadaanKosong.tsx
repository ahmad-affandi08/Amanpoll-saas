import type { ReactNode } from 'react';
import { Ilustrasi3d, type NadaIlustrasi } from '@/components/shared/Ilustrasi3d';

interface Props {
  ilustrasi?: string;
  /** Nada latar ubin ilustrasi; bawaannya diturunkan dari nama berkas. */
  nadaIlustrasi?: NadaIlustrasi;
  judul: string;
  deskripsi?: string;
  aksi?: ReactNode;
}

/* KeadaanKosong umum (DESIGN.md 13.6): jelaskan apa yang kosong + aksi berikutnya. */
export function KeadaanKosong({ ilustrasi, nadaIlustrasi, judul, deskripsi, aksi }: Props) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center">
      {ilustrasi && <Ilustrasi3d sumber={ilustrasi} nada={nadaIlustrasi} ukuran={96} className="mr-4 mb-4" />}
      <div className="space-y-1">
        <p className="text-sm font-medium text-foreground">{judul}</p>
        {deskripsi && <p className="text-sm text-muted-foreground">{deskripsi}</p>}
      </div>
      {aksi}
    </div>
  );
}
