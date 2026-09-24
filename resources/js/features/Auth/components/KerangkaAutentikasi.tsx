import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { LogoLambang } from '@/components/shared/Logo';
import { PanelPemasaran, PitaManfaat } from '@/features/Auth/components/PanelPemasaran';

interface Props {
  /** Judul tab peramban. */
  judulTab: string;
  judul: string;
  deskripsi?: ReactNode;
  /** Durasi trial dari server; tanpa nilai, ajakan "Coba gratis" tidak ditampilkan. */
  durasiTrialHari?: number | null;
  /** Formulir kerap lebih panjang (Daftar trial); lebarnya dinaikkan sedikit. */
  lebar?: 'biasa' | 'lebar';
  children: ReactNode;
}

/**
 * Kerangka bersama lima halaman autentikasi (DESIGN.md 37).
 *
 * Desktop (>= 1024px): formulir di kiri, panel pemasaran di kanan yang tetap di
 * tempat saat formulir digulir. HP dan tablet: formulir di layar pertama, pita
 * manfaat ringkas di bawahnya.
 */
export function KerangkaAutentikasi({
  judulTab,
  judul,
  deskripsi,
  durasiTrialHari,
  lebar = 'biasa',
  children,
}: Props) {
  return (
    <div className="flex min-h-screen flex-col bg-card lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <Head title={judulTab} />

      <div className="flex flex-col lg:min-h-screen">
        <header className="px-4 pt-5 sm:px-8 sm:pt-8 lg:px-12">
          <div className="flex items-center gap-2.5">
            <LogoLambang className="size-9" alt="" />
            <span className="text-lg font-semibold tracking-tight text-teknisi-900">Amanpoll</span>
          </div>
        </header>

        <main className="flex flex-1 justify-center px-4 pt-8 pb-10 sm:px-8 sm:pt-12 sm:pb-14 lg:items-center lg:px-12 lg:py-12">
          <div className={lebar === 'lebar' ? 'w-full max-w-[480px]' : 'w-full max-w-[400px]'}>
            <div className="mb-7 space-y-2">
              <h1 className="text-2xl font-bold tracking-tight text-foreground">{judul}</h1>
              {deskripsi ? <p className="text-sm leading-[21px] text-muted-foreground">{deskripsi}</p> : null}
            </div>
            {children}
          </div>
        </main>

        <footer className="hidden px-4 pb-6 text-xs text-muted-foreground sm:px-8 lg:block lg:px-12">
          Amanpoll · Manajemen aset dan pemeliharaan
        </footer>
      </div>

      <PitaManfaat durasiTrialHari={durasiTrialHari} className="flex-1 lg:hidden" />
      <PanelPemasaran durasiTrialHari={durasiTrialHari} className="hidden lg:block" />
    </div>
  );
}
