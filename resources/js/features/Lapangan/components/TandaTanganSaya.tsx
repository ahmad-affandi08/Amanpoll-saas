import { usePage } from '@inertiajs/react';
import { PenLine } from 'lucide-react';
import { useState, type Ref } from 'react';
import { PadTandaTangan, type KendaliPadTandaTangan } from '@/components/shared/PadTandaTangan';
import { ruteTandaTangan } from '@/lib/tanda-tangan';
import type { PropsLapangan } from '@/features/Lapangan/types';

interface PropsTandaTanganSaya {
  /** Kendali pad; `ambilBlob()` mengembalikan `null` bila pad tidak ditampilkan atau kosong. */
  padRef: Ref<KendaliPadTandaTangan>;
  /** Pad terisi atau tidak; tanda tangan tersimpan selalu dianggap siap. */
  onSiap: (siap: boolean) => void;
}

/**
 * "Gambar sekali lalu tersimpan" (PRD 8.22) untuk penerima yang mengonfirmasi dari akunnya.
 *
 * Belum punya tanda tangan tersimpan: tampilkan pad; server menyimpannya ke profil saat
 * konfirmasi dikirim, lalu mencapnya. Sudah punya: tampilkan pratinjau dan konfirmasi
 * cukup satu ketukan; server mencap tanda tangan tersimpan itu. "Ganti" membuka pad
 * untuk menggambar ulang (yang baru ikut tersimpan ke profil).
 */
export function TandaTanganSaya({ padRef, onSiap }: PropsTandaTanganSaya) {
  const { auth } = usePage<PropsLapangan>().props;
  const punya = Boolean(auth.pengguna?.PunyaTandaTangan);
  const [gambarUlang, setGambarUlang] = useState(false);

  if (punya && !gambarUlang) {
    return (
      <div>
        <div className="flex h-28 items-center justify-center rounded-[14px] bg-lapangan-latar/70 px-4 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]">
          <img
            src={ruteTandaTangan.lihat()}
            alt="Tanda tangan tersimpanmu"
            className="max-h-24 max-w-full object-contain"
          />
        </div>
        <div className="mt-1 flex items-center justify-between gap-3">
          <p className="text-[13px] text-lapangan-teks-3">
            Tanda tangan tersimpanmu dicap ke konfirmasi ini.
          </p>
          <button
            type="button"
            onClick={() => {
              setGambarUlang(true);
              onSiap(false);
            }}
            className="inline-flex min-h-11 shrink-0 items-center gap-1.5 text-sm font-bold text-lapangan-oranye-teks"
          >
            <PenLine aria-hidden className="size-4" />
            Ganti
          </button>
        </div>
      </div>
    );
  }

  return (
    <div>
      <PadTandaTangan ref={padRef} varian="lapangan" label="Kotak tanda tanganmu" onBerubah={onSiap} />
      <p className="-mt-1 text-[13px] text-lapangan-teks-3">
        Cukup digambar sekali. Tanda tanganmu tersimpan di akunmu untuk konfirmasi berikutnya.
      </p>
    </div>
  );
}

/** Tanda tangan siap dikirim tanpa menggambar (sudah tersimpan di profil). */
export function useSudahPunyaTandaTangan(): boolean {
  const { auth } = usePage<PropsLapangan>().props;
  return Boolean(auth.pengguna?.PunyaTandaTangan);
}
