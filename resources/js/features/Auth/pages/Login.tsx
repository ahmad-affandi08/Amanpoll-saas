import { FormEvent } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Checkbox } from '@/components/ui/checkbox';
import { ruteAuth } from '@/features/Auth/api';
import {
  BidangIsian,
  IsianAutentikasi,
  IsianKataSandi,
  PesanSukses,
  TAUTAN_AUTENTIKASI,
  TombolKirim,
} from '@/features/Auth/components/IsianAutentikasi';
import { KerangkaAutentikasi } from '@/features/Auth/components/KerangkaAutentikasi';
import { isiPanelAutentikasi } from '@/features/Auth/isi';

interface Props {
  /** Durasi trial dari kebijakan langganan (`amanpoll.langganan.hari_uji_coba`). */
  durasiTrialHari: number;
}

export default function AuthLogin({ durasiTrialHari }: Props) {
  const { props } = usePage<{ flash: { sukses?: string | null } }>();
  const form = useForm({ Email: '', KataSandi: '', IngatSaya: false });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.login, { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <KerangkaAutentikasi
      judulTab="Masuk"
      judul="Masuk ke Amanpoll"
      deskripsi="Gunakan email dan kata sandi akun organisasi Anda."
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

        <BidangIsian id="kata-sandi" label="Kata sandi" galat={form.errors.KataSandi}>
          {(atribut) => (
            <IsianKataSandi
              {...atribut}
              autoComplete="current-password"
              value={form.data.KataSandi}
              onChange={(e) => form.setData('KataSandi', e.target.value)}
            />
          )}
        </BidangIsian>

        <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
          <label className="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-foreground sm:min-h-0">
            <Checkbox
              checked={form.data.IngatSaya}
              onCheckedChange={(v) => form.setData('IngatSaya', v === true)}
            />
            Ingat saya
          </label>
          <Link href={ruteAuth.lupaKataSandi} className={`text-sm ${TAUTAN_AUTENTIKASI}`}>
            Lupa kata sandi?
          </Link>
        </div>

        <TombolKirim memproses={form.processing} labelMemproses="Memeriksa...">
          Masuk
        </TombolKirim>

        <p className="text-center text-sm text-muted-foreground">
          Belum punya akun?{' '}
          <Link href={ruteAuth.daftar} className={TAUTAN_AUTENTIKASI}>
            {durasiTrialHari > 0 ? isiPanelAutentikasi.ajakan.tombol(durasiTrialHari) : 'Daftar'}
          </Link>
        </p>
      </form>
    </KerangkaAutentikasi>
  );
}
