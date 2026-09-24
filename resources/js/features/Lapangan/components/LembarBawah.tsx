import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';

interface PropsLembarBawah {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  judul: ReactNode;
  /** Kalimat di bawah judul; juga dibacakan sebagai deskripsi dialog. */
  deskripsi?: ReactNode;
  children: ReactNode;
  /** Aksi di kaki lembar (mis. tombol oranye penuh "Kirim"). */
  kaki?: ReactNode;
  /** Elemen pemicu (dibungkus `SheetTrigger asChild`); boleh kosong bila dibuka lewat state. */
  pemicu?: ReactNode;
  /** Sembunyikan tombol tutup (X). Lembar tetap bisa ditutup dengan Esc atau mengetuk tirai. */
  tanpaTombolTutup?: boolean;
  className?: string;
}

/**
 * Lembar bawah (DESIGN.md 36.2) untuk pilihan dan input pendek: minta suku cadang, tambah
 * keterangan, hasil pindai. Dibangun di atas `components/ui/sheet` (Radix Dialog): fokus
 * terkunci di dalam, Esc menutup, judul dan deskripsi terhubung ke dialog.
 */
export function LembarBawah({
  buka,
  onBukaBerubah,
  judul,
  deskripsi,
  children,
  kaki,
  pemicu,
  tanpaTombolTutup = false,
  className,
}: PropsLembarBawah) {
  return (
    <Sheet open={buka} onOpenChange={onBukaBerubah}>
      {pemicu && <SheetTrigger asChild>{pemicu}</SheetTrigger>}
      <SheetContent
        side="bottom"
        showCloseButton={false}
        className={cn(
          'mx-auto max-h-[92dvh] w-full max-w-[480px] gap-0 overflow-y-auto rounded-t-[28px] border-0 bg-white px-[18px] pt-2.5 pb-[calc(env(safe-area-inset-bottom)+20px)] text-lapangan-teks',
          className,
        )}
      >
        <div aria-hidden className="mx-auto mb-3.5 h-[5px] w-11 shrink-0 rounded-full bg-lapangan-garis" />
        <div className="flex items-start gap-3">
          <div className="min-w-0 flex-1">
            <SheetTitle className="text-xl leading-tight font-bold tracking-[-0.01em] text-lapangan-teks">
              {judul}
            </SheetTitle>
            {deskripsi ? (
              <SheetDescription className="mt-0.5 text-[13px] text-lapangan-teks-3">
                {deskripsi}
              </SheetDescription>
            ) : (
              <SheetDescription className="sr-only">Lembar bawah</SheetDescription>
            )}
          </div>
          {!tanpaTombolTutup && (
            <SheetClose
              className="-mt-1 flex size-11 shrink-0 items-center justify-center rounded-full bg-lapangan-latar text-lapangan-teks-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
              aria-label="Tutup"
            >
              <X aria-hidden className="size-5" />
            </SheetClose>
          )}
        </div>
        <div className="mt-4 flex flex-col gap-3.5">{children}</div>
        {kaki && <div className="mt-[18px] flex gap-2.5">{kaki}</div>}
      </SheetContent>
    </Sheet>
  );
}
