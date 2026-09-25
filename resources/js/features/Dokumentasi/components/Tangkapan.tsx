import { type ReactNode, useState } from 'react';
import { Maximize2 } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

/** Kotak penanda dalam persen terhadap lebar dan tinggi gambar. */
interface Kotak {
  x: number;
  y: number;
  w: number;
  h: number;
}

interface DataTangkapan {
  lebar: number;
  tinggi: number;
  penanda: Record<string, Kotak>;
}

/**
 * Posisi penanda ditulis `tools/dokumentasi/tangkap.mjs` bersama gambarnya: skrip mencari tombol
 * yang dimaksud lewat namanya lalu mencatat kotaknya. Menjalankan ulang skrip setelah tampilan
 * berubah memindahkan penanda tanpa menyunting halaman dokumentasi.
 */
const semuaData = import.meta.glob<DataTangkapan>('../tangkapan/**/*.json', {
  eager: true,
  import: 'default',
});

function dataUntuk(gambar: string): DataTangkapan | undefined {
  return semuaData[`../tangkapan/${gambar}.json`];
}

export interface LangkahTangkapan {
  /** Kunci penanda di gambar; langkah tanpa penanda tetap bernomor di daftar. */
  penanda?: string;
  isi: ReactNode;
}

interface Props {
  /** Nama gambar tanpa ekstensi, mis. `perintah-kerja/daftar`. */
  gambar: string;
  /** Uraian isi layar untuk pembaca layar dan saat gambar gagal dimuat. */
  alt: string;
  langkah: LangkahTangkapan[];
}

/**
 * Tangkapan layar berpenanda bernomor. Nomor dan kotak sorot digambar di atas gambar, bukan
 * dicetak ke dalamnya, sehingga tetap tajam di layar mana pun. Menyorot langkah di daftar
 * menyorot penandanya di gambar, dan sebaliknya.
 */
export function Tangkapan({ gambar, alt, langkah }: Props) {
  const data = dataUntuk(gambar);
  const [aktif, setAktif] = useState<string | null>(null);
  const [perbesar, setPerbesar] = useState(false);

  if (!data) {
    // Dijaga DokumentasiTangkapanTest; baris ini hanya terlihat saat menulis panduan baru.
    return (
      <p className="rounded-md border border-dashed border-garis-300 p-4 text-sm text-muted-foreground">
        Tangkapan layar <code>{gambar}</code> belum dibuat. Jalankan{' '}
        <code>tools/dokumentasi/tangkap.mjs</code>.
      </p>
    );
  }

  const nomorPenanda = new Map<string, number>();
  langkah.forEach((satu, ke) => {
    if (satu.penanda && !nomorPenanda.has(satu.penanda)) {
      nomorPenanda.set(satu.penanda, ke + 1);
    }
  });

  const gambarBerpenanda = (besar: boolean) => (
    <div className="relative">
      <img
        src={`/assets/dokumentasi/${gambar}.webp`}
        alt={alt}
        width={data.lebar}
        height={data.tinggi}
        loading={besar ? 'eager' : 'lazy'}
        decoding="async"
        className="block h-auto w-full"
      />
      {[...nomorPenanda.entries()].map(([kunci, nomor]) => {
        const kotak = data.penanda[kunci];
        if (!kotak) {
          return null;
        }
        const redup = aktif !== null && aktif !== kunci;

        return (
          <span
            key={kunci}
            aria-hidden="true"
            className={cn(
              'pointer-events-none absolute rounded-[5px] transition-opacity duration-150',
              'shadow-[0_0_0_2px_#ffffff,0_0_0_4px_var(--color-teknisi-600)]',
              aktif === kunci && 'shadow-[0_0_0_2px_#ffffff,0_0_0_5px_var(--color-teknisi-600)]',
              redup && 'opacity-30',
            )}
            style={{ left: `${kotak.x}%`, top: `${kotak.y}%`, width: `${kotak.w}%`, height: `${kotak.h}%` }}
          >
            <span
              className={cn(
                'absolute top-0 left-0 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full',
                'bg-teknisi-900 font-semibold text-white shadow-[0_0_0_2px_#ffffff]',
                besar ? 'size-7 text-sm' : 'size-[22px] text-xs',
              )}
            >
              {nomor}
            </span>
          </span>
        );
      })}
    </div>
  );

  return (
    <figure className="space-y-3">
      <button
        type="button"
        onClick={() => setPerbesar(true)}
        // Potongan dialog lebih sempit dari kolom; direntangkan ia hanya jadi buram.
        style={{ maxWidth: data.lebar }}
        className="group relative block w-full cursor-zoom-in overflow-hidden rounded-md border border-border bg-permukaan-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        aria-label={`Perbesar tangkapan layar: ${alt}`}
      >
        {gambarBerpenanda(false)}
        <span className="absolute right-2 bottom-2 inline-flex items-center gap-1 rounded-sm border border-border bg-card/95 px-2 py-1 text-xs font-medium text-grafit-700 opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
          <Maximize2 aria-hidden="true" className="size-3.5" /> Perbesar
        </span>
      </button>

      <figcaption>
        <ol className="space-y-1.5">
          {langkah.map((satu, ke) => (
            <li
              key={ke}
              onMouseEnter={() => satu.penanda && setAktif(satu.penanda)}
              onMouseLeave={() => setAktif(null)}
              onFocus={() => satu.penanda && setAktif(satu.penanda)}
              onBlur={() => setAktif(null)}
              tabIndex={satu.penanda ? 0 : undefined}
              className={cn(
                'flex gap-3 rounded-sm px-1.5 py-1 text-sm leading-6 text-grafit-700 outline-none',
                satu.penanda && 'cursor-default hover:bg-accent focus-visible:bg-accent',
              )}
            >
              <span
                className={cn(
                  'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                  satu.penanda ? 'bg-teknisi-900 text-white' : 'border border-border bg-card text-foreground',
                )}
              >
                {ke + 1}
              </span>
              <span className="min-w-0">{satu.isi}</span>
            </li>
          ))}
        </ol>
      </figcaption>

      <Dialog open={perbesar} onOpenChange={setPerbesar}>
        <DialogContent className="max-h-[92vh] overflow-y-auto p-3 sm:max-w-[min(92vw,1400px)]">
          <DialogTitle className="sr-only">{alt}</DialogTitle>
          <DialogDescription className="sr-only">Tangkapan layar dengan penanda bernomor.</DialogDescription>
          <div className="overflow-hidden rounded-md border border-border">{gambarBerpenanda(true)}</div>
        </DialogContent>
      </Dialog>
    </figure>
  );
}
