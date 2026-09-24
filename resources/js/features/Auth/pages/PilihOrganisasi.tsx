import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Building2, ChevronRight, LoaderCircle } from 'lucide-react';
import { ruteAuth } from '@/features/Auth/api';
import { TAUTAN_AUTENTIKASI } from '@/features/Auth/components/IsianAutentikasi';
import { KerangkaAutentikasi } from '@/features/Auth/components/KerangkaAutentikasi';

interface PilihanOrganisasi {
  PenggunaId: string;
  NamaOrganisasi: string;
}

interface Props {
  pilihan: PilihanOrganisasi[];
}

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
    <KerangkaAutentikasi
      judulTab="Pilih Organisasi"
      judul="Pilih organisasi"
      deskripsi="Email Anda terdaftar di beberapa organisasi. Pilih organisasi yang ingin Anda buka."
    >
      <div className="space-y-5">
        {props.errors?.PenggunaId ? (
          <p role="alert" className="text-sm text-destructive">
            {props.errors.PenggunaId}
          </p>
        ) : null}

        <ul className="space-y-2" aria-label="Organisasi Anda">
          {pilihan.map((item) => {
            const sedangDipilih = memproses === item.PenggunaId;

            return (
              <li key={item.PenggunaId}>
                <button
                  type="button"
                  disabled={memproses !== null}
                  aria-busy={sedangDipilih}
                  onClick={() => pilih(item.PenggunaId)}
                  className="flex min-h-14 w-full items-center gap-3 rounded-[7px] border border-border bg-card px-3 py-2.5 text-left transition-colors hover:border-teknisi-300 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 focus-visible:outline-none disabled:cursor-not-allowed disabled:bg-permukaan-50"
                >
                  <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-teknisi-100 text-teknisi-900">
                    <Building2 className="size-[18px]" aria-hidden="true" />
                  </span>
                  <span className="min-w-0 flex-1 text-sm font-medium break-words text-foreground">
                    {item.NamaOrganisasi}
                  </span>
                  {sedangDipilih ? (
                    <LoaderCircle
                      className="size-4 shrink-0 animate-spin text-grafit-500"
                      aria-hidden="true"
                    />
                  ) : (
                    <ChevronRight className="size-4 shrink-0 text-grafit-500" aria-hidden="true" />
                  )}
                </button>
              </li>
            );
          })}
        </ul>

        <p className="text-center text-sm text-muted-foreground">
          Bukan Anda?{' '}
          <button
            type="button"
            className={`cursor-pointer ${TAUTAN_AUTENTIKASI}`}
            onClick={() => router.delete(ruteAuth.pilihOrganisasi)}
          >
            Kembali
          </button>
        </p>
      </div>
    </KerangkaAutentikasi>
  );
}
