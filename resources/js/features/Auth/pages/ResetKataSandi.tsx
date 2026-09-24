import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ruteAuth } from '@/features/Auth/api';
import {
  BidangIsian,
  IsianKataSandi,
  TAUTAN_AUTENTIKASI,
  TombolKirim,
} from '@/features/Auth/components/IsianAutentikasi';
import { KerangkaAutentikasi } from '@/features/Auth/components/KerangkaAutentikasi';

interface ResetKataSandiProps {
  penggunaId: string;
  token: string;
}

export default function AuthResetKataSandi({ penggunaId, token }: ResetKataSandiProps) {
  const form = useForm({ KataSandiBaru: '', KataSandiBaru_confirmation: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.resetKataSandi(penggunaId, token));
  };

  return (
    <KerangkaAutentikasi
      judulTab="Reset Kata Sandi"
      judul="Buat kata sandi baru"
      deskripsi="Kata sandi baru berlaku untuk akun di organisasi yang disebut pada email reset."
    >
      <form onSubmit={submit} className="space-y-5" noValidate>
        <BidangIsian
          id="kata-sandi-baru"
          label="Kata sandi baru"
          bantuan="Minimal 8 karakter."
          galat={form.errors.KataSandiBaru}
        >
          {(atribut) => (
            <IsianKataSandi
              {...atribut}
              autoComplete="new-password"
              autoFocus
              value={form.data.KataSandiBaru}
              onChange={(e) => form.setData('KataSandiBaru', e.target.value)}
            />
          )}
        </BidangIsian>

        <BidangIsian id="konfirmasi-kata-sandi" label="Ulangi kata sandi baru">
          {(atribut) => (
            <IsianKataSandi
              {...atribut}
              autoComplete="new-password"
              value={form.data.KataSandiBaru_confirmation}
              onChange={(e) => form.setData('KataSandiBaru_confirmation', e.target.value)}
            />
          )}
        </BidangIsian>

        <TombolKirim memproses={form.processing} labelMemproses="Menyimpan...">
          Simpan kata sandi baru
        </TombolKirim>

        <p className="text-center text-sm">
          <Link href={ruteAuth.login} className={`inline-flex items-center gap-1.5 ${TAUTAN_AUTENTIKASI}`}>
            <ArrowLeft className="size-4" aria-hidden="true" />
            Kembali ke halaman masuk
          </Link>
        </p>
      </form>
    </KerangkaAutentikasi>
  );
}
