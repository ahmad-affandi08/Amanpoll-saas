import { FormEvent, useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Lock, Mail } from 'lucide-react';
import { ruteAuth } from '@/features/Auth/api';
import {
  BidangIsian,
  CentangAutentikasi,
  IsianAutentikasi,
  IsianKataSandi,
  KELAS_FORMULIR,
  PesanSukses,
  TAUTAN_AUTENTIKASI,
  TombolKirim,
} from '@/features/Auth/components/IsianAutentikasi';
import { AjakanTrial, KerangkaAutentikasi } from '@/features/Auth/components/KerangkaAutentikasi';
import { salamWaktu } from '@/lib/waktu';

interface Props {
  /** Durasi trial dari kebijakan langganan (`amanpoll.langganan.hari_uji_coba`). */
  durasiTrialHari: number;
}

export default function AuthLogin({ durasiTrialHari }: Props) {
  const { props } = usePage<{ flash: { sukses?: string | null } }>();
  const form = useForm({ Email: '', KataSandi: '', IngatSaya: false });
  // Sapaan menurut jam lokal peramban, dihitung sekali saat halaman dibuka.
  const [sapaan] = useState(() => salamWaktu());

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.login, { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <KerangkaAutentikasi
      judulTab="Masuk"
      ikon="waving_hand"
      judul={sapaan}
      deskripsi="Masuk untuk melihat pekerjaan hari ini."
    >
      <form onSubmit={submit} className={KELAS_FORMULIR} noValidate>
        {props.flash?.sukses ? <PesanSukses>{props.flash.sukses}</PesanSukses> : null}

        <BidangIsian id="email" label="Email" galat={form.errors.Email}>
          {(atribut) => (
            <IsianAutentikasi
              {...atribut}
              ikon={Mail}
              type="email"
              inputMode="email"
              autoComplete="email"
              autoFocus
              value={form.data.Email}
              onChange={(e) => form.setData('Email', e.target.value)}
            />
          )}
        </BidangIsian>

        <BidangIsian id="kata-sandi" label="Kata sandi" galat={form.errors.KataSandi}>
          {(atribut) => (
            <IsianKataSandi
              {...atribut}
              ikon={Lock}
              autoComplete="current-password"
              value={form.data.KataSandi}
              onChange={(e) => form.setData('KataSandi', e.target.value)}
            />
          )}
        </BidangIsian>

        <div className="-mt-1 flex flex-wrap items-center justify-between gap-x-4 sm:-mt-2">
          <label className="flex min-h-11 cursor-pointer items-center gap-2.5 text-[15px] text-lapangan-teks">
            <CentangAutentikasi
              checked={form.data.IngatSaya}
              onCheckedChange={(v) => form.setData('IngatSaya', v === true)}
            />
            Ingat saya
          </label>
          <Link
            href={ruteAuth.lupaKataSandi}
            className={`inline-flex min-h-11 items-center text-[15px] ${TAUTAN_AUTENTIKASI}`}
          >
            Lupa kata sandi?
          </Link>
        </div>

        <TombolKirim memproses={form.processing} labelMemproses="Memeriksa...">
          Masuk
        </TombolKirim>

        {durasiTrialHari > 0 ? (
          <AjakanTrial durasiTrialHari={durasiTrialHari} />
        ) : (
          <p className="pt-1 text-center text-[15px] text-lapangan-teks-2">
            Belum punya akun?{' '}
            <Link href={ruteAuth.daftar} className={TAUTAN_AUTENTIKASI}>
              Daftar
            </Link>
          </p>
        )}
      </form>
    </KerangkaAutentikasi>
  );
}
