import { createContext, useContext, type ReactNode } from 'react';

/** Peta field -> wajib diisi, datang dari AturanWajib di server. */
export type AturanWajib = Record<string, boolean>;

const KonteksAturanWajib = createContext<AturanWajib | null>(null);

/**
 * Menyediakan aturan wajib satu formulir bagi seluruh Label di dalamnya.
 *
 * Tanpa ini tiap halaman menuliskan sendiri field mana yang wajib, dan
 * tulisannya perlahan menyimpang dari FormRequest yang sebenarnya menolak
 * kiriman -- itulah sebabnya ada formulir yang menandai field opsional dan
 * ada field wajib yang tidak bertanda sama sekali.
 */
export function AturanWajibProvider({ aturan, children }: { aturan: AturanWajib; children: ReactNode }) {
  return <KonteksAturanWajib.Provider value={aturan}>{children}</KonteksAturanWajib.Provider>;
}

/**
 * Status wajib satu field menurut server.
 *
 * Mengembalikan false bila tidak ada provider atau field-nya tidak dikenal,
 * supaya halaman yang belum dipasangi tetap tampil apa adanya.
 */
export function useWajib(nama: string | undefined): boolean {
  const aturan = useContext(KonteksAturanWajib);

  return nama === undefined ? false : (aturan?.[nama] ?? false);
}
