import { router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';

import { PadTandaTangan, type KendaliPadTandaTangan } from '@/components/shared/PadTandaTangan';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types/global';
import { hapusTandaTanganProfil, ruteTandaTangan, simpanTandaTanganProfil } from '@/lib/tanda-tangan';

interface PropsKelolaTandaTangan {
  varian?: 'lapangan' | 'dasbor';
}

const GAYA = {
  lapangan: {
    teks: 'text-[13.5px] leading-[1.45] text-lapangan-teks-2',
    pratinjau: 'rounded-[14px] bg-lapangan-latar/60',
    utama:
      'bg-lapangan-oranye-700 text-white shadow-lapangan-oranye rounded-[14px] focus-visible:ring-lapangan-biru-500',
    kedua: 'bg-lapangan-latar text-lapangan-teks rounded-[14px] focus-visible:ring-lapangan-biru-500',
    bahaya: 'text-lapangan-merah-700 focus-visible:ring-lapangan-biru-500',
  },
  dasbor: {
    teks: 'text-sm text-muted-foreground',
    pratinjau: 'rounded-lg bg-muted/40',
    utama: 'bg-primary text-primary-foreground rounded-md focus-visible:ring-ring',
    kedua: 'border border-input bg-background text-foreground rounded-md focus-visible:ring-ring',
    bahaya: 'text-destructive focus-visible:ring-ring',
  },
} as const;

/**
 * Buat, ganti, atau lepas tanda tangan tersimpan milik sendiri (PRD 8.22). Tanda tangan ini
 * dicap ke konfirmasi penerima hanya saat pemiliknya sendiri yang mengonfirmasi.
 */
export function KelolaTandaTangan({ varian = 'dasbor' }: PropsKelolaTandaTangan) {
  const { auth } = usePage<PageProps>().props;
  const punya = Boolean(auth.pengguna?.PunyaTandaTangan);
  const [menggambar, setMenggambar] = useState(!punya);
  const [ada, setAda] = useState(false);
  const [memproses, setMemproses] = useState(false);
  const [versi, setVersi] = useState(() => Date.now());
  const pad = useRef<KendaliPadTandaTangan>(null);
  const gaya = GAYA[varian];

  const muatUlang = () => router.reload({ only: ['auth'] });

  const simpan = async () => {
    const gambar = await pad.current?.ambilBlob();
    if (!gambar) {
      toast.error('Tanda tangani dulu di kotak yang tersedia.');
      return;
    }
    setMemproses(true);
    try {
      await simpanTandaTanganProfil(gambar);
      toast.success('Tanda tangan tersimpan.');
      setVersi(Date.now());
      setMenggambar(false);
      muatUlang();
    } catch {
      toast.error('Tanda tangan belum tersimpan. Coba lagi.');
    } finally {
      setMemproses(false);
    }
  };

  const hapus = async () => {
    setMemproses(true);
    try {
      await hapusTandaTanganProfil();
      toast.success('Tanda tangan dihapus dari profil.');
      setMenggambar(true);
      muatUlang();
    } catch {
      toast.error('Tanda tangan belum terhapus. Coba lagi.');
    } finally {
      setMemproses(false);
    }
  };

  const kelasTombol =
    'inline-flex min-h-11 items-center justify-center px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 disabled:opacity-50';

  return (
    <div className="space-y-3">
      <p className={gaya.teks}>
        {varian === 'lapangan'
          ? 'Dipakai saat kamu mengonfirmasi pekerjaan yang sudah selesai, jadi tidak perlu menggambar ulang setiap kali. Tanda tangan ini hanya tercantum bila kamu sendiri yang mengonfirmasi.'
          : 'Dipakai saat Anda mengonfirmasi pekerjaan yang sudah selesai, jadi tidak perlu menggambar ulang setiap kali. Tanda tangan ini hanya tercantum bila Anda sendiri yang mengonfirmasi.'}
      </p>

      {punya && !menggambar ? (
        <>
          <div className={cn('flex h-32 items-center justify-center p-3', gaya.pratinjau)}>
            <img
              src={ruteTandaTangan.lihat(versi)}
              alt="Tanda tangan tersimpan"
              className="max-h-full max-w-full object-contain"
            />
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <button
              type="button"
              className={cn(kelasTombol, gaya.kedua)}
              onClick={() => setMenggambar(true)}
              disabled={memproses}
            >
              Ganti tanda tangan
            </button>
            <button
              type="button"
              className={cn(kelasTombol, gaya.bahaya)}
              onClick={hapus}
              disabled={memproses}
            >
              Hapus
            </button>
          </div>
        </>
      ) : (
        <>
          <PadTandaTangan ref={pad} label="Kotak tanda tangan profil" varian={varian} onBerubah={setAda} />
          <div className="flex flex-wrap items-center gap-2">
            <button
              type="button"
              className={cn(kelasTombol, gaya.utama)}
              onClick={simpan}
              disabled={!ada || memproses}
            >
              {memproses ? 'Menyimpan…' : 'Simpan tanda tangan'}
            </button>
            {punya && (
              <button
                type="button"
                className={cn(kelasTombol, gaya.kedua)}
                onClick={() => setMenggambar(false)}
                disabled={memproses}
              >
                Batal
              </button>
            )}
          </div>
        </>
      )}
    </div>
  );
}
