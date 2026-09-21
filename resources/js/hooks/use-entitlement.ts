import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types/global';

/**
 * Pembantu tampilan saja: backend tetap satu-satunya penegak entitlement.
 *
 * Prop yang dibaca di sini dihitung oleh PemeriksaEntitlement — pemeriksa yang
 * sama dengan yang menolak permintaan — sehingga menu yang disembunyikan selalu
 * sama dengan rute yang ditutup, dan tidak ada tombol yang menjanjikan sesuatu
 * yang akan ditolak backend.
 */
export function useEntitlement() {
  const { entitlement } = usePage<PageProps>().props;

  return {
    /** Modul yang tidak termasuk paket dianggap tertutup. */
    bolehFitur: (kode: string) => entitlement?.Fitur?.[kode] === true,
    /** null berarti tanpa batas. */
    batas: (kode: string) => entitlement?.Batas?.[kode] ?? null,
    /** False saat langganan sudah lewat masa tenggang atau dibatalkan. */
    aksesPenuh: entitlement?.AksesPenuh !== false,
  };
}
