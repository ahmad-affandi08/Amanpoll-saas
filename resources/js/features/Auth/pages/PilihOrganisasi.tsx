import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { LogoLambang } from '@/components/shared/Logo';
import { ruteAuth } from '@/features/Auth/api';

interface PilihanOrganisasi {
  PenggunaId: string;
  NamaOrganisasi: string;
}

interface Props {
  pilihan: PilihanOrganisasi[];
}

/** Tautan teks: warna primer supaya terbaca sebagai tautan, garis bawah saat hover, cincin saat fokus. */
const TAUTAN =
  'cursor-pointer rounded-sm text-sm font-medium text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

/**
 * Layar Pilih organisasi (PRD 8.1): tampil hanya sesudah kata sandi terbukti cocok
 * di lebih dari satu organisasi, dan hanya memuat organisasi akun yang cocok itu.
 */
export default function AuthPilihOrganisasi({ pilihan }: Props) {
  const { props } = usePage<{ errors: { PenggunaId?: string } }>();
  const [memproses, setMemproses] = useState<string | null>(null);

  const pilih = (penggunaId: string) => {
    router.post(
      ruteAuth.pilihOrganisasi,
      { PenggunaId: penggunaId },
      { onStart: () => setMemproses(penggunaId), onFinish: () => setMemproses(null) },
    );
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Pilih Organisasi" />
      <div className="w-full max-w-sm space-y-5 rounded-[10px] border border-border bg-card p-6 shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]">
        <div className="flex flex-col items-center gap-3 pb-1 text-center">
          <LogoLambang className="size-14" />
          <div>
            <h1 className="text-xl font-semibold text-foreground">Pilih organisasi</h1>
            <p className="text-sm text-muted-foreground">
              Email Anda terdaftar di beberapa organisasi. Pilih organisasi yang ingin Anda buka.
            </p>
          </div>
        </div>
        {props.errors?.PenggunaId && (
          <p role="alert" className="text-sm text-destructive">
            {props.errors.PenggunaId}
          </p>
        )}
        <ul className="space-y-2">
          {pilihan.map((item) => (
            <li key={item.PenggunaId}>
              <Button
                type="button"
                variant="outline"
                className="h-auto w-full justify-between py-3 text-left sm:h-auto sm:py-3"
                disabled={memproses !== null}
                onClick={() => pilih(item.PenggunaId)}
              >
                <span className="min-w-0 break-words">{item.NamaOrganisasi}</span>
                <ChevronRight className="size-4 shrink-0 text-muted-foreground" aria-hidden />
              </Button>
            </li>
          ))}
        </ul>
        <p className="text-center text-sm text-muted-foreground">
          Bukan Anda?{' '}
          <button type="button" className={TAUTAN} onClick={() => router.delete(ruteAuth.pilihOrganisasi)}>
            Kembali
          </button>
        </p>
      </div>
    </div>
  );
}
