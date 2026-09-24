import { Mic } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface HasilPengenalan {
  results: ArrayLike<ArrayLike<{ transcript: string }> & { isFinal: boolean }>;
  resultIndex: number;
}

interface PengenalSuara {
  lang: string;
  continuous: boolean;
  interimResults: boolean;
  start(): void;
  stop(): void;
  onresult: ((hasil: HasilPengenalan) => void) | null;
  onerror: ((galat: { error: string }) => void) | null;
  onend: (() => void) | null;
}

type KonstruktorPengenal = new () => PengenalSuara;

/** Web Speech API bawaan peramban (Chrome Android: `webkitSpeechRecognition`); tanpa pustaka. */
function ambilPengenal(): KonstruktorPengenal | null {
  if (typeof window === 'undefined') return null;
  const kandidat: unknown =
    (window as unknown as Record<string, unknown>).SpeechRecognition ??
    (window as unknown as Record<string, unknown>).webkitSpeechRecognition;
  return typeof kandidat === 'function' ? (kandidat as KonstruktorPengenal) : null;
}

interface PropsBarisDikte {
  /** Teks hasil dikte ditambahkan ke isian yang terakhir disentuh. */
  onTeks: (teks: string) => void;
  /** Nama isian tujuan untuk dibacakan, mis. "Tindakan". */
  tujuan: string;
}

/**
 * Tombol dikte (papan layar 08): "Mendengarkan… Ketuk untuk berhenti mendikte".
 * Tidak dirender bila peramban tidak punya pengenal suara.
 */
export function BarisDikte({ onTeks, tujuan }: PropsBarisDikte) {
  const [Pengenal] = useState(ambilPengenal);
  const [mendengar, setMendengar] = useState(false);
  const pengenal = useRef<PengenalSuara | null>(null);
  const onTeksRef = useRef(onTeks);
  onTeksRef.current = onTeks;

  useEffect(() => () => pengenal.current?.stop(), []);

  if (!Pengenal) return null;

  const alihkan = () => {
    if (mendengar) {
      pengenal.current?.stop();
      return;
    }
    const baru = new Pengenal();
    baru.lang = 'id-ID';
    baru.continuous = true;
    baru.interimResults = false;
    baru.onresult = (hasil) => {
      for (let i = hasil.resultIndex; i < hasil.results.length; i++) {
        const satu = hasil.results[i];
        if (satu.isFinal && satu[0]) onTeksRef.current(satu[0].transcript.trim());
      }
    };
    baru.onerror = (galat) => {
      if (galat.error === 'not-allowed') toast.error('Izin mikrofon ditolak. Ketik saja keterangannya.');
      else if (galat.error === 'network') toast.error('Dikte butuh sinyal. Ketik saja keterangannya.');
    };
    baru.onend = () => setMendengar(false);
    pengenal.current = baru;
    baru.start();
    setMendengar(true);
  };

  return (
    <div className="flex items-center gap-3 rounded-2xl bg-lapangan-oranye-50 py-2.5 pr-2.5 pl-3.5">
      <span aria-hidden className="flex h-7 items-center gap-[3px]">
        {[10, 18, 26, 14, 22, 12, 20].map((tinggi, i) => (
          <i
            key={i}
            className={cn('w-1 rounded-sm bg-lapangan-oranye-600', mendengar && 'animate-pulse')}
            style={{ height: mendengar ? tinggi : 6, animationDelay: `${i * 90}ms` }}
          />
        ))}
      </span>
      <p
        aria-live="polite"
        className="min-w-0 flex-1 text-[13px] leading-tight font-bold text-lapangan-oranye-teks"
      >
        {mendengar ? 'Mendengarkan…' : `Dikte ${tujuan.toLowerCase()}`}
        <small className="block font-semibold text-lapangan-teks-2">
          {mendengar ? 'Ketuk untuk berhenti mendikte' : 'Ketuk mikrofon lalu bicara'}
        </small>
      </p>
      <button
        type="button"
        onClick={alihkan}
        aria-pressed={mendengar}
        aria-label={mendengar ? 'Berhenti mendikte' : `Mulai mendikte ${tujuan.toLowerCase()}`}
        className="gradien-fab-lapangan flex size-12 shrink-0 items-center justify-center rounded-full text-white shadow-lapangan-oranye focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500"
      >
        <Mic aria-hidden className="size-[22px]" />
      </button>
    </div>
  );
}
