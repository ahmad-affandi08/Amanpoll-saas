import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Ikon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';

export type JenisMomen = 'sukses' | 'kosong' | 'galat' | 'tanpa-izin' | 'offline';

const IKON_BAWAAN: Record<JenisMomen, NamaIkon3D> = {
  sukses: 'party_popper',
  kosong: 'clipboard',
  galat: 'warning',
  'tanpa-izin': 'shield',
  offline: 'satellite_antenna',
};

interface IkonPendamping {
  nama: NamaIkon3D;
  /** Letak di sekitar lingkaran. */
  letak: 'kanan-bawah' | 'kiri-atas' | 'kanan-atas';
}

interface PropsIlustrasiMomen {
  /** Menentukan ikon bawaan dan peran ARIA (`galat` → alert, selain itu status). */
  jenis?: JenisMomen;
  /** Ikon utama (±104px). Bawaan menurut `jenis`. */
  ikon?: NamaIkon3D;
  /** Ikon kecil di sekeliling lingkaran, mis. piala dan kilau di layar selesai. */
  pendamping?: IkonPendamping[];
  judul: ReactNode;
  teks?: ReactNode;
  /** Tombol atau tautan di bawah teks. */
  aksi?: ReactNode;
  /** Ringkas: lingkaran 112px dan judul 18px untuk keadaan kosong di dalam daftar. */
  ringkas?: boolean;
  className?: string;
}

const KELAS_LETAK: Record<IkonPendamping['letak'], string> = {
  'kanan-bawah': 'left-[calc(50%+46px)] top-24',
  'kiri-atas': 'left-[calc(50%-96px)] top-2.5',
  'kanan-atas': 'left-[calc(50%+54px)] top-1',
};

const UKURAN_LETAK: Record<IkonPendamping['letak'], number> = {
  'kanan-bawah': 56,
  'kiri-atas': 36,
  'kanan-atas': 40,
};

/**
 * Ilustrasi momen (DESIGN.md 36.3): ikon 3D besar di dalam lingkaran lembut, judul, dan teks.
 * Untuk layar sukses, keadaan kosong, galat, tanpa izin, dan offline.
 */
export function IlustrasiMomen({
  jenis = 'kosong',
  ikon,
  pendamping = [],
  judul,
  teks,
  aksi,
  ringkas = false,
  className,
}: PropsIlustrasiMomen) {
  return (
    <div
      role={jenis === 'galat' ? 'alert' : 'status'}
      className={cn('flex flex-col items-center text-center', ringkas ? 'px-4 py-6' : 'px-6 py-4', className)}
    >
      <div className="relative flex w-full justify-center">
        <div
          className={cn(
            'gradien-ilustrasi-lapangan flex items-center justify-center rounded-full shadow-lapangan-apung',
            ringkas ? 'size-28' : 'size-[150px]',
          )}
        >
          <Ikon3D nama={ikon ?? IKON_BAWAAN[jenis]} ukuran={ringkas ? 72 : 'ilustrasi'} segera />
        </div>
        {!ringkas &&
          pendamping.map((satu) => (
            <span key={satu.letak} className={cn('absolute', KELAS_LETAK[satu.letak])}>
              <Ikon3D nama={satu.nama} ukuran={UKURAN_LETAK[satu.letak]} />
            </span>
          ))}
      </div>
      <h2
        className={cn(
          'font-bold tracking-[-0.01em] text-lapangan-teks',
          ringkas ? 'mt-4 text-lg' : 'mt-6 text-[26px] leading-tight',
        )}
      >
        {judul}
      </h2>
      {teks && (
        <p className={cn('max-w-80 text-lapangan-teks-3', ringkas ? 'mt-1 text-sm' : 'mt-2 text-[15px]')}>
          {teks}
        </p>
      )}
      {aksi && <div className="mt-5 flex w-full flex-col items-center gap-2.5">{aksi}</div>}
    </div>
  );
}
