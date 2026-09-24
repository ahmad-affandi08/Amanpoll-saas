import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { router } from '@inertiajs/react';
import {
  Box,
  FileSignature,
  LayoutGrid,
  Loader2,
  MessageSquareWarning,
  Package,
  Search,
  Truck,
  Wrench,
  type LucideIcon,
} from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { http } from '@/lib/http';
import { cn } from '@/lib/utils';
import { rutePencarian } from '@/features/Pencarian/api';
import type { HalamanTujuan, KelompokPencarian } from '@/features/Pencarian/types';

const PANJANG_MINIMUM = 2;
const JEDA_KETIK_MS = 250;
const BATAS_HALAMAN = 5;

const IKON_KELOMPOK: Record<string, LucideIcon> = {
  Halaman: LayoutGrid,
  Aset: Box,
  'Perintah Kerja': Wrench,
  Keluhan: MessageSquareWarning,
  'Suku Cadang': Package,
  Penyedia: Truck,
  Kontrak: FileSignature,
};

interface Pilihan {
  kunci: string;
  kelompok: string;
  judul: string;
  keterangan: string;
  url: string;
}

type Keadaan = 'diam' | 'memuat' | 'selesai' | 'gagal';

/** Setiap kata ketikan harus menjadi awal salah satu kata teksnya: "vent" tidak mencocokkan "Preventif". */
function cocokAwalKata(teks: string, kataKetik: string[]): boolean {
  const kataTeks = teks.toLowerCase().split(/[^\p{L}\p{N}]+/u);
  return kataKetik.every((satu) => kataTeks.some((kata) => kata.startsWith(satu)));
}

/** Tombol cari di header beserta dialognya; dibuka juga dengan Ctrl+K / ⌘K atau "/". */
export function PencarianGlobal({ halaman }: { halaman: HalamanTujuan[] }) {
  const [buka, setBuka] = useState(false);
  const [kata, setKata] = useState('');
  const [kelompokServer, setKelompokServer] = useState<KelompokPencarian[]>([]);
  const [keadaan, setKeadaan] = useState<Keadaan>('diam');
  const [aktif, setAktif] = useState(0);
  const idDaftar = useId();
  const daftarRef = useRef<HTMLDivElement>(null);
  const pintasan = useMemo(() => (/Mac|iPhone|iPad/.test(navigator.platform) ? '⌘K' : 'Ctrl K'), []);

  useEffect(() => {
    const tangani = (event: globalThis.KeyboardEvent) => {
      const target = event.target as HTMLElement | null;
      const sedangMengetik =
        target !== null &&
        (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setBuka(true);
      } else if (event.key === '/' && !sedangMengetik) {
        event.preventDefault();
        setBuka(true);
      }
    };

    window.addEventListener('keydown', tangani);
    return () => window.removeEventListener('keydown', tangani);
  }, []);

  const kataBersih = kata.trim();
  const cukupPanjang = kataBersih.length >= PANJANG_MINIMUM;

  useEffect(() => {
    if (!cukupPanjang) {
      setKelompokServer([]);
      setKeadaan('diam');
      return;
    }

    const pembatal = new AbortController();
    setKeadaan('memuat');

    const jeda = window.setTimeout(() => {
      http
        .get<{ kelompok: KelompokPencarian[] }>(rutePencarian.cari, {
          params: { q: kataBersih },
          signal: pembatal.signal,
        })
        .then((respons) => {
          setKelompokServer(respons.data.kelompok);
          setKeadaan('selesai');
        })
        .catch(() => {
          if (!pembatal.signal.aborted) {
            setKeadaan('gagal');
          }
        });
    }, JEDA_KETIK_MS);

    return () => {
      window.clearTimeout(jeda);
      pembatal.abort();
    };
  }, [kataBersih, cukupPanjang]);

  const pilihan = useMemo<Pilihan[]>(() => {
    if (!cukupPanjang) {
      return [];
    }

    const kataKetik = kataBersih.toLowerCase().split(/\s+/);
    const halamanCocok = halaman
      .filter((satu) => cocokAwalKata(`${satu.jalur} ${satu.label}`, kataKetik))
      // Yang cocok pada namanya sendiri didahulukan dari yang cocok hanya karena grup menunya.
      .sort((a, b) => Number(cocokAwalKata(b.label, kataKetik)) - Number(cocokAwalKata(a.label, kataKetik)))
      .slice(0, BATAS_HALAMAN)
      .map((satu) => ({
        kunci: `halaman:${satu.href}`,
        kelompok: 'Halaman',
        judul: satu.label,
        keterangan: satu.jalur,
        url: satu.href,
      }));

    const dariServer = kelompokServer.flatMap((kelompok) =>
      kelompok.Hasil.map((hasil) => ({
        kunci: `${kelompok.Kelompok}:${hasil.Id}`,
        kelompok: kelompok.Kelompok,
        judul: hasil.Judul,
        keterangan: hasil.Keterangan,
        url: hasil.Url,
      })),
    );

    return [...halamanCocok, ...dariServer];
  }, [halaman, kelompokServer, kataBersih, cukupPanjang]);

  useEffect(() => {
    setAktif(0);
  }, [pilihan]);

  useEffect(() => {
    daftarRef.current
      ?.querySelector<HTMLElement>(`[data-indeks="${aktif}"]`)
      ?.scrollIntoView({ block: 'nearest' });
  }, [aktif]);

  const ubahBuka = (terbuka: boolean) => {
    setBuka(terbuka);
    if (!terbuka) {
      setKata('');
    }
  };

  const bukaPilihan = (satu: Pilihan) => {
    ubahBuka(false);
    router.visit(satu.url);
  };

  const tanganiTombol = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'ArrowDown' && pilihan.length > 0) {
      event.preventDefault();
      setAktif((indeks) => (indeks + 1) % pilihan.length);
    } else if (event.key === 'ArrowUp' && pilihan.length > 0) {
      event.preventDefault();
      setAktif((indeks) => (indeks - 1 + pilihan.length) % pilihan.length);
    } else if (event.key === 'Enter' && pilihan[aktif]) {
      event.preventDefault();
      bukaPilihan(pilihan[aktif]);
    }
  };

  const idOpsi = (indeks: number) => `${idDaftar}-opsi-${indeks}`;

  const kelompokTampil = pilihan.reduce<Array<{ kelompok: string; isi: Pilihan[] }>>((hasil, satu) => {
    const terakhir = hasil[hasil.length - 1];
    if (terakhir && terakhir.kelompok === satu.kelompok) {
      terakhir.isi.push(satu);
    } else {
      hasil.push({ kelompok: satu.kelompok, isi: [satu] });
    }
    return hasil;
  }, []);

  return (
    <>
      <button
        type="button"
        onClick={() => setBuka(true)}
        aria-label="Cari"
        aria-keyshortcuts="Control+K Meta+K"
        className="inline-flex size-11 items-center justify-center rounded-[7px] text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:h-9 sm:w-64 sm:justify-start sm:gap-2 sm:border sm:border-input sm:bg-card sm:px-3 sm:text-sm sm:text-muted-foreground sm:hover:border-teknisi-300 sm:hover:bg-card sm:hover:text-foreground lg:w-80"
      >
        <Search className="size-[18px] shrink-0 sm:size-4" strokeWidth={1.75} />
        <span className="hidden flex-1 truncate text-left sm:inline">Cari aset, perintah kerja…</span>
        <kbd className="hidden rounded-[4px] border border-garis-300 bg-permukaan-100 px-1.5 py-0.5 font-mono text-[11px] text-grafit-700 sm:inline">
          {pintasan}
        </kbd>
      </button>

      <Dialog open={buka} onOpenChange={ubahBuka}>
        <DialogContent
          showCloseButton={false}
          className="top-4 translate-y-0 gap-0 overflow-hidden p-0 sm:top-[12vh] sm:max-w-xl"
        >
          <DialogTitle className="sr-only">Pencarian global</DialogTitle>
          <DialogDescription className="sr-only">
            Cari aset, perintah kerja, keluhan, suku cadang, penyedia, kontrak, atau halaman. Gunakan panah
            atas dan bawah untuk memilih, Enter untuk membuka.
          </DialogDescription>

          <div className="flex items-center gap-2 border-b border-border px-4">
            <Search className="size-4 shrink-0 text-grafit-500" strokeWidth={1.75} />
            <input
              autoFocus
              value={kata}
              onChange={(event) => setKata(event.target.value)}
              onKeyDown={tanganiTombol}
              placeholder="Cari kode aset, nomor perintah kerja, nama penyedia…"
              maxLength={100}
              role="combobox"
              aria-expanded={pilihan.length > 0}
              aria-controls={idDaftar}
              aria-autocomplete="list"
              aria-activedescendant={pilihan[aktif] ? idOpsi(aktif) : undefined}
              aria-label="Kata pencarian"
              className="h-12 min-w-0 flex-1 bg-transparent text-base text-foreground outline-none placeholder:text-grafit-500 sm:text-sm"
            />
            {keadaan === 'memuat' && (
              <Loader2 className="size-4 shrink-0 animate-spin text-grafit-500" aria-label="Memuat" />
            )}
          </div>

          <div
            ref={daftarRef}
            id={idDaftar}
            role="listbox"
            aria-label="Hasil pencarian"
            className="max-h-[60vh] overflow-y-auto"
          >
            {!cukupPanjang && (
              <p className="px-4 py-6 text-sm text-muted-foreground">
                Ketik minimal {PANJANG_MINIMUM} huruf untuk mencari aset, perintah kerja, keluhan, suku
                cadang, penyedia, kontrak, atau halaman.
              </p>
            )}

            {cukupPanjang && keadaan === 'gagal' && pilihan.length === 0 && (
              <p className="px-4 py-6 text-sm text-destructive" role="alert">
                Pencarian gagal dimuat. Periksa koneksi lalu coba lagi.
              </p>
            )}

            {cukupPanjang && keadaan === 'selesai' && pilihan.length === 0 && (
              <p className="px-4 py-6 text-sm text-muted-foreground">
                Tidak ada hasil untuk “<span className="font-medium text-foreground">{kataBersih}</span>”.
              </p>
            )}

            {kelompokTampil.map(({ kelompok, isi }) => {
              const Ikon = IKON_KELOMPOK[kelompok] ?? Search;

              return (
                <div key={kelompok} role="group" aria-label={kelompok} className="py-1.5">
                  <p className="px-4 pt-1 pb-1 text-xs font-semibold text-grafit-700">{kelompok}</p>
                  {isi.map((satu) => {
                    const indeks = pilihan.indexOf(satu);
                    const terpilih = indeks === aktif;

                    return (
                      <div
                        key={satu.kunci}
                        id={idOpsi(indeks)}
                        role="option"
                        aria-selected={terpilih}
                        data-indeks={indeks}
                        onMouseMove={() => setAktif(indeks)}
                        onClick={() => bukaPilihan(satu)}
                        className={cn(
                          'mx-1.5 flex min-h-11 cursor-pointer items-center gap-3 rounded-[7px] px-2.5 py-2 sm:min-h-0',
                          terpilih && 'bg-accent text-accent-foreground',
                        )}
                      >
                        <Ikon
                          className="size-4 shrink-0 text-grafit-500"
                          strokeWidth={1.75}
                          aria-hidden="true"
                        />
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-sm font-medium">{satu.judul}</span>
                          {satu.keterangan && (
                            <span className="block truncate text-xs text-muted-foreground">
                              {satu.keterangan}
                            </span>
                          )}
                        </span>
                      </div>
                    );
                  })}
                </div>
              );
            })}
          </div>

          <div className="hidden items-center gap-4 border-t border-border bg-permukaan-100 px-4 py-2 text-xs text-grafit-700 sm:flex">
            <span>
              <kbd className="font-mono">↑</kbd> <kbd className="font-mono">↓</kbd> pilih
            </span>
            <span>
              <kbd className="font-mono">Enter</kbd> buka
            </span>
            <span>
              <kbd className="font-mono">Esc</kbd> tutup
            </span>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
