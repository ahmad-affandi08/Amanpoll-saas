import { Camera, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { perkecilFoto } from '@/features/Lapangan/components/teknisi/foto';
import { cn } from '@/lib/utils';

/** Pratinjau berkas foto yang dipilih; URL objek dilepas saat daftar berubah. */
export function usePratinjauFoto(foto: File[]): string[] {
  const url = useMemo(() => foto.map((satu) => URL.createObjectURL(satu)), [foto]);

  useEffect(() => () => url.forEach((satu) => URL.revokeObjectURL(satu)), [url]);

  return url;
}

interface PropsFotoPilihan {
  foto: File[];
  onUbah: (foto: File[]) => void;
  maks: number;
  /** Label ubin tambah. Bawaan "Tambah". */
  labelTambah?: string;
  /** Ukuran ubin (px). Bawaan 84. */
  ukuran?: number;
  disabled?: boolean;
}

/**
 * Foto dari kamera HP (papan pelapor layar 06 dan 11): pratinjau dengan tombol hapus
 * dan ubin bergaris putus "Tambah". Berkasnya dikirim bersama formulir.
 */
export function FotoPilihan({
  foto,
  onUbah,
  maks,
  labelTambah = 'Tambah',
  ukuran = 84,
  disabled = false,
}: PropsFotoPilihan) {
  const masukan = useRef<HTMLInputElement | null>(null);
  const [memproses, setMemproses] = useState(false);
  const pratinjau = usePratinjauFoto(foto);

  /** Foto dikecilkan di HP sebelum ditampilkan dan dikirim (PRD 11.1). */
  const tambahkan = async (baru: File[]) => {
    setMemproses(true);
    try {
      const kecil = await Promise.all(baru.slice(0, maks - foto.length).map((satu) => perkecilFoto(satu)));
      onUbah([...foto, ...kecil].slice(0, maks));
    } finally {
      setMemproses(false);
    }
  };
  const gaya = { width: ukuran, height: ukuran };

  return (
    <div className="flex flex-wrap gap-2.5">
      {pratinjau.map((url, i) => (
        <div
          key={url}
          className="relative shrink-0 overflow-hidden rounded-[14px] bg-lapangan-garis-2"
          style={gaya}
        >
          <img src={url} alt={`Foto ${i + 1}`} className="size-full object-cover" />
          <button
            type="button"
            onClick={() => onUbah(foto.filter((_, j) => j !== i))}
            aria-label={`Hapus foto ${i + 1}`}
            className="absolute top-0.5 right-0.5 flex size-8 items-center justify-center rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          >
            <span className="flex size-[22px] items-center justify-center rounded-full bg-lapangan-navy-900/70 text-white">
              <X aria-hidden className="size-3" strokeWidth={3} />
            </span>
          </button>
        </div>
      ))}
      {foto.length < maks && (
        <button
          type="button"
          disabled={disabled || memproses}
          onClick={() => masukan.current?.click()}
          className={cn(
            'flex shrink-0 flex-col items-center justify-center gap-1 rounded-[14px] bg-white text-xs font-bold text-lapangan-oranye-teks outline-2 -outline-offset-2 outline-lapangan-teks-3/35 outline-dashed',
            'focus-visible:outline-solid focus-visible:outline-lapangan-biru-500 disabled:opacity-50',
          )}
          style={gaya}
        >
          <Camera aria-hidden className="size-6" />
          {labelTambah}
        </button>
      )}
      <input
        ref={masukan}
        type="file"
        accept="image/jpeg,image/png,image/webp"
        multiple
        className="sr-only"
        tabIndex={-1}
        aria-hidden
        onChange={(event) => {
          const baru = Array.from(event.target.files ?? []);
          event.target.value = '';
          void tambahkan(baru);
        }}
      />
    </div>
  );
}
