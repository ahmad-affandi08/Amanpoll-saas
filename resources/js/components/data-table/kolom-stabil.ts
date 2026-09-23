import { useRef } from 'react';
import type { ColumnDef } from '@tanstack/react-table';

/**
 * Menjaga identitas fungsi `cell`/`header`/`footer` sebuah daftar kolom.
 *
 * `flexRender` memakai fungsi `cell` sebagai TIPE elemen React
 * (`createElement(cell, ctx)`). React membandingkan tipe elemen dengan `===`,
 * jadi fungsi baru berarti tipe baru: seluruh isi sel dibongkar lalu dipasang
 * ulang, dan state lokal komponen di dalamnya hilang.
 *
 * Halaman membangun kolomnya dengan `useMemo([...props])`, sedangkan props
 * Inertia diurai ulang dari JSON pada tiap respons sehingga identitasnya selalu
 * baru. Hasilnya: closure `cell` baru pada tiap respons, dan dialog yang hidup
 * di dalam sel menutup sendiri.
 *
 * Yang paling merugikan bukan dialog yang menutup sesudah tersimpan, melainkan
 * yang menutup sesudah validasi GAGAL: respons galat membawa props baru, sel
 * dipasang ulang, ketikan penggunanya hilang, dan pesan galat yang baru saja
 * dikirim server tidak pernah sempat terlihat. Perilaku ini diamati langsung di
 * peramban, bukan disimpulkan dari kode.
 *
 * Diperbaiki di sini, bukan di tiap halaman: 23 halaman memakai pola yang sama,
 * dan sebagian bergantung pada beberapa prop sekaligus sehingga menstabilkan
 * satu prop saja tidak menolong. Pembungkusnya meneruskan ke closure TERBARU
 * lewat ref, jadi isinya tetap segar -- yang dibekukan hanya identitas
 * fungsinya.
 *
 * Susunan kolom yang benar-benar berubah (kolom ditambah, dibuang, atau
 * diurutkan ulang) tetap diikuti: penanda di bawah menyusun ulang pembungkusnya
 * begitu daftar id kolomnya berubah.
 */
export function useKolomStabil<TData, TValue>(
  columns: ColumnDef<TData, TValue>[],
): ColumnDef<TData, TValue>[] {
  const terbaru = useRef(columns);
  terbaru.current = columns;

  const penanda = columns.map((kolom, i) => kolom.id ?? `#${i}`).join('\u0000');
  const tersimpan = useRef<{ penanda: string; kolom: ColumnDef<TData, TValue>[] } | null>(null);

  if (tersimpan.current === null || tersimpan.current.penanda !== penanda) {
    tersimpan.current = {
      penanda,
      kolom: columns.map((kolom, i) => {
        const dibungkus: Record<string, unknown> = { ...kolom };

        for (const kunci of ['cell', 'header', 'footer'] as const) {
          if (typeof kolom[kunci] !== 'function') {
            continue;
          }

          dibungkus[kunci] = (ctx: unknown) => {
            const terkini = terbaru.current[i]?.[kunci];

            return typeof terkini === 'function' ? (terkini as (ctx: unknown) => unknown)(ctx) : null;
          };
        }

        return dibungkus as unknown as ColumnDef<TData, TValue>;
      }),
    };
  }

  return tersimpan.current.kolom;
}
