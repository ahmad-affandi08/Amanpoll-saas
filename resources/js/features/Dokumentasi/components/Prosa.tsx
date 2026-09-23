import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { Info, TriangleAlert } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Satu-satunya tempat gaya teks dokumentasi ditetapkan.
 *
 * Halaman isi memakai komponen di sini, bukan kelas Tailwind langsung, supaya
 * jarak dan ukurannya seragam di dua puluh halaman tanpa perlu diingat ulang.
 */

/** Judul bagian; id-nya dipakai daftar isi di kanan, jadi harus stabil. */
export function Bagian({ id, judul, children }: { id: string; judul: string; children: ReactNode }) {
  return (
    // Jarak antarbagian dipasang di <section>: pada <h2>, `first:mt-0` selalu berlaku karena
    // judul adalah anak pertama section-nya, sehingga bagian saling menempel.
    <section aria-labelledby={id} className="mt-12 scroll-mt-24 first:mt-0">
      <h2
        id={id}
        className="scroll-mt-24 border-b border-border pb-2 text-xl font-semibold tracking-tight text-foreground"
      >
        {judul}
      </h2>
      <div className="mt-4 space-y-4">{children}</div>
    </section>
  );
}

export function SubJudul({ children }: { children: ReactNode }) {
  return <h3 className="mt-8 text-base font-semibold text-foreground">{children}</h3>;
}

export function P({ children }: { children: ReactNode }) {
  return <p className="text-sm leading-7 text-grafit-700">{children}</p>;
}

export function Daftar({ children, urut = false }: { children: ReactNode; urut?: boolean }) {
  const kelas = 'ml-5 space-y-2 text-sm leading-7 text-grafit-700';

  return urut ? (
    <ol className={cn(kelas, 'list-decimal')}>{children}</ol>
  ) : (
    <ul className={cn(kelas, 'list-disc')}>{children}</ul>
  );
}

export function Butir({ children }: { children: ReactNode }) {
  return <li className="pl-1">{children}</li>;
}

export function Tegas({ children }: { children: ReactNode }) {
  return <strong className="font-semibold text-foreground">{children}</strong>;
}

/** Nama menu, tombol, atau kolom persis seperti yang tampil di layar. */
export function Ui({ children }: { children: ReactNode }) {
  return (
    <span className="rounded-[4px] border border-border bg-card px-1.5 py-0.5 text-[0.8125rem] font-medium text-foreground">
      {children}
    </span>
  );
}

/** Nilai harfiah: kode izin, nama kolom, contoh kode. */
export function Kode({ children }: { children: ReactNode }) {
  return (
    <code className="rounded-[4px] border border-border bg-card px-1.5 py-0.5 font-mono text-[0.8125rem] text-foreground">
      {children}
    </code>
  );
}

export function Blok({ children }: { children: string }) {
  return (
    <pre className="overflow-x-auto rounded-[9px] border border-border bg-permukaan-50 p-4 text-[0.8125rem] leading-6 text-foreground">
      <code className="font-mono">{children}</code>
    </pre>
  );
}

export function TautanDoc({ ke, children }: { ke: string; children: ReactNode }) {
  return (
    <Link
      href={ke}
      className="font-medium text-primary underline underline-offset-4 hover:text-primary-hover"
    >
      {children}
    </Link>
  );
}

/** Jalur menu, mis. Sistem & Konfigurasi -> Struktur & Platform -> Lokasi. */
export function Jalur({ ruas }: { ruas: string[] }) {
  return (
    <span className="inline-flex flex-wrap items-center gap-1 align-middle">
      {ruas.map((satu, ke) => (
        <span key={satu} className="inline-flex items-center gap-1">
          {ke > 0 && <span className="text-muted-foreground">›</span>}
          <Ui>{satu}</Ui>
        </span>
      ))}
    </span>
  );
}

export function Catatan({ children }: { children: ReactNode }) {
  return (
    <div className="flex gap-3 rounded-[9px] border border-info-600/20 bg-info-600/5 p-4">
      <Info aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-info-700" />
      <div className="text-sm leading-7 text-grafit-700">{children}</div>
    </div>
  );
}

export function Awas({ children }: { children: ReactNode }) {
  return (
    <div className="flex gap-3 rounded-[9px] border border-safety-600/25 bg-safety-600/5 p-4">
      <TriangleAlert aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-safety-700" />
      <div className="text-sm leading-7 text-grafit-700">{children}</div>
    </div>
  );
}

export function Tabel({ kepala, baris }: { kepala: string[]; baris: ReactNode[][] }) {
  return (
    <div className="overflow-x-auto rounded-[9px] border border-border bg-card">
      <table className="w-full border-collapse text-sm">
        <thead>
          <tr className="border-b border-border bg-permukaan-50">
            {kepala.map((satu) => (
              <th key={satu} className="px-4 py-2.5 text-left font-semibold text-foreground">
                {satu}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {baris.map((satu, ke) => (
            <tr key={ke} className="border-b border-border last:border-0">
              {satu.map((sel, kolom) => (
                <td key={kolom} className="px-4 py-2.5 align-top leading-6 text-grafit-700">
                  {sel}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

/** Langkah bernomor dengan garis penghubung, untuk alur yang harus urut. */
export function Langkah({ daftar }: { daftar: { judul: string; isi: ReactNode }[] }) {
  return (
    <ol className="space-y-0">
      {daftar.map((satu, ke) => (
        <li key={satu.judul} className="relative flex gap-4 pb-6 last:pb-0">
          {ke < daftar.length - 1 && (
            <span aria-hidden="true" className="absolute left-[13px] top-7 bottom-0 w-px bg-border" />
          )}
          <span className="relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full border border-border bg-card text-xs font-semibold text-foreground">
            {ke + 1}
          </span>
          <div className="min-w-0 flex-1 space-y-2 pt-0.5">
            <p className="text-sm font-semibold text-foreground">{satu.judul}</p>
            <div className="space-y-2 text-sm leading-7 text-grafit-700">{satu.isi}</div>
          </div>
        </li>
      ))}
    </ol>
  );
}
