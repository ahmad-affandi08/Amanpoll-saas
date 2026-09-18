import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types/global';

/**
 * Visibility helper saja: backend tetap satu-satunya sumber otorisasi.
 */
export function useIzin() {
  const { izin } = usePage<PageProps>().props;

  return {
    boleh: (kode: string) => izin.includes(kode),
  };
}
