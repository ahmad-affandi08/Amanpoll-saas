import { Slot } from '@radix-ui/react-slot';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

const KELAS_RAGAM = {
  /** Aksi utama: Oranye-700 dengan teks putih (4,6:1). */
  oranye: 'bg-lapangan-oranye-700 text-white shadow-lapangan-oranye hover:bg-lapangan-oranye-teks',
  navy: 'bg-lapangan-navy-800 text-white hover:bg-lapangan-navy-900',
  garis:
    'bg-white text-lapangan-navy-800 ring-[1.5px] ring-lapangan-garis ring-inset hover:bg-lapangan-latar',
  lembut: 'bg-lapangan-biru-50 text-lapangan-biru-600 hover:bg-lapangan-biru-50/70',
  bahaya: 'bg-lapangan-merah-50 text-lapangan-merah-700 hover:bg-lapangan-merah-50/70',
} as const;

const KELAS_UKURAN = {
  /** 52px: tombol utama layar alur. */
  besar: 'h-[52px] rounded-[14px] px-5 text-base',
  /** 44px: tombol di dalam kartu (target sentuh minimum). */
  kecil: 'h-11 rounded-xl px-4 text-sm',
} as const;

export type RagamTombolLapangan = keyof typeof KELAS_RAGAM;

interface PropsTombolLapangan extends ComponentProps<'button'> {
  /** Bawaan `oranye`. */
  ragam?: RagamTombolLapangan;
  /** Bawaan `besar`. */
  ukuran?: keyof typeof KELAS_UKURAN;
  /** Selebar wadah. */
  penuh?: boolean;
  /** Render sebagai anaknya (mis. `<Link>`), tetap dengan gaya tombol. */
  asChild?: boolean;
}

/** Tombol Mode Lapangan (papan acuan `.tombol`). */
export function TombolLapangan({
  ragam = 'oranye',
  ukuran = 'besar',
  penuh = false,
  asChild = false,
  className,
  type,
  ...props
}: PropsTombolLapangan) {
  const Komponen = asChild ? Slot : 'button';

  return (
    <Komponen
      type={asChild ? undefined : (type ?? 'button')}
      className={cn(
        'inline-flex shrink-0 items-center justify-center gap-2 font-bold transition-colors',
        'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50',
        'disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-5 [&_svg]:shrink-0',
        KELAS_RAGAM[ragam],
        KELAS_UKURAN[ukuran],
        penuh && 'w-full',
        className,
      )}
      {...props}
    />
  );
}
