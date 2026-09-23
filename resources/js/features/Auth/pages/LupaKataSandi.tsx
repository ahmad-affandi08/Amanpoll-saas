import { FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ruteAuth } from '@/features/Auth/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

export default function AuthLupaKataSandi() {
  const { props } = usePage<{ flash: { sukses?: string | null } }>();
  const form = useForm({ KodeOrganisasi: '', Email: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.lupaKataSandi);
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Lupa Kata Sandi" />
      <form
        onSubmit={submit}
        className="w-full max-w-sm space-y-5 rounded-[10px] border border-border bg-card p-6 shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]"
      >
        <KepalaHalaman
          judul="Lupa Kata Sandi"
          deskripsi="Masukkan kode organisasi dan email untuk menerima tautan reset kata sandi."
          tanpaBreadcrumb
        />
        {props.flash?.sukses && (
          <p
            role="status"
            className="rounded-md border border-sukses-200 bg-sukses-50 p-3 text-sm text-sukses-700"
          >
            {props.flash.sukses}
          </p>
        )}
        <div className="space-y-1.5">
          <Label htmlFor="kode-organisasi">Kode Organisasi</Label>
          <Input
            id="kode-organisasi"
            autoComplete="organization"
            value={form.data.KodeOrganisasi}
            onChange={(e) => form.setData('KodeOrganisasi', e.target.value)}
          />
          {form.errors.KodeOrganisasi && (
            <p className="text-sm text-destructive">{form.errors.KodeOrganisasi}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="email">Email</Label>
          <Input
            id="email"
            type="email"
            value={form.data.Email}
            onChange={(e) => form.setData('Email', e.target.value)}
          />
          {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
        </div>
        <Button className="w-full" disabled={form.processing}>
          Kirim Tautan Reset
        </Button>
        <a
          href={ruteAuth.login}
          className="block rounded-sm text-center text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
          Kembali ke halaman masuk
        </a>
      </form>
    </div>
  );
}
