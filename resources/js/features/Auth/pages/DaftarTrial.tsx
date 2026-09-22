import { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LogoLambang } from '@/components/shared/Logo';

interface Props {
  durasiHari: number;
  namaPaket: string | null;
  kartuDiminta: boolean;
  penyediaSiapKartu: boolean;
}

export default function AuthDaftarTrial({ durasiHari, namaPaket, kartuDiminta, penyediaSiapKartu }: Props) {
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
    form.post('/daftar', { onFinish: () => form.reset('KataSandi', 'KataSandi_confirmation') });
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Coba Gratis" />
      <form
        onSubmit={submit}
        className="w-full max-w-md space-y-5 rounded-[10px] border border-border bg-card p-6 shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]"
      >
        <div className="flex flex-col items-center gap-3 pb-1 text-center">
          <LogoLambang className="size-14" />
          <div>
            <h1 className="text-xl font-semibold text-foreground">Coba Amanpoll {durasiHari} hari</h1>
            <p className="text-sm text-muted-foreground">
              {namaPaket ? `Paket ${namaPaket}, tanpa biaya selama masa percobaan.` : 'Tanpa biaya selama masa percobaan.'}
            </p>
          </div>
        </div>

        {terkunci ? (
          <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            Pendaftaran mandiri sedang ditutup karena trial menuntut kartu sementara penyedia pembayaran
            yang aktif belum dapat menerimanya. Hubungi tim penjualan untuk memulai.
          </p>
        ) : null}

        <div className="space-y-1.5">
          <Label htmlFor="nama-organisasi">Nama Organisasi</Label>
          <Input
            id="nama-organisasi"
            value={form.data.NamaOrganisasi}
            onChange={(e) => form.setData('NamaOrganisasi', e.target.value)}
            autoComplete="organization"
            disabled={terkunci}
          />
          {form.errors.NamaOrganisasi && (
            <p className="text-sm text-destructive">{form.errors.NamaOrganisasi}</p>
          )}
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="nama">Nama Anda</Label>
          <Input
            id="nama"
            value={form.data.Nama}
            onChange={(e) => form.setData('Nama', e.target.value)}
            autoComplete="name"
            disabled={terkunci}
          />
          {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="email">Email</Label>
          <Input
            id="email"
            type="email"
            value={form.data.Email}
            onChange={(e) => form.setData('Email', e.target.value)}
            autoComplete="email"
            disabled={terkunci}
          />
          {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="telepon">Telepon (opsional)</Label>
          <Input
            id="telepon"
            type="tel"
            value={form.data.Telepon}
            onChange={(e) => form.setData('Telepon', e.target.value)}
            autoComplete="tel"
            disabled={terkunci}
          />
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label htmlFor="kata-sandi">Kata Sandi</Label>
            <Input
              id="kata-sandi"
              type="password"
              autoComplete="new-password"
              value={form.data.KataSandi}
              onChange={(e) => form.setData('KataSandi', e.target.value)}
              disabled={terkunci}
            />
            {form.errors.KataSandi && <p className="text-sm text-destructive">{form.errors.KataSandi}</p>}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="kata-sandi-ulang">Ulangi</Label>
            <Input
              id="kata-sandi-ulang"
              type="password"
              autoComplete="new-password"
              value={form.data.KataSandi_confirmation}
              onChange={(e) => form.setData('KataSandi_confirmation', e.target.value)}
              disabled={terkunci}
            />
          </div>
        </div>

        {kartuDiminta && penyediaSiapKartu ? (
          <div className="space-y-1.5">
            <Label htmlFor="token-kartu">Metode Pembayaran</Label>
            <Input
              id="token-kartu"
              value={form.data.TokenKartu}
              onChange={(e) => form.setData('TokenKartu', e.target.value)}
            />
            <p className="text-sm text-muted-foreground">
              Kartu tidak ditagih selama masa percobaan.
            </p>
            {form.errors.TokenKartu && <p className="text-sm text-destructive">{form.errors.TokenKartu}</p>}
          </div>
        ) : null}

        <label className="flex items-start gap-2 text-sm">
          <Checkbox
            checked={form.data.Persetujuan}
            onCheckedChange={(nilai) => form.setData('Persetujuan', nilai === true)}
            disabled={terkunci}
          />
          <span>Saya menyetujui syarat layanan dan kebijakan privasi Amanpoll.</span>
        </label>
        {form.errors.Persetujuan && <p className="text-sm text-destructive">{form.errors.Persetujuan}</p>}

        <Button type="submit" className="w-full" disabled={form.processing || terkunci}>
          {form.processing ? 'Menyiapkan workspace...' : 'Mulai Coba Gratis'}
        </Button>

        <p className="text-center text-sm text-muted-foreground">
          Sudah punya akun?{' '}
          <Link href="/login" className="underline">
            Masuk
          </Link>
        </p>
      </form>
    </div>
  );
}
