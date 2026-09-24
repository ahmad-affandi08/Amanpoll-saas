import { useEffect, type ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import { LogoLambang } from '@/components/shared/Logo';
import { Ikon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';
import { ruteAuth } from '@/features/Auth/api';
import {
  DeretanJenisTempat,
  IlustrasiHero,
  LatarHero,
  TeknisiHeroRingkas,
} from '@/features/Auth/components/HeroAutentikasi';
import { TAUTAN_AUTENTIKASI } from '@/features/Auth/components/IsianAutentikasi';
import { isiAutentikasi } from '@/features/Auth/isi';
import { cn } from '@/lib/utils';

interface Props {
  /** Judul tab peramban. */
  judulTab: string;
  /** Ikon 3D di wadah oranye di samping judul kartu, mis. `waving_hand` untuk sapaan Masuk. */
  ikon: NamaIkon3D;
  /** Judul kartu (h1), mis. "Selamat pagi" atau "Lupa kata sandi". */
  judul: string;
  deskripsi?: ReactNode;
  /** Formulir kerap lebih panjang (Daftar trial); kartunya dilebarkan sedikit. */
  lebar?: 'biasa' | 'lebar';
  children: ReactNode;
}

/**
 * Kerangka bersama lima halaman autentikasi (DESIGN.md 37, arah desain 2).
 *
 * Hero gradien biru di atas; kartu formulir putih mengapung menutupi batas hero. Desktop
 * (>= 1024px): judul, paragraf, ilustrasi tiket 3D, dan deretan jenis tempat di kiri, kartu
 * di kanan. HP dan tablet: hero ringkas dengan teknisi 3D, kartu menimpa batas hero sehingga
 * formulir tetap di layar pertama.
 *
 * Memasang `data-tampilan="autentikasi"` pada `<html>` selama terpasang supaya halaman ini
 * (dan portalnya) memakai Plus Jakarta Sans; dasbor tetap IBM Plex Sans.
 */
export function KerangkaAutentikasi({ judulTab, ikon, judul, deskripsi, lebar = 'biasa', children }: Props) {
  useEffect(() => {
    document.documentElement.dataset.tampilan = 'autentikasi';
    return () => {
      delete document.documentElement.dataset.tampilan;
    };
  }, []);

  return (
    <div
      className={cn(
        'relative isolate min-h-screen overflow-x-clip bg-lapangan-latar text-lapangan-teks',
        'lg:[--tinggi-hero:580px] xl:[--tinggi-hero:560px]',
      )}
    >
      <Head title={judulTab} />
      <LatarHero />

      <div
        className={cn(
          'mx-auto flex min-h-screen w-full max-w-[600px] flex-col px-4 sm:px-8',
          // Isi paling lebar 1248px (papan 1440px dengan tepi 96px); max-w termasuk padding samping.
          'lg:grid lg:max-w-[1328px] lg:grid-rows-[auto_1fr_auto] lg:gap-x-10 lg:px-10 xl:max-w-[1376px] xl:gap-x-12 xl:px-16',
          lebar === 'lebar'
            ? 'lg:grid-cols-[minmax(0,1fr)_440px] xl:grid-cols-[minmax(0,1fr)_480px]'
            : 'lg:grid-cols-[minmax(0,1fr)_400px] xl:grid-cols-[minmax(0,1fr)_440px]',
        )}
      >
        {/* Hero kiri. Tingginya tetap: HP/tablet sampai titik kartu mulai, desktop sampai dasar tiket. */}
        <header className="relative h-[238px] sm:h-[304px] lg:col-start-1 lg:row-start-1 lg:h-[calc(var(--tinggi-hero)+113px)]">
          <div className="flex items-center gap-2 pt-5 text-[19px] font-extrabold tracking-[-0.01em] text-white sm:pt-8 lg:gap-2.5 lg:pt-11 lg:text-[22px]">
            <span className="flex size-9 items-center justify-center rounded-[11px] bg-white lg:size-11 lg:rounded-[14px]">
              <LogoLambang className="size-[26px] lg:size-8" alt="" />
            </span>
            Amanpoll
          </div>

          <p className="mt-6 w-[240px] text-[25px] max-[379px]:w-[215px] leading-[1.1] font-extrabold tracking-[-0.03em] text-white sm:mt-8 sm:w-[340px] sm:text-[34px] lg:mt-6 lg:w-auto lg:text-[40px] xl:text-[48px]">
            {isiAutentikasi.judulBaris1} <br className="hidden lg:inline" />
            <span className="text-lapangan-oranye-200">{isiAutentikasi.judulBaris2}</span>
          </p>
          <p className="mt-[18px] hidden max-w-[540px] text-[17px] leading-[1.55] text-lapangan-biru-100 lg:block xl:text-lg">
            {isiAutentikasi.paragraf}
          </p>

          <TeknisiHeroRingkas />
          <IlustrasiHero />
        </header>

        <main className="relative z-10 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:pt-[72px] xl:pt-[84px]">
          <div className="rounded-[24px] bg-white px-5 pt-[22px] pb-[18px] shadow-lapangan-formulir sm:rounded-[26px] sm:px-10 sm:pt-9 sm:pb-8">
            <div className="flex items-start gap-3.5">
              <span className="flex size-12 shrink-0 items-center justify-center rounded-[15px] bg-lapangan-oranye-50 sm:size-14 sm:rounded-[18px]">
                <Ikon3D
                  nama={ikon}
                  ukuran={40}
                  segera
                  className="size-[34px]! drop-shadow-none sm:size-10!"
                />
              </span>
              <div className="min-w-0">
                <h1 className="text-[21px] leading-[1.15] font-extrabold tracking-[-0.02em] text-lapangan-navy-900 sm:text-[26px]">
                  {judul}
                </h1>
                {deskripsi ? (
                  <p className="mt-0.5 text-sm leading-snug text-lapangan-teks-2 sm:text-[15px]">
                    {deskripsi}
                  </p>
                ) : null}
              </div>
            </div>
            <div className="my-4 h-px bg-lapangan-garis-2 sm:my-6" />
            {children}
          </div>
        </main>

        <DeretanJenisTempat className="mt-10 hidden sm:block lg:col-start-1 lg:row-start-2 lg:mt-[27px]" />

        <footer className="mt-auto pt-10 pb-6 text-center text-[13px] text-lapangan-teks-3 lg:col-start-2 lg:row-start-3 lg:pt-6">
          © {new Date().getFullYear()} Amanpoll · {isiAutentikasi.kaki}
        </footer>
      </div>
    </div>
  );
}

/**
 * "Belum punya akun? Coba gratis N hari" di bawah formulir Masuk dan Lupa kata sandi.
 * N dari server (`durasiTrialHari`); tanpa nilai positif, tidak dirender sama sekali.
 */
export function AjakanTrial({ durasiTrialHari }: { durasiTrialHari: number | null | undefined }) {
  if (durasiTrialHari === null || durasiTrialHari === undefined || durasiTrialHari <= 0) {
    return null;
  }

  return (
    <p className="pt-1 text-center text-[15px] text-lapangan-teks-2 sm:pt-1">
      {isiAutentikasi.ajakan.pertanyaan}{' '}
      <Link href={ruteAuth.daftar} className={TAUTAN_AUTENTIKASI}>
        {isiAutentikasi.ajakan.tombol(durasiTrialHari)}
      </Link>
    </p>
  );
}
