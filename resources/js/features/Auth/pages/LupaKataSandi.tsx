import { FormEvent } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ruteAuth } from '@/features/Auth/api';
import {
  BidangIsian,
  IsianAutentikasi,
  PesanSukses,
  TAUTAN_AUTENTIKASI,
  TombolKirim,
} from '@/features/Auth/components/IsianAutentikasi';
import { KerangkaAutentikasi } from '@/features/Auth/components/KerangkaAutentikasi';

interface Props {
  /** Durasi trial dari kebijakan langganan (`amanpoll.langganan.hari_uji_coba`). */
  durasiTrialHari: number;
}

export default function AuthLupaKataSandi({ durasiTrialHari }: Props) {
  const { props } = usePage<{ flash: { sukses?: string | null } }>();
  const form = useForm({ Email: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.lupaKataSandi);
  };

  return (
    <KerangkaAutentikasi
      judulTab="Lupa Kata Sandi"
      judul="Lupa kata sandi"
      deskripsi="Masukkan email akun Anda. Bila email itu terdaftar di beberapa organisasi, tautan reset dikirim untuk masing-masing."
      durasiTrialHari={durasiTrialHari}
    >
      <form onSubmit={submit} className="space-y-5" noValidate>
        {props.flash?.sukses ? <PesanSukses>{props.flash.sukses}</PesanSukses> : null}

        <BidangIsian id="email" label="Email" galat={form.errors.Email}>
          {(atribut) => (
            <IsianAutentikasi
              {...atribut}
              type="email"
              inputMode="email"
              autoComplete="email"
              autoFocus
              value={form.data.Email}
              onChange={(e) => form.setData('Email', e.target.value)}
            />
          )}
        </BidangIsian>

        <TombolKirim memproses={form.processing} labelMemproses="Mengirim...">
          Kirim tautan reset
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
