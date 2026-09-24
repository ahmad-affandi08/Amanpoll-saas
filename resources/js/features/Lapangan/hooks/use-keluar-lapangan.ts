import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteAuth } from '@/features/Auth/api';

/**
 * Keluar dari Mode Lapangan memakai alur logout yang sama dengan dasbor (PRD 8.17, 8.20):
 * coba kirim antrean dulu, peringatkan bila masih ada perubahan yang belum tersinkron,
 * bersihkan data lokal organisasi ini, lalu POST logout.
 *
 * Harus dipakai di dalam KerangkaLapangan (butuh PenyediaSinkronisasiOffline).
 */
export function useKeluarLapangan(): () => Promise<void> {
  const konfirmasi = useKonfirmasi();
  const { bersihkanDataLokal, dorong, jumlahBelumTersinkron } = useSinkronisasiOffline();

  return useCallback(async () => {
    await dorong();

    if (jumlahBelumTersinkron > 0) {
      const lanjut = await konfirmasi({
        judul: 'Keluar dengan perubahan yang belum tersinkron?',
        deskripsi: `${jumlahBelumTersinkron} perubahan lapangan masih tersimpan di perangkat ini dan belum diterima server. Keluar sekarang akan menghapus data lokal beserta perubahan tersebut.`,
        ragam: 'bahaya',
        labelAksi: 'Tetap keluar',
      });

      if (!lanjut) return;
    }

    await bersihkanDataLokal();
    router.post(ruteAuth.logout);
  }, [bersihkanDataLokal, dorong, jumlahBelumTersinkron, konfirmasi]);
}
