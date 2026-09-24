import { Head, usePage } from '@inertiajs/react';
import type { PageProps } from '@/types/global';

/**
 * Beranda Mode Lapangan Pelapor (papan pelapor layar 02).
 *
 * Kerangka minimum dari 39.02 supaya rute `lapangan.pelapor.beranda` sudah dapat dibuka;
 * layar sesungguhnya dibangun di 39.07 di atas KerangkaLapangan.
 */
export default function BerandaPelapor() {
  const { auth } = usePage<PageProps>().props;

  return (
    <>
      <Head title="Beranda" />
      <main className="min-h-screen bg-lapangan-latar px-4 py-6 text-lapangan-teks">
        <p className="text-sm text-lapangan-teks-2">Mode Lapangan Pelapor</p>
        <h1 className="text-xl font-bold">Halo, {auth.pengguna?.Nama}</h1>
      </main>
    </>
  );
}
