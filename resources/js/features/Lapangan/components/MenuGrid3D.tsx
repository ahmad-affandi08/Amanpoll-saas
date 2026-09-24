import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Ikon3D, kelasTint, type NamaIkon3D, type TintIkon } from '@/components/shared/Ikon3D';

export interface ItemMenu3D {
  label: string;
  ikon: NamaIkon3D;
  /** Tint wadah. Bawaan `biru`. */
  tint?: TintIkon;
  href?: string;
  onClick?: () => void;
  /** Lencana jumlah merah di pojok wadah; 0 atau kosong tidak ditampilkan. */
  jumlah?: number;
}

interface PropsMenuGrid3D {
  item: ItemMenu3D[];
  /** Bawaan 4 kolom. */
  kolom?: 3 | 4;
  /** `ringkas` (wadah 54px) di dalam kartu apung yang juga memuat jadwal; bawaan 60px. */
  ukuran?: 'normal' | 'ringkas';
  /** Nama navigasi untuk pembaca layar. */
  label?: string;
  className?: string;
}

/** Grid menu ikon 3D (DESIGN.md 36.2 kartu apung): wadah tint 60px, label 12.5px di bawahnya. */
export function MenuGrid3D({
  item,
  kolom = 4,
  ukuran = 'normal',
  label = 'Menu',
  className,
}: PropsMenuGrid3D) {
  const ringkas = ukuran === 'ringkas';

  return (
    <nav aria-label={label} className={className}>
      <ul
        className={cn(
          'grid gap-x-1.5 gap-y-3.5',
          kolom === 4 ? 'grid-cols-4' : 'grid-cols-3',
          ringkas ? 'px-2.5 py-3' : 'px-2.5 pt-[18px] pb-4',
        )}
      >
        {item.map((satu) => {
          const isi = (
            <>
              <span
                className={cn(
                  'relative flex items-center justify-center rounded-[18px]',
                  ringkas ? 'size-[54px]' : 'size-[60px]',
                  kelasTint(satu.tint ?? 'biru'),
                )}
              >
                <Ikon3D nama={satu.ikon} ukuran={ringkas ? 36 : 40} />
                {satu.jumlah ? (
                  <span className="absolute -top-1 -right-1.5 flex h-5 min-w-5 items-center justify-center rounded-[10px] border-2 border-white bg-lapangan-merah-700 px-[5px] text-xs leading-none font-bold text-white">
                    {satu.jumlah}
                    <span className="sr-only"> baru</span>
                  </span>
                ) : null}
              </span>
              <span className="text-[12.5px] leading-tight font-semibold text-lapangan-teks">
                {satu.label}
              </span>
            </>
          );
          const kelas =
            'flex w-full flex-col items-center gap-2 rounded-2xl py-1 text-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500';

          return (
            <li key={satu.label}>
              {satu.href ? (
                <Link href={satu.href} className={kelas}>
                  {isi}
                </Link>
              ) : (
                <button type="button" onClick={satu.onClick} className={kelas}>
                  {isi}
                </button>
              )}
            </li>
          );
        })}
      </ul>
    </nav>
  );
}
