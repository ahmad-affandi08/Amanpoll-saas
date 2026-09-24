import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type LatarLekuk = 'latar' | 'putih';

const KELAS_LEKUK: Record<LatarLekuk, string> = {
  latar: 'before:bg-lapangan-latar after:bg-lapangan-latar',
  putih: 'before:bg-white after:bg-white',
};

interface PropsSobekan {
  /** Warna latar di belakang tiket; lekukan diisi warna ini agar tampak berlubang. Bawaan `latar`. */
  latarLekuk?: LatarLekuk;
  /** Tiket bergaris tipis: lekukan ikut bergaris. */
  bergaris?: boolean;
  className?: string;
}

/** Sobekan tiket: dua lekukan setengah lingkaran di tepi dan garis putus-putus di tengah. */
export function Sobekan({ latarLekuk = 'latar', bergaris = false, className }: PropsSobekan) {
  return (
    <div
      aria-hidden
      className={cn(
        'relative h-[18px]',
        'before:absolute before:top-0 before:-left-[9px] before:size-[18px] before:rounded-full',
        'after:absolute after:top-0 after:-right-[9px] after:size-[18px] after:rounded-full',
        bergaris &&
          'before:shadow-[inset_-1.5px_0_0_0_var(--color-lapangan-garis)] after:shadow-[inset_1.5px_0_0_0_var(--color-lapangan-garis)]',
        KELAS_LEKUK[latarLekuk],
        className,
      )}
    >
      <i className="absolute inset-x-4 top-2 border-t-2 border-dashed border-lapangan-garis" />
    </div>
  );
}

interface PropsTiket {
  /** Bagian atas: chip status, nomor, judul, rute jam. */
  atas: ReactNode;
  /** Bagian bawah sesudah sobekan: aset, lokasi, aksi. Tanpa `bawah`, sobekan tidak digambar. */
  bawah?: ReactNode;
  /** Disorot cincin oranye (mis. laporan yang menunggu konfirmasi). */
  sorot?: boolean;
  /** Bergaris tipis tanpa bayangan (di atas latar putih, mis. layar sukses). */
  bergaris?: boolean;
  /** Menimpa hero seperti kartu apung (bayangan lebih dalam, `-mt-14`). */
  apung?: boolean;
  /** Warna di belakang tiket. Bawaan `latar`; pakai `putih` bila tiket di atas permukaan putih. */
  latarLekuk?: LatarLekuk;
  /** Padding bagian; `ringkas` untuk daftar. */
  kerapatan?: 'normal' | 'ringkas';
  className?: string;
  /** Elemen pembungkus. Bawaan `article`. */
  sebagai?: 'article' | 'div' | 'li';
}

/**
 * Tiket (DESIGN.md 36.3): kartu dengan sobekan berlekuk di kedua sisi dan garis putus-putus.
 * Untuk tiket kerja, laporan keluhan, dan bukti selesai.
 */
export function Tiket({
  atas,
  bawah,
  sorot = false,
  bergaris = false,
  apung = false,
  latarLekuk,
  kerapatan = 'normal',
  className,
  sebagai: Elemen = 'article',
}: PropsTiket) {
  const ringkas = kerapatan === 'ringkas';

  return (
    <Elemen
      className={cn(
        'relative rounded-[20px] bg-white',
        bergaris ? 'ring-[1.5px] ring-lapangan-garis ring-inset' : 'shadow-lapangan-kartu',
        sorot && 'ring-2 ring-lapangan-oranye-100',
        apung && 'relative z-10 -mt-14 shadow-lapangan-apung',
        className,
      )}
    >
      <div
        className={cn(
          'px-4',
          ringkas ? 'pt-3.5 pb-2.5' : 'pt-4 pb-3',
          !bawah && (ringkas ? 'pb-3.5' : 'pb-4'),
        )}
      >
        {atas}
      </div>
      {bawah && (
        <>
          <Sobekan latarLekuk={latarLekuk ?? (bergaris ? 'putih' : 'latar')} bergaris={bergaris} />
          <div className={cn('px-4', ringkas ? 'pt-2.5 pb-3.5' : 'pt-3 pb-4')}>{bawah}</div>
        </>
      )}
    </Elemen>
  );
}

interface PropsJudulTiket {
  children: ReactNode;
  className?: string;
}

/** Judul tiket 17px tebal. */
export function JudulTiket({ children, className }: PropsJudulTiket) {
  return (
    <h3
      className={cn(
        'mt-2.5 mb-3 text-[17px] leading-[1.3] font-bold tracking-[-0.01em] text-lapangan-teks',
        className,
      )}
    >
      {children}
    </h3>
  );
}
