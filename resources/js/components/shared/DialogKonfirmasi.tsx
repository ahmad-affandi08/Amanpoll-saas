import { type FormEvent, type ReactNode, useEffect, useState } from 'react';
import { AlertDialog as AlertDialogPrimitive } from 'radix-ui';
import { CircleAlert, Info, TriangleAlert, type LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Ilustrasi3d } from '@/components/shared/Ilustrasi3d';
import { cn } from '@/lib/utils';

export type RagamKonfirmasi = 'bahaya' | 'perhatian' | 'info';

export interface OpsiKonfirmasi {
  /** Pertanyaan yang menyebut entitasnya, mis. `Hapus kategori "Pompa"?` (DESIGN.md 22). */
  judul: string;
  /** Dampak tindakan. Wajib diisi untuk tindakan yang tidak dapat dibatalkan. */
  deskripsi?: ReactNode;
  ragam?: RagamKonfirmasi;
  labelAksi?: string;
  labelBatal?: string;
  /** Path ilustrasi 3D; `false` mematikan ilustrasi untuk dialog ini. */
  ilustrasi?: string | false;
  /** Menampilkan isian alasan yang ikut dikembalikan ke pemanggil. */
  alasan?: { label: string; wajib?: boolean; placeholder?: string };
  /** Teks yang harus diketik ulang sebelum tombol aksi aktif, untuk hard delete. */
  ketikUntukKonfirmasi?: string;
}

/** `false` bila dibatalkan; objek berisi alasan bila dikonfirmasi. */
export type HasilKonfirmasi = false | { alasan?: string };

interface TampilanRagam {
  ilustrasi: string;
  ikon: LucideIcon;
  warna: string;
  latar: string;
  varianTombol: 'destructive' | 'default';
}

const RAGAM: Record<RagamKonfirmasi, TampilanRagam> = {
  bahaya: {
    ilustrasi: '/assets/3d/hapus.webp',
    ikon: CircleAlert,
    warna: 'text-bahaya-600',
    latar: 'bg-bahaya-600/10',
    varianTombol: 'destructive',
  },
  perhatian: {
    ilustrasi: '/assets/3d/peringatan.webp',
    ikon: TriangleAlert,
    warna: 'text-safety-600',
    latar: 'bg-safety-500/15',
    varianTombol: 'default',
  },
  info: {
    ilustrasi: '/assets/3d/info.webp',
    ikon: Info,
    warna: 'text-info-600',
    latar: 'bg-info-600/10',
    varianTombol: 'default',
  },
};

interface Props {
  opsi: OpsiKonfirmasi | null;
  onSelesai: (hasil: HasilKonfirmasi) => void;
}

/**
 * Dialog konfirmasi berbasis AlertDialog: fokus awal jatuh ke tombol batal dan
 * dialog tidak tertutup oleh Esc/klik luar, supaya tindakan merusak butuh
 * keputusan yang disengaja.
 */
export function DialogKonfirmasi({ opsi, onSelesai }: Props) {
  const [alasan, setAlasan] = useState('');
  const [ketikan, setKetikan] = useState('');

  useEffect(() => {
    if (opsi) {
      setAlasan('');
      setKetikan('');
    }
  }, [opsi]);

  if (!opsi) return null;

  const ragam = RAGAM[opsi.ragam ?? 'bahaya'];
  const ilustrasi = opsi.ilustrasi === false ? null : (opsi.ilustrasi ?? ragam.ilustrasi);
  const alasanKurang = Boolean(opsi.alasan?.wajib) && alasan.trim() === '';
  const ketikanKurang = Boolean(opsi.ketikUntukKonfirmasi) && ketikan.trim() !== opsi.ketikUntukKonfirmasi;
  const terkunci = alasanKurang || ketikanKurang;

  function konfirmasi(event: FormEvent): void {
    event.preventDefault();
    if (terkunci) return;
    onSelesai(opsi?.alasan ? { alasan: alasan.trim() } : {});
  }

  return (
    <AlertDialogPrimitive.Root open onOpenChange={(terbuka) => !terbuka && onSelesai(false)}>
      <AlertDialogPrimitive.Portal>
        <AlertDialogPrimitive.Overlay className="fixed inset-0 z-50 bg-black/50 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0" />
        <AlertDialogPrimitive.Content
          onEscapeKeyDown={(event) => event.preventDefault()}
          className="fixed top-1/2 left-1/2 z-50 w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 rounded-[10px] border border-border bg-background p-6 shadow-lg duration-200 outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 sm:max-w-md"
        >
          <form onSubmit={konfirmasi} className="space-y-5">
            <div className="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-start sm:text-left">
              {ilustrasi && (
                <div className={cn('flex size-16 items-center justify-center rounded-[9px]', ragam.latar)}>
                  <Ilustrasi3d
                    sumber={ilustrasi}
                    ikonCadangan={ragam.ikon}
                    warnaCadangan={ragam.warna}
                    ukuran={40}
                  />
                </div>
              )}
              <div className="min-w-0 space-y-1.5">
                <AlertDialogPrimitive.Title className="text-base font-semibold text-foreground">
                  {opsi.judul}
                </AlertDialogPrimitive.Title>
                {opsi.deskripsi && (
                  <AlertDialogPrimitive.Description className="text-sm text-muted-foreground">
                    {opsi.deskripsi}
                  </AlertDialogPrimitive.Description>
                )}
              </div>
            </div>

            {opsi.alasan && (
              <div className="space-y-1.5">
                <Label htmlFor="alasan-konfirmasi">
                  {opsi.alasan.label}
                  {opsi.alasan.wajib && <span className="text-destructive"> *</span>}
                </Label>
                <Textarea
                  id="alasan-konfirmasi"
                  rows={3}
                  placeholder={opsi.alasan.placeholder}
                  value={alasan}
                  onChange={(event) => setAlasan(event.target.value)}
                />
              </div>
            )}

            {opsi.ketikUntukKonfirmasi && (
              <div className="space-y-1.5">
                <Label htmlFor="ketikan-konfirmasi">
                  Ketik <span className="font-mono font-medium">{opsi.ketikUntukKonfirmasi}</span> untuk
                  melanjutkan
                </Label>
                <Input
                  id="ketikan-konfirmasi"
                  autoComplete="off"
                  value={ketikan}
                  onChange={(event) => setKetikan(event.target.value)}
                />
              </div>
            )}

            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <AlertDialogPrimitive.Cancel asChild>
                <Button type="button" variant="outline" className="min-h-11 sm:min-h-9">
                  {opsi.labelBatal ?? 'Batal'}
                </Button>
              </AlertDialogPrimitive.Cancel>
              <Button
                type="submit"
                variant={ragam.varianTombol}
                disabled={terkunci}
                className="min-h-11 sm:min-h-9"
              >
                {opsi.labelAksi ?? 'Lanjutkan'}
              </Button>
            </div>
          </form>
        </AlertDialogPrimitive.Content>
      </AlertDialogPrimitive.Portal>
    </AlertDialogPrimitive.Root>
  );
}
