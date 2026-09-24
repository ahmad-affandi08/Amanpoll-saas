import { Check } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import type { KeadaanPerhentian } from '@/features/Lapangan/types';

export interface LangkahPerhentian {
  label: string;
  keadaan: KeadaanPerhentian;
}

interface PropsPerhentianLangkah {
  langkah: LangkahPerhentian[];
  /** Nama daftar untuk pembaca layar, mis. "Tahap pengerjaan". */
  label?: string;
  className?: string;
}

/**
 * Perhentian mendatar untuk langkah pengerjaan teknisi (Checklist • Diagnosis • Suku cadang •
 * Foto • Selesai). Yang sudah lewat oranye bercentang; yang sedang berlangsung bercincin oranye.
 */
export function PerhentianLangkah({
  langkah,
  label = 'Tahap pengerjaan',
  className,
}: PropsPerhentianLangkah) {
  return (
    <ol aria-label={label} className={cn('flex px-1 pt-4 pb-3', className)}>
      {langkah.map((satu, i) => {
        const lewat = satu.keadaan === 'lewat';
        const kini = satu.keadaan === 'kini';

        return (
          <li
            key={satu.label}
            aria-current={kini ? 'step' : undefined}
            className={cn(
              'relative flex flex-1 flex-col items-center gap-[7px] px-px text-center text-xs leading-tight font-semibold',
              kini ? 'font-bold text-lapangan-teks' : lewat ? 'text-lapangan-teks-2' : 'text-lapangan-teks-3',
            )}
          >
            {i > 0 && (
              <span
                aria-hidden
                className={cn(
                  'absolute right-1/2 w-full',
                  lewat || kini
                    ? 'top-[10.5px] border-t-[3px] border-lapangan-oranye-600'
                    : 'top-[11px] border-t-2 border-dashed border-lapangan-teks-3/40',
                )}
              />
            )}
            <span
              aria-hidden
              className={cn(
                'relative z-10 flex size-6 items-center justify-center rounded-full text-white',
                lewat && 'bg-lapangan-oranye-600',
                kini &&
                  'bg-white shadow-[inset_0_0_0_7px_var(--color-lapangan-oranye-600),0_0_0_5px_var(--color-lapangan-oranye-100)]',
                !lewat && !kini && 'bg-white shadow-[inset_0_0_0_2px_rgb(91_103_115_/_0.4)]',
              )}
            >
              {lewat && <Check className="size-3.5" strokeWidth={3.2} />}
            </span>
            <span>
              {satu.label}
              {lewat && <span className="sr-only"> (selesai)</span>}
            </span>
          </li>
        );
      })}
    </ol>
  );
}

export interface TitikLinimasa {
  judul: ReactNode;
  keterangan?: ReactNode;
  /** Jam di kiri, mis. "08.12" atau "±10.30". */
  jam?: string;
  keadaan: KeadaanPerhentian;
  /** Isi tambahan di bawah keterangan (mis. kartu teknisi dengan tombol telepon). */
  isi?: ReactNode;
}

interface PropsPerhentianLinimasa {
  titik: TitikLinimasa[];
  /** Nama daftar untuk pembaca layar, mis. "Perjalanan laporan". */
  label?: string;
  /** Teks penanda perhentian saat ini. Bawaan "Sekarang"; `null` untuk menyembunyikan. */
  penandaKini?: string | null;
  className?: string;
}

/**
 * Perhentian menurun untuk lacak laporan (Dilaporkan → Ditinjau → Ditugaskan → Dikerjakan →
 * Selesai): jam di kiri, noda di rel, judul dan keterangan di kanan.
 */
export function PerhentianLinimasa({
  titik,
  label = 'Perjalanan',
  penandaKini = 'Sekarang',
  className,
}: PropsPerhentianLinimasa) {
  return (
    <ol aria-label={label} className={cn('flex flex-col', className)}>
      {titik.map((satu, i) => {
        const akhir = i === titik.length - 1;
        const kini = satu.keadaan === 'kini';
        const nanti = satu.keadaan === 'nanti';

        return (
          <li
            key={i}
            aria-current={kini ? 'step' : undefined}
            className="grid grid-cols-[50px_28px_1fr] gap-x-2"
          >
            <span
              className={cn(
                'pt-px text-right tabular-nums',
                nanti
                  ? 'text-sm font-semibold text-lapangan-teks-3'
                  : 'text-base font-bold tracking-[-0.01em]',
                kini ? 'text-lapangan-oranye-teks' : !nanti && 'text-lapangan-teks',
              )}
            >
              {satu.jam}
            </span>
            <span aria-hidden className="relative flex justify-center">
              {!akhir && (
                <span
                  className={cn(
                    'absolute top-[18px] -bottom-1 left-1/2 -ml-[1.5px] w-[3px] rounded-sm',
                    kini || nanti
                      ? 'bg-[repeating-linear-gradient(to_bottom,var(--color-lapangan-garis)_0_6px,transparent_6px_11px)]'
                      : 'bg-lapangan-biru-500',
                  )}
                />
              )}
              <span
                className={cn(
                  'relative z-10 flex items-center justify-center rounded-full text-white',
                  kini
                    ? 'mt-px size-5 bg-lapangan-oranye-700 shadow-[0_0_0_5px_rgb(245_130_32_/_0.22),0_0_0_11px_rgb(245_130_32_/_0.1)]'
                    : nanti
                      ? 'mt-[3px] size-4 bg-white shadow-[inset_0_0_0_3px_var(--color-lapangan-garis),0_0_0_3px_white]'
                      : 'mt-[3px] size-4 bg-lapangan-biru-600 shadow-[0_0_0_3px_white]',
                )}
              >
                {!kini && !nanti && <Check className="size-2.5" strokeWidth={4} />}
              </span>
            </span>
            <div className="min-w-0 pb-3.5">
              <b
                className={cn(
                  'flex flex-wrap items-center gap-2 text-[15px] leading-[1.3]',
                  nanti ? 'font-semibold text-lapangan-teks-2' : 'font-bold text-lapangan-teks',
                )}
              >
                {satu.judul}
                {kini && penandaKini && (
                  <ChipStatus warna="oranye" ukuran="kecil">
                    {penandaKini}
                  </ChipStatus>
                )}
              </b>
              {satu.keterangan && (
                <span className="mt-px block text-[13px] font-medium text-lapangan-teks-3">
                  {satu.keterangan}
                </span>
              )}
              {satu.isi && <div className="mt-2">{satu.isi}</div>}
            </div>
          </li>
        );
      })}
    </ol>
  );
}

export interface HalteJadwal {
  jam: string;
  label: string;
  keadaan: KeadaanPerhentian;
}

interface PropsPerhentianJadwal {
  halte: HalteJadwal[];
  label?: string;
  className?: string;
}

/** Jadwal hari ini di beranda teknisi: jam di atas, noda di garis putus, nama pekerjaan di bawah. */
export function PerhentianJadwal({ halte, label = 'Jadwal hari ini', className }: PropsPerhentianJadwal) {
  return (
    <ol
      aria-label={label}
      className={cn('grid px-1.5 pt-0.5 pb-2.5', className)}
      style={{ gridTemplateColumns: `repeat(${Math.max(halte.length, 1)}, minmax(0, 1fr))` }}
    >
      {halte.map((satu, i) => {
        const kini = satu.keadaan === 'kini';

        return (
          <li
            key={`${satu.jam}-${i}`}
            aria-current={kini ? 'step' : undefined}
            className="relative flex flex-col items-center text-center"
          >
            {i > 0 && (
              <span
                aria-hidden
                className="absolute top-8 right-1/2 w-full border-t-2 border-dashed border-lapangan-teks-3/40"
              />
            )}
            <b
              className={cn(
                'text-[15px] leading-5 font-bold tracking-[-0.01em] tabular-nums',
                kini ? 'text-lapangan-oranye-teks' : 'text-lapangan-teks',
              )}
            >
              {satu.jam}
            </b>
            <span
              aria-hidden
              className={cn(
                'relative z-10 mt-1.5 mb-[5px] size-3.5 rounded-full bg-white',
                kini
                  ? 'shadow-[inset_0_0_0_4px_var(--color-lapangan-oranye-600),0_0_0_4px_var(--color-lapangan-oranye-100)]'
                  : satu.keadaan === 'lewat'
                    ? 'bg-lapangan-oranye-600'
                    : 'shadow-[inset_0_0_0_3px_rgb(91_103_115_/_0.45)]',
              )}
            />
            <span
              className={cn(
                'line-clamp-2 text-xs leading-[1.3]',
                kini ? 'font-bold text-lapangan-teks' : 'font-semibold text-lapangan-teks-3',
              )}
            >
              {satu.label}
            </span>
          </li>
        );
      })}
    </ol>
  );
}

interface PropsPerhentianAppbar {
  langkah: LangkahPerhentian[];
  label?: string;
  className?: string;
}

/**
 * Indikator langkah di dalam appbar gradien (papan Pelapor layar 04–07: 1 Alat — 2 Masalah — 3 Kirim).
 * Pasang lewat prop `langkah` KerangkaLapangan varian `appbar`.
 */
export function PerhentianAppbar({ langkah, label = 'Langkah', className }: PropsPerhentianAppbar) {
  return (
    <ol aria-label={label} className={cn('flex items-center gap-2 px-1', className)}>
      {langkah.map((satu, i) => {
        const lewat = satu.keadaan === 'lewat';
        const kini = satu.keadaan === 'kini';

        return (
          <li
            key={satu.label}
            aria-current={kini ? 'step' : undefined}
            className={cn('flex items-center gap-2', i > 0 && 'flex-1')}
          >
            {i > 0 && (
              <span
                aria-hidden
                className={cn(
                  'flex-1 border-t-2',
                  lewat || kini ? 'border-solid border-white/85' : 'border-dashed border-white/30',
                )}
              />
            )}
            <span
              className={cn(
                'flex items-center gap-2 text-[13px] font-bold whitespace-nowrap',
                lewat || kini ? 'text-white' : 'text-white/70',
              )}
            >
              <span
                aria-hidden
                className={cn(
                  'flex size-7 items-center justify-center rounded-full text-[13px] font-bold',
                  kini && 'bg-lapangan-oranye-700 text-white shadow-[0_0_0_4px_rgb(245_130_32_/_0.3)]',
                  lewat && 'bg-white text-lapangan-navy-800',
                  !lewat && !kini && 'shadow-[inset_0_0_0_1.5px_rgb(255_255_255_/_0.4)]',
                )}
              >
                {lewat ? <Check className="size-4" strokeWidth={3} /> : i + 1}
              </span>
              {satu.label}
              {lewat && <span className="sr-only"> (selesai)</span>}
            </span>
          </li>
        );
      })}
    </ol>
  );
}
