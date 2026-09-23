import { Link } from '@inertiajs/react';
import { AlertTriangle, CloudOff, RefreshCw, TriangleAlert, Wifi } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteOffline } from '@/features/Sinkronisasi/api';
import type { StatusSinkronisasi } from '@/features/Sinkronisasi/types';

const TAMPILAN: Record<
  StatusSinkronisasi,
  { label: string; ikon: typeof Wifi; kelas: string; berputar?: boolean }
> = {
  Online: { label: 'Online', ikon: Wifi, kelas: 'border-sukses-600/25 bg-sukses-600/10 text-sukses-700' },
  Offline: {
    label: 'Offline',
    ikon: CloudOff,
    kelas: 'border-safety-600/30 bg-safety-500/15 text-safety-700',
  },
  Menyinkronkan: {
    label: 'Menyinkronkan',
    ikon: RefreshCw,
    kelas: 'border-teknisi-600/25 bg-teknisi-600/10 text-teknisi-700',
    berputar: true,
  },
  GagalSinkron: {
    label: 'Sinkron gagal',
    ikon: TriangleAlert,
    kelas: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700',
  },
  Konflik: {
    label: 'Konflik',
    ikon: AlertTriangle,
    kelas: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700',
  },
};

/** Indikator sinkronisasi topbar (DESIGN.md 24). */
export function IndikatorSinkronisasi({ className }: { className?: string }) {
  const { status, jumlahBelumTersinkron, jumlahKonflik } = useSinkronisasiOffline();

  const perluTampil = status !== 'Online' || jumlahBelumTersinkron > 0;
  if (!perluTampil) {
    return null;
  }

  const { label, ikon: Ikon, kelas, berputar } = TAMPILAN[status];
  const keterangan =
    jumlahKonflik > 0
      ? `${jumlahKonflik} perubahan berkonflik`
      : jumlahBelumTersinkron > 0
        ? `${jumlahBelumTersinkron} perubahan belum tersinkron`
        : null;

  return (
    <Link
      href={ruteOffline.teknisi}
      title={keterangan ?? label}
      className={cn(
        'inline-flex items-center gap-2 rounded-[5px] border px-2 py-1 text-xs font-medium transition-colors',
        kelas,
        className,
      )}
    >
      <Ikon className={cn('size-3.5 shrink-0', berputar && 'animate-spin')} />
      <span>{label}</span>
      {keterangan && <span className="hidden font-normal sm:inline">· {keterangan}</span>}
    </Link>
  );
}
