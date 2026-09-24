import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { Checkbox } from '@/components/ui/checkbox';
import { ruteAuth } from '@/features/Auth/api';
import {
  BidangIsian,
  IsianAutentikasi,
  IsianKataSandi,
  TAUTAN_AUTENTIKASI,
  TombolKirim,
} from '@/features/Auth/components/IsianAutentikasi';
import { KerangkaAutentikasi } from '@/features/Auth/components/KerangkaAutentikasi';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  durasiHari: number;
  namaPaket: string | null;
  kartuDiminta: boolean;
  penyediaSiapKartu: boolean;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function AuthDaftarTrial({
  durasiHari,
  namaPaket,
  kartuDiminta,
  penyediaSiapKartu,
  wajib,
}: Props) {
  const form = useForm({
    NamaOrganisasi: '',
    Nama: '',
    Email: '',
    Telepon: '',
    KataSandi: '',
    KataSandi_confirmation: '',
    TokenKartu: '',
    Persetujuan: false,
  });

  const terkunci = kartuDiminta && !penyediaSiapKartu;

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.daftar, { onFinish: () => form.reset('KataSandi', 'KataSandi_confirmation') });
  };

  return (
    <KerangkaAutentikasi
      judulTab="Coba Gratis"
      judul={`Coba Amanpoll ${durasiHari} hari`}
      deskripsi={
        namaPaket
          ? `Paket ${namaPaket}, tanpa biaya selama masa percobaan.`
          : 'Tanpa biaya selama masa percobaan.'
      }
      lebar="lebar"
    >
      <AturanWajibProvider aturan={wajib.trial}>
        <form onSubmit={submit} className="space-y-5" noValidate>
          {terkunci ? (
            <p className="rounded-md border border-safety-600/30 bg-safety-500/15 p-3 text-sm text-safety-700">
              Pendaftaran mandiri sedang ditutup karena trial menuntut kartu sementara penyedia pembayaran
              yang aktif belum dapat menerimanya. Hubungi tim penjualan untuk memulai.
            </p>
          ) : null}

          <BidangIsian
            id="nama-organisasi"
            label="Nama organisasi"
            nama="NamaOrganisasi"
            galat={form.errors.NamaOrganisasi}
          >
            {(atribut) => (
              <IsianAutentikasi
                {...atribut}
                value={form.data.NamaOrganisasi}
                onChange={(e) => form.setData('NamaOrganisasi', e.target.value)}
                autoComplete="organization"
                disabled={terkunci}
              />
            )}
          </BidangIsian>

          <div className="grid gap-5 sm:grid-cols-2 sm:gap-4">
            <BidangIsian id="nama" label="Nama Anda" nama="Nama" galat={form.errors.Nama}>
              {(atribut) => (
                <IsianAutentikasi
                  {...atribut}
                  value={form.data.Nama}
                  onChange={(e) => form.setData('Nama', e.target.value)}
                  autoComplete="name"
                  disabled={terkunci}
                />
              )}
            </BidangIsian>

            <BidangIsian id="telepon" label="Telepon (opsional)" nama="Telepon" galat={form.errors.Telepon}>
              {(atribut) => (
                <IsianAutentikasi
                  {...atribut}
                  type="tel"
                  inputMode="tel"
                  value={form.data.Telepon}
                  onChange={(e) => form.setData('Telepon', e.target.value)}
                  autoComplete="tel"
                  disabled={terkunci}
                />
              )}
            </BidangIsian>
          </div>

          <BidangIsian id="email" label="Email kerja" nama="Email" galat={form.errors.Email}>
            {(atribut) => (
              <IsianAutentikasi
                {...atribut}
                type="email"
                inputMode="email"
                value={form.data.Email}
                onChange={(e) => form.setData('Email', e.target.value)}
                autoComplete="email"
                disabled={terkunci}
              />
            )}
          </BidangIsian>

          <div className="grid gap-5 sm:grid-cols-2 sm:gap-4">
            <BidangIsian
              id="kata-sandi"
              label="Kata sandi"
              nama="KataSandi"
              bantuan="Minimal 8 karakter."
              galat={form.errors.KataSandi}
            >
              {(atribut) => (
                <IsianKataSandi
                  {...atribut}
                  autoComplete="new-password"
                  value={form.data.KataSandi}
                  onChange={(e) => form.setData('KataSandi', e.target.value)}
                  disabled={terkunci}
                />
              )}
            </BidangIsian>
            <BidangIsian id="kata-sandi-ulang" label="Ulangi kata sandi" nama="KataSandi_confirmation">
              {(atribut) => (
                <IsianKataSandi
                  {...atribut}
                  autoComplete="new-password"
                  value={form.data.KataSandi_confirmation}
                  onChange={(e) => form.setData('KataSandi_confirmation', e.target.value)}
                  disabled={terkunci}
                />
              )}
            </BidangIsian>
          </div>

          {kartuDiminta && penyediaSiapKartu ? (
            <BidangIsian
              id="token-kartu"
              label="Metode pembayaran"
              nama="TokenKartu"
              bantuan="Kartu tidak ditagih selama masa percobaan."
              galat={form.errors.TokenKartu}
            >
              {(atribut) => (
                <IsianAutentikasi
                  {...atribut}
                  value={form.data.TokenKartu}
                  onChange={(e) => form.setData('TokenKartu', e.target.value)}
                />
              )}
            </BidangIsian>
          ) : null}

          <div className="space-y-1.5">
            <label className="flex cursor-pointer items-start gap-2.5 text-sm text-foreground">
              <Checkbox
                className="mt-0.5"
                checked={form.data.Persetujuan}
                onCheckedChange={(nilai) => form.setData('Persetujuan', nilai === true)}
                disabled={terkunci}
                aria-invalid={form.errors.Persetujuan ? true : undefined}
                aria-describedby={form.errors.Persetujuan ? 'persetujuan-galat' : undefined}
              />
              <span>Saya menyetujui syarat layanan dan kebijakan privasi Amanpoll.</span>
            </label>
            {form.errors.Persetujuan ? (
              <p id="persetujuan-galat" className="text-sm text-destructive">
                {form.errors.Persetujuan}
              </p>
            ) : null}
          </div>

          <TombolKirim
            memproses={form.processing}
            labelMemproses="Menyiapkan workspace..."
            disabled={terkunci}
          >
            Mulai coba gratis
          </TombolKirim>

          <p className="text-center text-sm text-muted-foreground">
            Sudah punya akun?{' '}
            <Link href={ruteAuth.login} className={TAUTAN_AUTENTIKASI}>
              Masuk
            </Link>
          </p>
        </form>
      </AturanWajibProvider>
    </KerangkaAutentikasi>
  );
}
