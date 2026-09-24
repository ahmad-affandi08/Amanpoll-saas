import { useState, type ComponentProps, type ReactNode } from 'react';
import { CircleCheck, Eye, EyeOff, LoaderCircle, type LucideIcon } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** Jarak antarbagian formulir di kartu autentikasi: 14px di HP, 18px mulai 640px (DESIGN.md 37). */
export const KELAS_FORMULIR = 'space-y-3.5 sm:space-y-[18px]';

/** Tautan teks halaman autentikasi: oranye teks (>= 4,5:1 di atas putih), cincin biru saat fokus. */
export const TAUTAN_AUTENTIKASI =
  'rounded-sm font-bold text-lapangan-oranye-teks underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-lapangan-biru-500 focus-visible:outline-none';

/** Atribut yang menghubungkan isian dengan pesan galatnya (DESIGN.md 26). */
export interface AtributIsian {
  id: string;
  'aria-invalid': boolean | undefined;
  'aria-describedby': string | undefined;
}

interface BidangIsianProps {
  id: string;
  label: string;
  /** Nama field pada FormRequest, untuk tanda wajib dari server. */
  nama?: string;
  galat?: string;
  /** Teks bantuan di bawah isian, mis. syarat panjang kata sandi. */
  bantuan?: string;
  children: (atribut: AtributIsian) => ReactNode;
}

/** Label di atas, isian, lalu bantuan dan galat tepat di bawahnya. */
export function BidangIsian({ id, label, nama, galat, bantuan, children }: BidangIsianProps) {
  // Galat sudah menyebut aturannya; bantuan disembunyikan agar tidak berulang.
  const tampilkanBantuan = bantuan !== undefined && !galat;
  const idBantuan = tampilkanBantuan ? `${id}-bantuan` : null;
  const idGalat = galat ? `${id}-galat` : null;
  const dijelaskan = [idBantuan, idGalat].filter(Boolean).join(' ');

  return (
    <div className="space-y-2">
      <Label nama={nama} htmlFor={id} className="text-sm font-semibold text-lapangan-teks">
        {label}
      </Label>
      {children({
        id,
        'aria-invalid': galat ? true : undefined,
        'aria-describedby': dijelaskan === '' ? undefined : dijelaskan,
      })}
      {tampilkanBantuan ? (
        <p id={idBantuan ?? undefined} className="text-[13px] text-lapangan-teks-3">
          {bantuan}
        </p>
      ) : null}
      {galat ? (
        <p id={idGalat ?? undefined} className="text-sm font-medium text-lapangan-merah-700">
          {galat}
        </p>
      ) : null}
    </div>
  );
}

type PropsIsian = ComponentProps<typeof Input> & {
  /** Ikon Lucide kecil di sisi kiri isian (mis. `Mail`, `Lock`); hiasan, label tetap di atas. */
  ikon?: LucideIcon;
};

/**
 * Isian setinggi 52px bergaya papan arah 2: latar abu sangat muda, bingkai 1,5px, radius 14px,
 * fokus berbingkai Biru-500 dengan cincin lembut. Teks 16px supaya Safari di HP tidak memperbesar.
 */
export function IsianAutentikasi({ className, ikon: Ikon, ...props }: PropsIsian) {
  return (
    <div className="relative">
      {Ikon ? (
        <Ikon
          className="pointer-events-none absolute top-1/2 left-4 size-[19px] -translate-y-1/2 text-lapangan-teks-3"
          aria-hidden="true"
        />
      ) : null}
      <Input
        className={cn(
          'h-[52px] rounded-[14px] border-[1.5px] border-lapangan-garis bg-lapangan-latar/50 px-4 text-base text-lapangan-teks shadow-none transition-[color,box-shadow,background-color] placeholder:text-lapangan-teks-3 md:text-base',
          'focus-visible:border-lapangan-biru-500 focus-visible:bg-white focus-visible:ring-4 focus-visible:ring-lapangan-biru-500/15',
          'aria-invalid:border-lapangan-merah-700 aria-invalid:ring-lapangan-merah-700/15',
          'disabled:bg-lapangan-latar disabled:text-lapangan-teks-3',
          Ikon && 'pl-[46px]',
          className,
        )}
        {...props}
      />
    </div>
  );
}

/** Isian kata sandi dengan tombol tampilkan/sembunyikan 44px di sisi kanan. */
export function IsianKataSandi({ className, ...props }: Omit<PropsIsian, 'type'>) {
  const [terlihat, setTerlihat] = useState(false);

  return (
    <div className="relative">
      <IsianAutentikasi type={terlihat ? 'text' : 'password'} className={cn('pr-14', className)} {...props} />
      <button
        type="button"
        onClick={() => setTerlihat((sebelumnya) => !sebelumnya)}
        aria-label={terlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
        aria-controls={props.id}
        aria-pressed={terlihat}
        disabled={props.disabled}
        className="absolute top-1/2 right-1 flex size-11 -translate-y-1/2 items-center justify-center rounded-[10px] text-lapangan-teks-2 transition-colors hover:bg-lapangan-latar hover:text-lapangan-teks focus-visible:ring-2 focus-visible:ring-lapangan-biru-500 focus-visible:outline-none"
      >
        {terlihat ? (
          <EyeOff className="size-5" aria-hidden="true" />
        ) : (
          <Eye className="size-5" aria-hidden="true" />
        )}
      </button>
    </div>
  );
}

/** Kotak centang 22px bersudut 7px; tercentang Navy-800 (papan arah 2). */
export function CentangAutentikasi({ className, ...props }: ComponentProps<typeof Checkbox>) {
  return (
    <Checkbox
      className={cn(
        'size-[22px] rounded-[7px] border-[1.5px] border-lapangan-teks-3 bg-white shadow-none focus-visible:ring-lapangan-biru-500 data-[state=checked]:border-lapangan-navy-800 data-[state=checked]:bg-lapangan-navy-800 data-[state=checked]:text-white [&_svg]:size-[15px] [&_svg]:stroke-[3]',
        className,
      )}
      {...props}
    />
  );
}

interface TombolKirimProps {
  memproses: boolean;
  labelMemproses: string;
  disabled?: boolean;
  children: ReactNode;
}

/** Tombol utama: oranye Mode Lapangan (teks putih 4,6:1), 54px, selebar kartu, dengan keadaan memuat. */
export function TombolKirim({ memproses, labelMemproses, disabled, children }: TombolKirimProps) {
  return (
    <button
      type="submit"
      disabled={memproses || disabled}
      aria-busy={memproses}
      className="flex h-[54px] w-full items-center justify-center gap-2.5 rounded-[14px] bg-lapangan-oranye-700 text-[17px] font-bold text-white shadow-lapangan-oranye transition-colors hover:bg-lapangan-oranye-teks focus-visible:ring-4 focus-visible:ring-lapangan-oranye-100 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
    >
      {memproses ? (
        <>
          <LoaderCircle className="size-5 animate-spin" aria-hidden="true" />
          {labelMemproses}
        </>
      ) : (
        children
      )}
    </button>
  );
}

/** Pesan sukses dari flash server, mis. sesudah reset kata sandi atau pendaftaran trial. */
export function PesanSukses({ children }: { children: ReactNode }) {
  return (
    <div
      role="status"
      className="flex items-start gap-2.5 rounded-[14px] bg-lapangan-hijau-50 px-4 py-3 text-sm font-medium text-lapangan-hijau-700"
    >
      <CircleCheck className="mt-0.5 size-[18px] shrink-0" aria-hidden="true" />
      <p>{children}</p>
    </div>
  );
}
