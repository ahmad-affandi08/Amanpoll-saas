import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { WarnaChip } from '@/features/Lapangan/types';

const KELAS_WARNA: Record<WarnaChip, string> = {
  merah: 'bg-lapangan-merah-50 text-lapangan-merah-700',
  oranye: 'bg-lapangan-oranye-50 text-lapangan-oranye-teks',
  kuning: 'bg-lapangan-kuning-50 text-lapangan-kuning-700',
  biru: 'bg-lapangan-biru-50 text-lapangan-biru-600',
  hijau: 'bg-lapangan-hijau-50 text-lapangan-hijau-700',
  abu: 'bg-lapangan-garis-2 text-lapangan-teks-2',
  putih: 'bg-white/15 text-white',
};

const MERAH = ['Kritis', 'Terlambat', 'Rusak', 'Konflik', 'Gagal', 'GagalSinkron', 'Berbahaya'];
const ORANYE = ['Tinggi', 'Mendesak'];
const KUNING = ['Dijeda', 'PerluKonfirmasi', 'Offline', 'Terbatas', 'Baru'];
const BIRU = [
  'Ditugaskan',
  'Diterima',
  'Dikerjakan',
  'Diproses',
  'Ditinjau',
  'Terjadwal',
  'Menyinkronkan',
  'SedangDikerjakan',
];
const HIJAU = ['Selesai', 'Ditutup', 'Baik', 'Berfungsi', 'Tersinkron', 'Online', 'Aktif'];

/**
 * Warna chip untuk status atau prioritas domain (DESIGN.md 36.3):
 * Kritis/Terlambat merah · Tinggi oranye · Menunggu… kuning ·
 * Ditugaskan/Dikerjakan/Diproses biru · Selesai/Ditutup hijau · lainnya abu.
 */
export function warnaStatus(status: string | null | undefined): WarnaChip {
  if (!status) return 'abu';
  const kunci = status.replace(/\s+/g, '');
  if (MERAH.includes(kunci)) return 'merah';
  if (ORANYE.includes(kunci)) return 'oranye';
  if (kunci.startsWith('Menunggu') || KUNING.includes(kunci)) return 'kuning';
  if (BIRU.includes(kunci)) return 'biru';
  if (HIJAU.includes(kunci)) return 'hijau';
  return 'abu';
}

/** Kode status berhuruf kapital tengah menjadi teks baca: "MenungguSukuCadang" → "Menunggu suku cadang". */
export function labelStatus(status: string): string {
  const kata = status.replace(/([a-z])([A-Z])/g, '$1 $2').split(' ');
  return kata.map((satu, i) => (i === 0 ? satu : satu.toLowerCase())).join(' ');
}

interface PropsChipStatus {
  /** Kode status/prioritas domain; menentukan warna dan (bila `children` kosong) teksnya. */
  status?: string | null;
  /** Menimpa warna hasil pemetaan `status`. */
  warna?: WarnaChip;
  /** Teks chip. Bawaan: `labelStatus(status)`. Chip selalu bertuliskan teks, tidak hanya warna. */
  children?: ReactNode;
  /** Ikon Lucide kecil di depan teks. */
  ikon?: LucideIcon;
  /** `kecil` (22px) untuk chip di dalam baris; bawaan 26px. */
  ukuran?: 'normal' | 'kecil';
  className?: string;
}

/** Pil status berwarna lembut dengan teks -700 (DESIGN.md 36.3). */
export function ChipStatus({
  status,
  warna,
  children,
  ikon: Ikon,
  ukuran = 'normal',
  className,
}: PropsChipStatus) {
  const warnaAkhir = warna ?? warnaStatus(status);
  const teks = children ?? (status ? labelStatus(status) : null);

  return (
    <span
      className={cn(
        'inline-flex shrink-0 items-center gap-[5px] rounded-full px-2.5 text-xs font-bold whitespace-nowrap',
        ukuran === 'kecil' ? 'h-[22px]' : 'h-[26px]',
        KELAS_WARNA[warnaAkhir],
        className,
      )}
    >
      {Ikon && <Ikon aria-hidden className="size-3.5 shrink-0" strokeWidth={2.4} />}
      {teks}
    </span>
  );
}
