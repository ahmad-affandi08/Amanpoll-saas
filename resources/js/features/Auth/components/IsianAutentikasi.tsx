import { useState, type ComponentProps, type ReactNode } from 'react';
import { CircleCheck, Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** Tautan teks halaman autentikasi: warna primer, garis bawah saat hover, cincin saat fokus. */
export const TAUTAN_AUTENTIKASI =
  'rounded-sm font-medium text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

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
    <div className="space-y-1.5">
      <Label nama={nama} htmlFor={id}>
        {label}
      </Label>
      {children({
        id,
        'aria-invalid': galat ? true : undefined,
        'aria-describedby': dijelaskan === '' ? undefined : dijelaskan,
      })}
      {tampilkanBantuan ? (
        <p id={idBantuan ?? undefined} className="text-xs text-muted-foreground">
          {bantuan}
        </p>
      ) : null}
      {galat ? (
        <p id={idGalat ?? undefined} className="text-sm text-destructive">
          {galat}
        </p>
      ) : null}
    </div>
  );
}

/** Isian setinggi 44px: target sentuh nyaman di HP, tetap lega di desktop. */
export function IsianAutentikasi({ className, ...props }: ComponentProps<typeof Input>) {
  return <Input className={cn('h-11 bg-card', className)} {...props} />;
}

/** Isian kata sandi dengan tombol tampilkan/sembunyikan di sisi kanan. */
export function IsianKataSandi({ className, ...props }: Omit<ComponentProps<typeof Input>, 'type'>) {
  const [terlihat, setTerlihat] = useState(false);

  return (
    <div className="relative">
      <IsianAutentikasi type={terlihat ? 'text' : 'password'} className={cn('pr-12', className)} {...props} />
      <button
        type="button"
        onClick={() => setTerlihat((sebelumnya) => !sebelumnya)}
        aria-label={terlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
        aria-controls={props.id}
        aria-pressed={terlihat}
        disabled={props.disabled}
        className="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-grafit-500 hover:text-grafit-950 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
      >
        {terlihat ? (
          <EyeOff className="size-[18px]" aria-hidden="true" />
        ) : (
          <Eye className="size-[18px]" aria-hidden="true" />
        )}
      </button>
    </div>
  );
}

interface TombolKirimProps {
  memproses: boolean;
  labelMemproses: string;
  disabled?: boolean;
  children: ReactNode;
}

/** Tombol utama formulir autentikasi: selebar formulir, 44px, dengan keadaan memuat. */
export function TombolKirim({ memproses, labelMemproses, disabled, children }: TombolKirimProps) {
  return (
    <Button
      type="submit"
      className="h-11 w-full sm:h-11"
      disabled={memproses || disabled}
      aria-busy={memproses}
    >
      {memproses ? (
        <>
          <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />
          {labelMemproses}
        </>
      ) : (
        children
      )}
    </Button>
  );
}

/** Pesan sukses dari flash server, mis. sesudah reset kata sandi atau pendaftaran trial. */
export function PesanSukses({ children }: { children: ReactNode }) {
  return (
    <div
      role="status"
      className="flex items-start gap-2 rounded-md border border-sukses-200 bg-sukses-50 p-3 text-sm text-sukses-700"
    >
      <CircleCheck className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
      <p>{children}</p>
    </div>
  );
}
