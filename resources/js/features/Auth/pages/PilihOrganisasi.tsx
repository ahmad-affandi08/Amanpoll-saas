import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { ChevronRight, LoaderCircle } from 'lucide-react';
import { Ikon3D } from '@/components/shared/Ikon3D';
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
      ikon="office_building"
      judul="Pilih organisasi"
      deskripsi="Email Anda terdaftar di beberapa organisasi. Pilih organisasi yang ingin Anda buka."
    >
      <div className="space-y-4">
        {props.errors?.PenggunaId ? (
          <p role="alert" className="text-sm font-medium text-lapangan-merah-700">
            {props.errors.PenggunaId}
          </p>
        ) : null}

        <ul className="space-y-2.5" aria-label="Organisasi Anda">
          {pilihan.map((item) => {
            const sedangDipilih = memproses === item.PenggunaId;

            return (
              <li key={item.PenggunaId}>
                <button
                  type="button"
                  disabled={memproses !== null}
                  aria-busy={sedangDipilih}
                  onClick={() => pilih(item.PenggunaId)}
                  className="flex min-h-16 w-full items-center gap-3 rounded-2xl border-[1.5px] border-lapangan-garis bg-white px-3 py-2.5 text-left transition-colors hover:border-lapangan-biru-500 hover:bg-lapangan-biru-50 focus-visible:border-lapangan-biru-500 focus-visible:ring-4 focus-visible:ring-lapangan-biru-500/15 focus-visible:outline-none disabled:cursor-not-allowed disabled:bg-lapangan-latar"
                >
                  <span className="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-lapangan-biru-50">
                    <Ikon3D nama="office_building" ukuran={30} className="drop-shadow-none" />
                  </span>
                  <span className="min-w-0 flex-1 text-[15px] font-bold break-words text-lapangan-teks">
                    {item.NamaOrganisasi}
                  </span>
                  {sedangDipilih ? (
                    <LoaderCircle
                      className="size-5 shrink-0 animate-spin text-lapangan-teks-3"
                      aria-hidden="true"
                    />
                  ) : (
                    <ChevronRight className="size-5 shrink-0 text-lapangan-teks-3" aria-hidden="true" />
                  )}
                </button>
              </li>
            );
          })}
        </ul>

        <p className="text-center text-[15px] text-lapangan-teks-2">
          Bukan Anda?{' '}
          <button
            type="button"
            className={`inline-flex min-h-11 cursor-pointer items-center ${TAUTAN_AUTENTIKASI}`}
            onClick={() => router.delete(ruteAuth.pilihOrganisasi)}
          >
            Kembali
          </button>
        </p>
      </div>
    </KerangkaAutentikasi>
  );
}
