import { useState } from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Props {
  /** Path berkas di `public/assets/3d`. */
  sumber: string;
  /** Ikon pengganti saat berkas ilustrasi belum tersedia. */
  ikonCadangan: LucideIcon;
  /** Kelas warna untuk ikon cadangan, mis. `text-bahaya-600`. */
  warnaCadangan?: string;
  ukuran?: number;
  className?: string;
}

/**
 * Ilustrasi 3D dengan cadangan ikon. Berkas ilustrasi bersifat dekoratif, jadi
 * kegagalan memuatnya tidak boleh menyisakan gambar rusak di tengah dialog.
 */
export function Ilustrasi3d({
  sumber,
  ikonCadangan: IkonCadangan,
  warnaCadangan = 'text-muted-foreground',
  ukuran = 56,
  className,
}: Props) {
  const [gagal, setGagal] = useState(false);

  if (gagal) {
    return (
      <IkonCadangan
        aria-hidden
        className={cn('shrink-0', warnaCadangan, className)}
        style={{ width: ukuran, height: ukuran }}
      />
    );
  }

  return (
    <img
      src={sumber}
      alt=""
      aria-hidden
      width={ukuran}
      height={ukuran}
      loading="lazy"
      onError={() => setGagal(true)}
      className={cn('shrink-0 object-contain', className)}
      style={{ width: ukuran, height: ukuran }}
    />
  );
}
