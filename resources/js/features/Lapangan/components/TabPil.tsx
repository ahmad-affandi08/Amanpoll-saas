import { useRef, type KeyboardEvent, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

export interface ItemTabPil<K extends string> {
  kunci: K;
  label: string;
  /** Jumlah pada lencana oranye; kosong atau 0 tidak ditampilkan. */
  jumlah?: number;
}

interface PropsTabPil<K extends string> {
  item: ItemTabPil<K>[];
  aktif: K;
  onGanti: (kunci: K) => void;
  /** Nama kelompok tab untuk pembaca layar. */
  label: string;
  /** Awalan id supaya tab dan panelnya saling menunjuk (`<awalan>-tab-<kunci>`, `<awalan>-panel-<kunci>`). */
  idAwalan: string;
  className?: string;
}

export function idTabPil(idAwalan: string, kunci: string): string {
  return `${idAwalan}-tab-${kunci}`;
}

export function idPanelTabPil(idAwalan: string, kunci: string): string {
  return `${idAwalan}-panel-${kunci}`;
}

/**
 * Tab pil (DESIGN.md 36.3): segmen dengan jumlah, bukan tab bergaris. Pola ARIA tablist:
 * panah kiri/kanan, Home, End memindah fokus sekaligus memilih tab.
 */
export function TabPil<K extends string>({
  item,
  aktif,
  onGanti,
  label,
  idAwalan,
  className,
}: PropsTabPil<K>) {
  const daftarTombol = useRef<(HTMLButtonElement | null)[]>([]);

  const pindah = (indeks: number) => {
    const tujuan = (indeks + item.length) % item.length;
    onGanti(item[tujuan].kunci);
    daftarTombol.current[tujuan]?.focus();
  };

  const tanganiTombol = (event: KeyboardEvent<HTMLButtonElement>, indeks: number) => {
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      pindah(indeks + 1);
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      pindah(indeks - 1);
    } else if (event.key === 'Home') {
      event.preventDefault();
      pindah(0);
    } else if (event.key === 'End') {
      event.preventDefault();
      pindah(item.length - 1);
    }
  };

  return (
    <div
      role="tablist"
      aria-label={label}
      className={cn('flex rounded-[14px] bg-white p-1 shadow-lapangan-kartu', className)}
    >
      {item.map((satu, indeks) => {
        const terpilih = satu.kunci === aktif;

        return (
          <button
            key={satu.kunci}
            ref={(elemen) => {
              daftarTombol.current[indeks] = elemen;
            }}
            type="button"
            role="tab"
            id={idTabPil(idAwalan, satu.kunci)}
            aria-selected={terpilih}
            aria-controls={idPanelTabPil(idAwalan, satu.kunci)}
            tabIndex={terpilih ? 0 : -1}
            onClick={() => onGanti(satu.kunci)}
            onKeyDown={(event) => tanganiTombol(event, indeks)}
            className={cn(
              'flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-[10px] px-2 text-[13.5px] font-bold transition-colors',
              'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
              terpilih ? 'bg-lapangan-navy-800 text-white' : 'text-lapangan-teks-3 hover:text-lapangan-teks',
            )}
          >
            {satu.label}
            {satu.jumlah ? (
              <b className="rounded-[9px] bg-lapangan-oranye-700 px-1.5 py-px text-xs leading-4 text-white">
                {satu.jumlah}
              </b>
            ) : null}
          </button>
        );
      })}
    </div>
  );
}

interface PropsPanelTabPil {
  idAwalan: string;
  kunci: string;
  children: ReactNode;
  className?: string;
}

/** Panel isi untuk satu tab; render hanya panel yang aktif. */
export function PanelTabPil({ idAwalan, kunci, children, className }: PropsPanelTabPil) {
  return (
    <div
      role="tabpanel"
      id={idPanelTabPil(idAwalan, kunci)}
      aria-labelledby={idTabPil(idAwalan, kunci)}
      tabIndex={0}
      className={cn('flex flex-col gap-3 focus-visible:outline-none', className)}
    >
      {children}
    </div>
  );
}
