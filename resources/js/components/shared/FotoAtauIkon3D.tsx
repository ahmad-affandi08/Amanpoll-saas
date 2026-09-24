import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { Ikon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';

interface PropsFotoAtauIkon3D {
  /** URL thumbnail foto; `null` langsung menampilkan ikon. */
  url: string | null | undefined;
  /** Ikon 3D cadangan, biasanya dari kategori aset (`ikonKategori`). */
  ikon: NamaIkon3D;
  /** Ukuran ikon cadangan dalam px. */
  ukuranIkon: number;
  /** Teks alternatif foto. Ikon cadangan selalu hiasan (namanya sudah tertulis di dekatnya). */
  alt: string;
  /** Ukuran, sudut, dan latar wadah; dipakai foto maupun ikonnya. */
  className?: string;
  /** Muat segera (foto besar di atas lipatan). Bawaan: lazy, cocok untuk daftar. */
  segera?: boolean;
}

/**
 * Foto aset (thumbnail) dengan cadangan ikon 3D kategorinya (PRD 8.4 "Foto Aset"). Ikon juga
 * dipakai bila foto gagal dimuat, mis. tanpa sinyal dan thumbnail belum ada di cache peramban,
 * atau hak melihatnya sudah dicabut.
 */
export function FotoAtauIkon3D({
  url,
  ikon,
  ukuranIkon,
  alt,
  className,
  segera = false,
}: PropsFotoAtauIkon3D) {
  const [gagal, setGagal] = useState(false);

  useEffect(() => setGagal(false), [url]);

  if (url && !gagal) {
    return (
      <img
        src={url}
        alt={alt}
        loading={segera ? 'eager' : 'lazy'}
        decoding="async"
        draggable={false}
        onError={() => setGagal(true)}
        className={cn('block shrink-0 object-cover select-none', className)}
      />
    );
  }

  return (
    <span className={cn('flex shrink-0 items-center justify-center', className)}>
      <Ikon3D nama={ikon} ukuran={ukuranIkon} segera={segera} />
    </span>
  );
}
