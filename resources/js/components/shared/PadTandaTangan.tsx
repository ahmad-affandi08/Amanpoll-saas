import { RotateCcw } from 'lucide-react';
import { useEffect, useImperativeHandle, useRef, useState, type PointerEvent, type Ref } from 'react';

import { cn } from '@/lib/utils';

/** Kendali pad dari luar, mis. untuk mengambil gambar saat formulir dikirim. */
export interface KendaliPadTandaTangan {
  /** PNG tanda tangan, atau `null` bila pad masih kosong. */
  ambilBlob: () => Promise<Blob | null>;
  bersihkan: () => void;
  kosong: () => boolean;
}

interface PropsPadTandaTangan {
  ref?: Ref<KendaliPadTandaTangan>;
  /** Label aksesibel kotak gambar. */
  label: string;
  /** Dipanggil saat pad berubah dari kosong ke terisi atau sebaliknya. */
  onBerubah?: (ada: boolean) => void;
  /** `lapangan` memakai token Mode Lapangan, `dasbor` memakai token dasbor. */
  varian?: 'lapangan' | 'dasbor';
  className?: string;
}

const GAYA = {
  lapangan: {
    kotak: 'rounded-[14px] border-lapangan-teks-3/35 bg-lapangan-latar/60',
    garis: 'border-lapangan-garis',
    petunjuk: 'text-lapangan-teks-3',
    tinta: 'text-lapangan-navy-800',
    ulangi: 'text-lapangan-oranye-teks',
  },
  dasbor: {
    kotak: 'rounded-lg border-border bg-muted/40',
    garis: 'border-border',
    petunjuk: 'text-muted-foreground',
    tinta: 'text-foreground',
    ulangi: 'text-primary',
  },
} as const;

/**
 * Kotak tanda tangan dengan jari atau tetikus (PRD 8.22), memakai canvas bawaan peramban.
 * Garis dasar dan tanda "×" meniru kertas; "Ulangi" mengosongkan kotak. Gambar diambil
 * lewat `ref` saat dibutuhkan, sehingga pad tidak mengunggah apa pun sendiri.
 */
export function PadTandaTangan({ ref, label, onBerubah, varian = 'dasbor', className }: PropsPadTandaTangan) {
  const kanvas = useRef<HTMLCanvasElement | null>(null);
  const menggambar = useRef(false);
  const [terisi, setTerisi] = useState(false);
  const gaya = GAYA[varian];

  useEffect(() => {
    const elemen = kanvas.current;
    if (!elemen) return;
    const rasio = window.devicePixelRatio || 1;
    const kotak = elemen.getBoundingClientRect();
    elemen.width = kotak.width * rasio;
    elemen.height = kotak.height * rasio;
    const konteks = elemen.getContext('2d');
    if (!konteks) return;
    konteks.scale(rasio, rasio);
    konteks.lineWidth = 2.6;
    konteks.lineCap = 'round';
    konteks.lineJoin = 'round';
    konteks.strokeStyle = getComputedStyle(elemen).color;
  }, []);

  const ubahTerisi = (nilai: boolean) => {
    setTerisi(nilai);
    onBerubah?.(nilai);
  };

  const bersihkan = () => {
    const elemen = kanvas.current;
    const konteks = elemen?.getContext('2d');
    if (!elemen || !konteks) return;
    konteks.clearRect(0, 0, elemen.width, elemen.height);
    ubahTerisi(false);
  };

  useImperativeHandle(ref, () => ({
    ambilBlob: () =>
      new Promise<Blob | null>((selesai) => {
        if (!terisi || !kanvas.current) {
          selesai(null);
          return;
        }
        kanvas.current.toBlob((gambar) => selesai(gambar), 'image/png');
      }),
    bersihkan,
    kosong: () => !terisi,
  }));

  const titik = (event: PointerEvent<HTMLCanvasElement>) => {
    const kotak = event.currentTarget.getBoundingClientRect();
    return { x: event.clientX - kotak.left, y: event.clientY - kotak.top };
  };

  const mulai = (event: PointerEvent<HTMLCanvasElement>) => {
    const konteks = kanvas.current?.getContext('2d');
    if (!konteks) return;
    event.currentTarget.setPointerCapture(event.pointerId);
    menggambar.current = true;
    const { x, y } = titik(event);
    konteks.beginPath();
    konteks.moveTo(x, y);
  };

  const gerak = (event: PointerEvent<HTMLCanvasElement>) => {
    if (!menggambar.current) return;
    const konteks = kanvas.current?.getContext('2d');
    if (!konteks) return;
    const { x, y } = titik(event);
    konteks.lineTo(x, y);
    konteks.stroke();
  };

  const selesaiGores = () => {
    if (!menggambar.current) return;
    menggambar.current = false;
    if (!terisi) ubahTerisi(true);
  };

  return (
    <div className={className}>
      <div className={cn('relative h-32 border-2 border-dashed', gaya.kotak)}>
        <span aria-hidden className={cn('absolute inset-x-4 bottom-7 border-t-[1.5px]', gaya.garis)} />
        <span aria-hidden className={cn('absolute bottom-8 left-4 text-[13px] font-bold', gaya.petunjuk)}>
          ×
        </span>
        {!terisi && (
          <span
            className={cn(
              'pointer-events-none absolute inset-x-0 top-9 text-center text-sm font-semibold',
              gaya.petunjuk,
            )}
          >
            Tanda tangani di sini
          </span>
        )}
        <canvas
          ref={kanvas}
          aria-label={label}
          role="img"
          className={cn('absolute inset-0 size-full touch-none', gaya.tinta)}
          onPointerDown={mulai}
          onPointerMove={gerak}
          onPointerUp={selesaiGores}
          onPointerCancel={selesaiGores}
          onPointerLeave={selesaiGores}
        />
      </div>
      <div className="mt-1 flex justify-end">
        <button
          type="button"
          onClick={bersihkan}
          disabled={!terisi}
          className={cn(
            'inline-flex min-h-11 items-center gap-1.5 text-sm font-bold disabled:opacity-40',
            gaya.ulangi,
          )}
        >
          <RotateCcw aria-hidden className="size-4" />
          Ulangi
        </button>
      </div>
    </div>
  );
}
