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
  nilai: string;
  onPilih: (nilai: string) => void;
  placeholder?: string;
  placeholderCari?: string;
  pesanKosong?: string;
  disabled?: boolean;
  id?: string;
  className?: string;
  /**
   * Dipasang bila daftarnya dicari di server. Penyaringan internal dimatikan
   * karena `opsi` sudah merupakan hasil saringan; tanpa itu hasil server
   * disaring dua kali dan baris yang cocok lewat kolom lain ikut hilang.
   */
  onCari?: (kueri: string) => void;
  memuat?: boolean;
}

/**
 * Pemilih dengan pencarian, untuk daftar yang tumbuh: aset, suku cadang,
 * lokasi, pengguna, penyedia. Select biasa tetap dipakai untuk pilihan tetap
 * seperti status dan prioritas -- memasang kotak cari di atas tiga pilihan
 * justru menambah satu langkah tanpa menolong siapa pun.
 *
 * "Tidak memilih" tetap diwakili TANPA_PILIHAN seperti pada Select, jadi
 * logika kirim formulir tidak berubah saat sebuah Select ditukar ke sini.
 */
export function Combobox({
  opsi,
  nilai,
  onPilih,
  placeholder = 'Pilih…',
  placeholderCari = 'Cari…',
  pesanKosong = 'Tidak ada yang cocok.',
  disabled = false,
  id,
  className,
  onCari,
  memuat = false,
}: Props) {
  const [terbuka, setTerbuka] = useState(false);
  const [kueri, setKueri] = useState('');
  const kolomCari = useRef<HTMLInputElement>(null);

  const terpilih = opsi.find((o) => o.nilai === nilai) ?? null;

  const tersaring = useMemo(() => {
    if (onCari) return opsi;

    const q = kueri.trim().toLowerCase();
    if (q === '') return opsi;

    return opsi.filter(
      (o) => o.label.toLowerCase().includes(q) || (o.keterangan ?? '').toLowerCase().includes(q),
    );
  }, [opsi, kueri, onCari]);

  const pilih = (opsiNilai: string) => {
    onPilih(opsiNilai);
    setTerbuka(false);
    setKueri('');
  };

  return (
    <Popover
      open={terbuka}
      onOpenChange={(buka) => {
        setTerbuka(buka);
        if (!buka) {
          setKueri('');
        } else {
          onCari?.('');
        }
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
          <ChevronsUpDown aria-hidden="true" className="ms-2 size-4 shrink-0 text-muted-foreground" />
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
              onChange={(e) => {
                setKueri(e.target.value);
                onCari?.(e.target.value);
              }}
              placeholder={placeholderCari}
              className="ps-8"
              aria-label={placeholderCari}
            />
          </div>
        </div>

        <ul role="listbox" className="max-h-64 overflow-y-auto p-1">
          {memuat ? (
            <li className="px-3 py-6 text-center text-sm text-muted-foreground">Mencari…</li>
          ) : tersaring.length === 0 ? (
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
                      'hover:bg-accent focus-visible:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset',
                      aktif && 'bg-accent font-medium',
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
