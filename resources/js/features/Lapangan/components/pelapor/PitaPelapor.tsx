import { usePage } from '@inertiajs/react';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import type { PropsLapangan } from '@/features/Lapangan/types';

/**
 * Pita kabar layar pelapor: pesan sukses/gagal dari server, dan laporan yang masih
 * menunggu dikirim dari antrean offline. Harus dirender di dalam `KerangkaLapangan`.
 */
export function PitaPelapor() {
  const { flash } = usePage<PropsLapangan>().props;
  const { antrian, daring } = useSinkronisasiOffline();
  const menunggu = antrian.filter(
    (m) => m.Operasi === 'Keluhan.Buat' && (m.Status === 'Menunggu' || m.Status === 'Diproses'),
  ).length;
  const gagal = antrian.filter((m) => m.Operasi === 'Keluhan.Buat' && m.Status === 'Gagal');

  return (
    <>
      {flash?.gagal && <PitaInfo nada="merah" ikon="warning" judul={flash.gagal} />}
      {flash?.sukses && <PitaInfo nada="hijau" ikon="check_mark_button" judul={flash.sukses} />}
      {menunggu > 0 && (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul={`${menunggu} laporan menunggu dikirim`}
          teks={
            daring
              ? 'Sedang dikirim ke tim teknik…'
              : 'Tersimpan di HP ini. Dikirim otomatis saat sinyal kembali.'
          }
        />
      )}
      {gagal.length > 0 && (
        <PitaInfo
          nada="merah"
          ikon="warning"
          judul={`${gagal.length} laporan gagal dikirim`}
          teks={gagal[0].Konflik?.Pesan ?? 'Buka Akun untuk melihat antrean.'}
          tautan={{ label: 'Lihat', href: ruteLapangan.akun }}
        />
      )}
    </>
  );
}
