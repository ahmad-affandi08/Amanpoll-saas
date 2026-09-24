import { Link } from '@inertiajs/react';
import { CloudCheck, CloudOff, RefreshCw, TriangleAlert, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteLapangan } from '@/features/Lapangan/api';
import { waktuRelatif } from '@/features/Lapangan/waktu';

interface TampilanChip {
  label: string;
  ikon: LucideIcon;
  kelas: string;
  berputar?: boolean;
}

/**
 * Teks dan gaya chip sinkronisasi dari status gabungan `use-sinkronisasi-offline`
 * (Online · Offline · Menyinkronkan · GagalSinkron · Konflik), ditambah jumlah antrean.
 */
export function useTampilanSinkron(): TampilanChip {
  const { status, jumlahBelumTersinkron, jumlahKonflik, paket } = useSinkronisasiOffline();
  const menunggu = jumlahBelumTersinkron > 0 ? ` · ${jumlahBelumTersinkron} menunggu dikirim` : '';

  switch (status) {
    case 'Offline':
      return {
        label: `Offline${menunggu}`,
        ikon: CloudOff,
        kelas: 'bg-lapangan-kuning-50/20 text-lapangan-oranye-100',
      };
    case 'Menyinkronkan':
      return {
        label: 'Mengirim perubahan…',
        ikon: RefreshCw,
        kelas: 'bg-white/15 text-white',
        berputar: true,
      };
    case 'GagalSinkron':
      return {
        label: 'Gagal kirim · coba lagi',
        ikon: TriangleAlert,
        kelas: 'bg-lapangan-merah-50/20 text-white',
      };
    case 'Konflik':
      return {
        label: `${jumlahKonflik} konflik perlu dipilih`,
        ikon: TriangleAlert,
        kelas: 'bg-lapangan-merah-50/20 text-white',
      };
    default:
      return {
        label:
          jumlahBelumTersinkron > 0
            ? `${jumlahBelumTersinkron} menunggu dikirim`
            : paket
              ? `Tersinkron ${waktuRelatif(paket.DibuatPada)}`
              : 'Online',
        ikon: CloudCheck,
        kelas: 'bg-white/15 text-white',
      };
  }
}

interface PropsChipSinkron {
  /** Tujuan saat diketuk. Bawaan layar Akun (antrean dan konflik ada di sana). */
  href?: string;
  className?: string;
}

/** Chip status sinkronisasi untuk hero Mode Lapangan (papan Teknisi layar 03 dan 16). */
export function ChipSinkron({ href = ruteLapangan.akun, className }: PropsChipSinkron) {
  const { label, ikon: Ikon, kelas, berputar } = useTampilanSinkron();

  return (
    <Link
      href={href}
      aria-label={`Status sinkronisasi: ${label}`}
      className={cn(
        'inline-flex h-[26px] max-w-full shrink-0 items-center gap-[5px] rounded-full px-2.5 text-xs font-bold whitespace-nowrap',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white',
        kelas,
        className,
      )}
    >
      <Ikon aria-hidden className={cn('size-3.5 shrink-0', berputar && 'animate-spin')} strokeWidth={2.4} />
      <span className="truncate">{label}</span>
    </Link>
  );
}
