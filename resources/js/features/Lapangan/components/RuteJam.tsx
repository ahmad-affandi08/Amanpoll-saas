import { cn } from '@/lib/utils';
import { Ikon3D, type NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';

export interface TitikRute {
  /** Jam besar, mis. "09.00" atau "±10.30". */
  jam: string;
  /** Label kecil di bawah jam, mis. "Dilaporkan", "Target SLA". */
  label: string;
  /** Jam merah (terlambat). */
  merah?: boolean;
}

interface PropsRuteJam {
  kiri: TitikRute;
  kanan: TitikRute;
  /** Ikon 3D di tengah garis titik. Bawaan `stopwatch`. */
  ikon?: NamaIkon3D;
  /** Label kecil di bawah ikon, mis. "sisa 1j 49m" atau "42 mnt". */
  label?: string;
  /** Label tengah merah. */
  labelMerah?: boolean;
  /** Warna permukaan di belakang rute (menutup garis di belakang ikon). Bawaan `putih`. */
  latar?: 'putih' | 'latar';
  className?: string;
}

/**
 * Rute jam (DESIGN.md 36.3): dua jam besar di kiri-kanan dihubungkan garis titik dengan
 * ikon 3D di tengah. Contoh: "09.00 Dilaporkan ··⏱ sisa 1j 49m·· 11.30 Target SLA".
 */
export function RuteJam({
  kiri,
  kanan,
  ikon = 'stopwatch',
  label,
  labelMerah = false,
  latar = 'putih',
  className,
}: PropsRuteJam) {
  return (
    <div className={cn('grid grid-cols-[auto_1fr_auto] items-center gap-2.5', className)}>
      <TitikJam titik={kiri} />
      <div className="relative flex h-8 items-center justify-center">
        <span
          aria-hidden
          className="absolute inset-x-0 top-1/2 border-t-2 border-dotted border-lapangan-teks-3/40"
        />
        <span
          className={cn(
            'relative flex flex-col items-center px-1.5 text-[12px] leading-tight font-bold',
            latar === 'putih' ? 'bg-white' : 'bg-lapangan-latar',
            labelMerah ? 'text-lapangan-merah-700' : 'text-lapangan-teks-3',
          )}
        >
          <Ikon3D nama={ikon} ukuran={26} />
          {label}
        </span>
      </div>
      <TitikJam titik={kanan} kanan />
    </div>
  );
}

function TitikJam({ titik, kanan = false }: { titik: TitikRute; kanan?: boolean }) {
  return (
    <div className={cn(kanan && 'text-right')}>
      <strong
        className={cn(
          'block text-[22px] leading-[1.1] font-bold tracking-[-0.02em] tabular-nums',
          titik.merah ? 'text-lapangan-merah-700' : 'text-lapangan-teks',
        )}
      >
        {titik.jam}
      </strong>
      <small className="mt-0.5 block text-xs font-semibold text-lapangan-teks-3">{titik.label}</small>
    </div>
  );
}
