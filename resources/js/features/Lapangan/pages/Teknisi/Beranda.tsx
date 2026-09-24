import { Head, usePage } from '@inertiajs/react';
import type { PageProps } from '@/types/global';

/**
 * Beranda Mode Lapangan Teknisi (papan teknisi layar 03).
 *
 * Kerangka minimum dari 39.02 supaya rute `lapangan.teknisi.beranda` sudah dapat dibuka;
 * layar sesungguhnya dibangun di 39.04 di atas KerangkaLapangan.
 */
export default function BerandaTeknisi() {
  const { auth } = usePage<PageProps>().props;

  return (
    <>
      <Head title="Beranda" />
      <main className="min-h-screen bg-lapangan-latar px-4 py-6 text-lapangan-teks">
        <p className="text-sm text-lapangan-teks-2">Mode Lapangan Teknisi</p>
        <h1 className="text-xl font-bold">Halo, {auth.pengguna?.Nama}</h1>
      </main>
    </>
  );
}
