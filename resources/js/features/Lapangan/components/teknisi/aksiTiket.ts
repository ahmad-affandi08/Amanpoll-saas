import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteLapangan } from '@/features/Lapangan/api';
import type { TiketTeknisi } from '@/features/Lapangan/types';
import { ubahSesiKerja, useKonteksOffline } from '@/features/Lapangan/components/teknisi/sesiKerja';
import {
  antrikanBerurutan,
  keadaanLokal,
  rencanaMulai,
} from '@/features/Lapangan/components/teknisi/statusLokal';

/**
 * Aksi tiket dari beranda, daftar, detail, dan aset ditemukan. Semuanya lewat antrean
 * offline FASE 20: tanpa sinyal tersimpan di HP, dengan sinyal langsung dikirim dan
 * diterapkan server memakai Action dan policy Pemeliharaan.
 */
export function useAksiTiket() {
  const { antrian, antrikan, daring } = useSinkronisasiOffline();
  const konteks = useKonteksOffline();
  const [memproses, setMemproses] = useState<string | null>(null);

  /** Terima (bila belum) lalu mulai, catat awal sesi waktu kerja, buka layar kerja. */
  const mulai = useCallback(
    async (tiket: TiketTeknisi) => {
      setMemproses(tiket.Id);
      try {
        const rencana = rencanaMulai(tiket, keadaanLokal(tiket, antrian));
        await antrikanBerurutan(antrikan, rencana);
        await ubahSesiKerja(konteks, tiket.Id, { MulaiPada: new Date().toISOString() });
        if (rencana.length > 0 && !daring) {
          toast.success('Tersimpan di HP. Terkirim otomatis saat ada sinyal.');
        }
        router.visit(ruteLapangan.teknisi.kerjakan(tiket.Id));
      } finally {
        setMemproses(null);
      }
    },
    [antrian, antrikan, daring, konteks],
  );

  /** Menolak penugasan dengan alasan, supaya koordinator menugaskan teknisi lain. */
  const alihkan = useCallback(
    async (tiket: TiketTeknisi, alasan: string) => {
      setMemproses(tiket.Id);
      try {
        await antrikan({
          Operasi: 'PerintahKerja.ResponsPenugasan',
          EntitasId: tiket.Id,
          VersiKlien: keadaanLokal(tiket, antrian).Versi,
          MuatanData: { Respons: 'Tolak', Catatan: alasan },
          Label: `${tiket.Nomor}: minta dialihkan`,
        });
        toast.success(
          daring
            ? 'Koordinator diberi tahu untuk mengalihkan tiket ini.'
            : 'Permintaan alihkan tersimpan di HP.',
        );
        router.visit(ruteLapangan.teknisi.tugas);
      } finally {
        setMemproses(null);
      }
    },
    [antrian, antrikan, daring],
  );

  return { mulai, alihkan, memproses };
}
