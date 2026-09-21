'use client';

import { useMemo, useRef, useState } from 'react';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export interface OpsiCombobox {
  nilai: string;
  label: string;
  /** Teks tambahan yang ikut dicari, mis. kode aset. */
  keterangan?: string;
}

interface Props {
  opsi: OpsiCombobox[];
  nilai: string | null;
  onPilih: (nilai: string | null) => void;
  placeholder?: string;
  placeholderCari?: string;
  pesanKosong?: string;
  /** Mengizinkan pilihan dikosongkan kembali. */
  dapatDikosongkan?: boolean;
  disabled?: boolean;
  id?: string;
  className?: string;
}

/**
 * Pemilih dengan pencarian (DESIGN.md 13).
 *
 * Dipakai menggantikan Select ketika pilihannya banyak — daftar aset atau
 * pengguna tidak dapat ditelusuri dengan menggulir. Pencariannya menyertakan
 * keterangan, sehingga "AST-0042" menemukan asetnya walau penggunanya tidak
 * ingat namanya.
 *
 * Seluruh label dirender sebagai teks React biasa karena isinya berasal dari
 * data tenant, tidak pernah lewat HTML mentah.
 */
export function Combobox({
  opsi,
  nilai,
  onPilih,
  placeholder = 'Pilih…',
  placeholderCari = 'Cari…',
  pesanKosong = 'Tidak ada yang cocok.',
  dapatDikosongkan = false,
  disabled = false,
  id,
  className,
}: Props) {
  const [terbuka, setTerbuka] = useState(false);
  const [kueri, setKueri] = useState('');
  const kolomCari = useRef<HTMLInputElement>(null);

  const terpilih = opsi.find((o) => o.nilai === nilai) ?? null;

  const tersaring = useMemo(() => {
    const q = kueri.trim().toLowerCase();
    if (q === '') return opsi;

    return opsi.filter(
      (o) => o.label.toLowerCase().includes(q) || (o.keterangan ?? '').toLowerCase().includes(q),
    );
  }, [opsi, kueri]);

  const pilih = (opsiNilai: string) => {
    onPilih(dapatDikosongkan && opsiNilai === nilai ? null : opsiNilai);
    setTerbuka(false);
    setKueri('');
  };

  return (
    <Popover
      open={terbuka}
      onOpenChange={(buka) => {
        setTerbuka(buka);
        if (!buka) setKueri('');
      }}
    >
      <PopoverTrigger asChild>
        <Button
          id={id}
          type="button"
          variant="outline"
          role="combobox"
          aria-expanded={terbuka}
          disabled={disabled}
          className={cn(
            'w-full justify-between font-normal',
            !terpilih && 'text-muted-foreground',
            className,
          )}
        >
          <span className="truncate">{terpilih?.label ?? placeholder}</span>
          <ChevronsUpDown aria-hidden="true" className="ms-2 size-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>

      <PopoverContent
        align="start"
        className="w-(--radix-popover-trigger-width) p-0"
        onOpenAutoFocus={(e) => {
          e.preventDefault();
          kolomCari.current?.focus();
        }}
      >
        <div className="border-b border-border p-2">
          <div className="relative">
            <Search
              aria-hidden="true"
              className="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
              ref={kolomCari}
              value={kueri}
              onChange={(e) => setKueri(e.target.value)}
              placeholder={placeholderCari}
              className="ps-8"
              aria-label={placeholderCari}
            />
          </div>
        </div>

        <ul role="listbox" className="max-h-64 overflow-y-auto p-1">
          {tersaring.length === 0 ? (
            <li className="px-3 py-6 text-center text-sm text-muted-foreground">{pesanKosong}</li>
          ) : (
            tersaring.map((o) => {
              const aktif = o.nilai === nilai;

              return (
                <li key={o.nilai}>
                  <button
                    type="button"
                    role="option"
                    aria-selected={aktif}
                    onClick={() => pilih(o.nilai)}
                    className={cn(
                      'flex w-full items-start gap-2 rounded-[5px] px-2 py-2 text-start text-sm',
                      'hover:bg-accent focus-visible:bg-accent focus-visible:outline-none',
                      aktif && 'bg-accent/60',
                    )}
                  >
                    <Check
                      aria-hidden="true"
                      className={cn('mt-0.5 size-4 shrink-0', aktif ? 'opacity-100' : 'opacity-0')}
                    />
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-foreground">{o.label}</span>
                      {o.keterangan && (
                        <span className="block truncate text-xs text-muted-foreground">{o.keterangan}</span>
                      )}
                    </span>
                  </button>
                </li>
              );
            })
          )}
        </ul>
      </PopoverContent>
    </Popover>
  );
}
