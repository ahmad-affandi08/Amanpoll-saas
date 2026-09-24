import type { LucideIcon } from 'lucide-react';
import { createContext, useContext, useId, type ComponentProps, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface KonteksIsian {
  id: string;
  idKeterangan: string | undefined;
  galat: boolean;
}

const KonteksIsianTiket = createContext<KonteksIsian | null>(null);

function useKonteksIsian(): KonteksIsian | null {
  return useContext(KonteksIsianTiket);
}

interface PropsIsianTiket {
  /** Label kecil di dalam kotak, mis. "Lokasi". */
  label: string;
  /** Kontrolnya: `<MasukanTiket>`, `<PilihanTiket>`, atau `<AreaTiket>`. */
  children: ReactNode;
  /** Id kontrol; bawaan dibuat otomatis dan dipasang ke kontrol di dalamnya. */
  id?: string;
  /** Ikon Lucide di wadah biru muda di kiri. */
  ikon?: LucideIcon;
  /** Isi kanan, mis. tautan "Ganti" atau tombol mikrofon. */
  kanan?: ReactNode;
  /** Pesan galat validasi; memberi bingkai merah dan `aria-invalid`. */
  galat?: string | null;
  /** Teks bantuan di bawah kotak. */
  bantuan?: ReactNode;
  className?: string;
}

/**
 * Isian bergaya formulir tiket (DESIGN.md 36.3): label kecil di dalam kotak putih radius 16px,
 * bingkai biru saat fokus, merah saat galat. Label terhubung ke kontrol lewat `htmlFor`.
 */
export function IsianTiket({
  label,
  children,
  id,
  ikon: Ikon,
  kanan,
  galat,
  bantuan,
  className,
}: PropsIsianTiket) {
  const idOtomatis = useId();
  const idKontrol = id ?? `isian-${idOtomatis}`;
  const idKeterangan = galat || bantuan ? `${idKontrol}-keterangan` : undefined;

  return (
    <div className={className}>
      <div
        className={cn(
          'flex min-h-[62px] items-center gap-3 rounded-2xl bg-white px-3.5 py-3 transition-shadow',
          galat
            ? 'shadow-[inset_0_0_0_2px_var(--color-lapangan-merah-700)]'
            : 'shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)] focus-within:shadow-[inset_0_0_0_2px_var(--color-lapangan-biru-500),0_0_0_4px_var(--color-lapangan-biru-50)]',
        )}
      >
        {Ikon && (
          <span className="flex size-[38px] shrink-0 items-center justify-center rounded-xl bg-lapangan-biru-50 text-lapangan-biru-600">
            <Ikon aria-hidden className="size-[19px]" />
          </span>
        )}
        <div className="min-w-0 flex-1">
          <label htmlFor={idKontrol} className="block text-xs font-semibold text-lapangan-teks-3">
            {label}
          </label>
          <KonteksIsianTiket.Provider value={{ id: idKontrol, idKeterangan, galat: Boolean(galat) }}>
            {children}
          </KonteksIsianTiket.Provider>
        </div>
        {kanan}
      </div>
      {(galat || bantuan) && (
        <p
          id={idKeterangan}
          className={cn(
            'mt-1.5 px-1 text-[13px]',
            galat ? 'font-semibold text-lapangan-merah-700' : 'text-lapangan-teks-3',
          )}
        >
          {galat ?? bantuan}
        </p>
      )}
    </div>
  );
}

const KELAS_KONTROL =
  'block w-full bg-transparent p-0 text-base font-bold text-lapangan-teks outline-none placeholder:font-medium placeholder:text-lapangan-teks-3';

function atributKontrol(konteks: KonteksIsian | null, id: string | undefined) {
  return {
    id: id ?? konteks?.id,
    'aria-invalid': konteks?.galat ? true : undefined,
    'aria-describedby': konteks?.idKeterangan,
  };
}

/** `<input>` tanpa bingkai untuk dipakai di dalam `IsianTiket`. */
export function MasukanTiket({ className, id, ...props }: ComponentProps<'input'>) {
  const konteks = useKonteksIsian();
  return <input {...atributKontrol(konteks, id)} className={cn(KELAS_KONTROL, className)} {...props} />;
}

/** `<select>` bawaan (pemilih asli HP) untuk dipakai di dalam `IsianTiket`. */
export function PilihanTiket({ className, id, ...props }: ComponentProps<'select'>) {
  const konteks = useKonteksIsian();
  return (
    <select
      {...atributKontrol(konteks, id)}
      className={cn(KELAS_KONTROL, 'appearance-none', className)}
      {...props}
    />
  );
}

/** `<textarea>` tanpa bingkai untuk dipakai di dalam `IsianTiket`. */
export function AreaTiket({ className, id, rows = 3, ...props }: ComponentProps<'textarea'>) {
  const konteks = useKonteksIsian();
  return (
    <textarea
      {...atributKontrol(konteks, id)}
      rows={rows}
      className={cn(KELAS_KONTROL, 'resize-none text-[15px] leading-[1.45] font-semibold', className)}
      {...props}
    />
  );
}
