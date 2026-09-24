import { RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState, type PointerEvent } from 'react';

interface PropsKanvasTandaTangan {
  /** Dipanggil setiap goresan selesai dengan PNG tanda tangan, atau `null` setelah diulang. */
  onBerubah: (gambar: Blob | null) => void;
  /** Gambar tersimpan sebelumnya (mis. dari draf) untuk ditampilkan. */
  urlAwal?: string | null;
  label: string;
}

/**
 * Kotak tanda tangan jari (papan layar 11) dengan canvas bawaan peramban. Garis dasar dan
 * tanda "×" meniru kertas; "Ulangi" mengosongkan kotak.
 */
export function KanvasTandaTangan({ onBerubah, urlAwal, label }: PropsKanvasTandaTangan) {
  const kanvas = useRef<HTMLCanvasElement | null>(null);
  const menggambar = useRef(false);
  const [terisi, setTerisi] = useState(Boolean(urlAwal));

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

    if (urlAwal) {
      const gambar = new Image();
      gambar.onload = () => konteks.drawImage(gambar, 0, 0, kotak.width, kotak.height);
      gambar.src = urlAwal;
    }
  }, [urlAwal]);

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

  const selesai = () => {
    if (!menggambar.current) return;
    menggambar.current = false;
    setTerisi(true);
    kanvas.current?.toBlob((gambar) => onBerubah(gambar), 'image/png');
  };

  const ulangi = () => {
    const elemen = kanvas.current;
    const konteks = elemen?.getContext('2d');
    if (!elemen || !konteks) return;
    konteks.clearRect(0, 0, elemen.width, elemen.height);
    setTerisi(false);
    onBerubah(null);
  };

  return (
    <div>
      <button
        type="button"
        onClick={ulangi}
        disabled={!terisi}
        className="absolute top-4 right-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-bold text-lapangan-oranye-teks disabled:opacity-40"
      >
        <RotateCcw aria-hidden className="size-[18px]" />
        Ulangi
      </button>
      <div className="relative mt-3 h-28 rounded-[14px] border-2 border-dashed border-lapangan-teks-3/35 bg-lapangan-latar/60">
        <span aria-hidden className="absolute inset-x-4 bottom-6 border-t-[1.5px] border-lapangan-garis" />
        <span aria-hidden className="absolute bottom-7 left-4 text-[13px] font-bold text-lapangan-teks-3">
          ×
        </span>
        {!terisi && (
          <span className="pointer-events-none absolute inset-x-0 top-8 text-center text-sm font-semibold text-lapangan-teks-3">
            Tanda tangani di sini dengan jari
          </span>
        )}
        <canvas
          ref={kanvas}
          aria-label={label}
          role="img"
          className="absolute inset-0 size-full touch-none text-lapangan-navy-800"
          onPointerDown={mulai}
          onPointerMove={gerak}
          onPointerUp={selesai}
          onPointerCancel={selesai}
          onPointerLeave={selesai}
        />
      </div>
    </div>
  );
}
