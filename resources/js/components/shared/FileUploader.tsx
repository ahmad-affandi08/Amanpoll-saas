'use client';

import { type DragEvent, useId, useRef, useState } from 'react';
import { FileUp, Paperclip, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Props {
  berkas: File[];
  onUbah: (berkas: File[]) => void;
  /** Atribut accept, mis. "image/*,.pdf". */
  terima?: string;
  banyak?: boolean;
  /** Batas ukuran per berkas dalam megabyte. */
  maksMb?: number;
  label?: string;
  petunjuk?: string;
  disabled?: boolean;
  className?: string;
}

function ukuranTerbaca(byte: number): string {
  if (byte < 1024) return `${byte} B`;
  if (byte < 1024 * 1024) return `${(byte / 1024).toFixed(0)} KB`;

  return `${(byte / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * Pemilih berkas dengan seret-lepas (DESIGN.md 13).
 *
 * `<input type="file">` yang sesungguhnya hanya disembunyikan secara visual
 * agar papan ketik dan pembaca layar tetap bekerja; seret-lepas hanya kemudahan
 * tambahan. Batas ukuran di sini sekadar umpan balik awal — server yang
 * menegakkannya.
 */
export function FileUploader({
  berkas,
  onUbah,
  terima,
  banyak = false,
  maksMb,
  label = 'Pilih berkas',
  petunjuk,
  disabled = false,
  className,
}: Props) {
  const idInput = useId();
  const input = useRef<HTMLInputElement>(null);
  const [seret, setSeret] = useState(false);
  const [ditolak, setDitolak] = useState<string[]>([]);

  const terapkan = (masuk: FileList | null) => {
    if (!masuk) return;

    const diterima: File[] = [];
    const tolak: string[] = [];

    for (const f of Array.from(masuk)) {
      if (maksMb !== undefined && f.size > maksMb * 1024 * 1024) {
        tolak.push(`${f.name} melebihi ${maksMb} MB`);
        continue;
      }
      diterima.push(f);
    }

    setDitolak(tolak);
    onUbah(banyak ? [...berkas, ...diterima] : diterima.slice(0, 1));

    // Input direset supaya berkas yang sama dapat dipilih lagi setelah dihapus.
    if (input.current) input.current.value = '';
  };

  const jatuhkan = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setSeret(false);
    if (!disabled) terapkan(e.dataTransfer.files);
  };

  const hapus = (indeks: number) => {
    onUbah(berkas.filter((_, i) => i !== indeks));
  };

  return (
    <div className={cn('space-y-2', className)}>
      <div
        onDragOver={(e) => {
          e.preventDefault();
          if (!disabled) setSeret(true);
        }}
        onDragLeave={() => setSeret(false)}
        onDrop={jatuhkan}
        className={cn(
          'rounded-[8px] border border-dashed border-input px-4 py-6 text-center transition-colors',
          seret && 'border-primary bg-primary/5',
          disabled && 'opacity-60',
        )}
      >
        <FileUp aria-hidden="true" className="mx-auto mb-2 size-6 text-muted-foreground" />

        <input
          ref={input}
          id={idInput}
          type="file"
          accept={terima}
          multiple={banyak}
          disabled={disabled}
          onChange={(e) => terapkan(e.target.files)}
          className="sr-only"
        />

        <label
          htmlFor={idInput}
          className={cn(
            'inline-flex h-9 cursor-pointer items-center gap-2 rounded-[5px] border border-input bg-background px-3 text-sm font-medium',
            'hover:bg-accent focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-ring',
            disabled && 'pointer-events-none',
          )}
        >
          <Paperclip aria-hidden="true" className="size-4" />
          {label}
        </label>

        <p className="mt-2 text-xs text-muted-foreground">
          {petunjuk ?? 'Seret berkas ke sini, atau pilih dari perangkat Anda.'}
          {maksMb !== undefined && ` Maksimal ${maksMb} MB per berkas.`}
        </p>
      </div>

      {ditolak.length > 0 && (
        <ul role="alert" className="space-y-0.5 text-xs text-destructive">
          {ditolak.map((pesan) => (
            <li key={pesan}>{pesan}</li>
          ))}
        </ul>
      )}

      {berkas.length > 0 && (
        <ul className="space-y-1.5">
          {berkas.map((f, i) => (
            <li
              key={`${f.name}-${i}`}
              className="flex items-center gap-2 rounded-[5px] border border-border bg-card px-3 py-2 text-sm"
            >
              <Paperclip aria-hidden="true" className="size-4 shrink-0 text-muted-foreground" />
              <span className="min-w-0 flex-1 truncate text-foreground">{f.name}</span>
              <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                {ukuranTerbaca(f.size)}
              </span>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                disabled={disabled}
                onClick={() => hapus(i)}
                className="size-7 shrink-0 p-0"
              >
                <X aria-hidden="true" className="size-4" />
                <span className="sr-only">Hapus {f.name}</span>
              </Button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
