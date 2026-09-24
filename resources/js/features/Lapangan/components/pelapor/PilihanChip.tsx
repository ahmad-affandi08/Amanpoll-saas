import { Check } from 'lucide-react';
import { useEffect, useRef, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

export interface ItemPilihanChip<K extends string> {
  kunci: K;
  label: string;
  /** Ikon di depan label (mis. ikon 3D kategori); tanpa ikon, yang terpilih diberi centang. */
  ikon?: ReactNode;
}

interface PropsPilihanChip<K extends string> {
  item: ItemPilihanChip<K>[];
  terpilih: K | null;
  onPilih: (kunci: K) => void;
  /** Nama kelompok untuk pembaca layar. */
  label: string;
  /** Satu baris bergulir mendatar (kategori), bukan membungkus. */
  gulir?: boolean;
  className?: string;
}

/** Pilihan chip (papan acuan `.pilihan-chip`): pil putih bergaris, yang terpilih navy. */
export function PilihanChip<K extends string>({
  item,
  terpilih,
  onPilih,
  label,
  gulir = false,
  className,
}: PropsPilihanChip<K>) {
  const wadah = useRef<HTMLDivElement | null>(null);

  // Pilihan awal di barisan bergulir (mis. kategori tebakan dari aset) digulir ke tampilan.
  useEffect(() => {
    if (!gulir) return;
    const pilih = wadah.current?.querySelector<HTMLElement>('[aria-checked="true"]');
    if (pilih && wadah.current) wadah.current.scrollLeft = pilih.offsetLeft - 16;
  }, []);

  return (
    <div
      ref={wadah}
      role="radiogroup"
      aria-label={label}
      className={cn(
        'relative flex gap-2',
        gulir ? '-mx-4 overflow-x-auto px-4 pb-1 [scrollbar-width:none]' : 'flex-wrap',
        className,
      )}
    >
      {item.map((satu) => {
        const pilih = satu.kunci === terpilih;
        return (
          <button
            key={satu.kunci}
            type="button"
            role="radio"
            aria-checked={pilih}
            onClick={() => onPilih(satu.kunci)}
            className={cn(
              'inline-flex h-11 shrink-0 items-center gap-1.5 rounded-xl px-3.5 text-sm font-semibold whitespace-nowrap transition-colors',
              'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50',
              pilih
                ? 'bg-lapangan-navy-800 text-white'
                : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
            )}
          >
            {satu.ikon ?? (pilih && <Check aria-hidden className="size-4" strokeWidth={2.6} />)}
            {satu.label}
          </button>
        );
      })}
    </div>
  );
}
