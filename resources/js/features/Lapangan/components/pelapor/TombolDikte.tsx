import { Mic, MicOff } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

interface HasilUcapan {
  isFinal: boolean;
  0: { transcript: string };
}

interface PeristiwaUcapan {
  resultIndex: number;
  results: ArrayLike<HasilUcapan>;
}

interface PengenalUcapan {
  lang: string;
  interimResults: boolean;
  continuous: boolean;
  onresult: ((peristiwa: PeristiwaUcapan) => void) | null;
  onend: (() => void) | null;
  onerror: (() => void) | null;
  start(): void;
  stop(): void;
}

type KonstruktorPengenal = new () => PengenalUcapan;

/** Pengenal ucapan bawaan peramban (Chrome: `webkitSpeechRecognition`); null bila tidak ada. */
function ambilPengenal(): KonstruktorPengenal | null {
  if (typeof window === 'undefined') return null;
  const kandidat: unknown =
    (window as unknown as Record<string, unknown>).SpeechRecognition ??
    (window as unknown as Record<string, unknown>).webkitSpeechRecognition;
  return typeof kandidat === 'function' ? (kandidat as KonstruktorPengenal) : null;
}

interface PropsTombolDikte {
  /** Dipanggil dengan kalimat yang selesai diucapkan. */
  onTeks: (teks: string) => void;
  className?: string;
}

/**
 * Tombol mikrofon untuk mendikte cerita (papan pelapor layar 06). Memakai pengenal
 * ucapan bawaan peramban tanpa pustaka tambahan; tidak dirender bila perambannya
 * tidak mendukung, jadi pelapor tidak melihat tombol yang tidak berfungsi.
 */
export function TombolDikte({ onTeks, className }: PropsTombolDikte) {
  const [didukung] = useState(() => ambilPengenal() !== null);
  const [mendengar, setMendengar] = useState(false);
  const pengenal = useRef<PengenalUcapan | null>(null);
  const onTeksRef = useRef(onTeks);
  onTeksRef.current = onTeks;

  useEffect(() => () => pengenal.current?.stop(), []);

  if (!didukung) return null;

  const alihkan = () => {
    if (mendengar) {
      pengenal.current?.stop();
      return;
    }

    const Konstruktor = ambilPengenal();
    if (!Konstruktor) return;
    const baru = new Konstruktor();
    baru.lang = 'id-ID';
    baru.interimResults = false;
    baru.continuous = false;
    baru.onresult = (peristiwa) => {
      for (let i = peristiwa.resultIndex; i < peristiwa.results.length; i++) {
        const hasil = peristiwa.results[i];
        if (hasil.isFinal) onTeksRef.current(hasil[0].transcript.trim());
      }
    };
    baru.onend = () => setMendengar(false);
    baru.onerror = () => setMendengar(false);
    pengenal.current = baru;
    setMendengar(true);
    baru.start();
  };

  return (
    <button
      type="button"
      onClick={alihkan}
      aria-pressed={mendengar}
      aria-label={mendengar ? 'Berhenti mendikte' : 'Dikte cerita'}
      className={cn(
        'flex size-11 shrink-0 items-center justify-center rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
        mendengar ? 'bg-lapangan-oranye-700 text-white' : 'bg-lapangan-oranye-50 text-lapangan-oranye-teks',
        className,
      )}
    >
      {mendengar ? <MicOff aria-hidden className="size-5" /> : <Mic aria-hidden className="size-5" />}
    </button>
  );
}
