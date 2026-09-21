import { FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ruteAuth } from '@/features/Auth/api';
import { PageHeader } from '@/components/shared/PageHeader';

export default function LupaKataSandi() {
  const { props } = usePage<{ flash: { sukses?: string | null } }>();
  const form = useForm({ KodeOrganisasi: '', Email: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.lupaKataSandi);
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-6">
      <Head title="Lupa Kata Sandi" />
      <form
        onSubmit={submit}
        className="w-full max-w-sm space-y-5 rounded-lg border border-border bg-card p-6 shadow-sm"
      >
        <PageHeader
          judul="Lupa Kata Sandi"
          deskripsi="Masukkan kode organisasi dan email untuk menerima tautan reset kata sandi."
        />
        {props.flash?.sukses && (
          <p className="rounded-md bg-sukses-600/10 p-3 text-sm text-sukses-600">{props.flash.sukses}</p>
        )}
        <div className="space-y-1.5">
          <Label>Kode Organisasi</Label>
          <Input
            value={form.data.KodeOrganisasi}
            onChange={(e) => form.setData('KodeOrganisasi', e.target.value)}
          />
          {form.errors.KodeOrganisasi && (
            <p className="text-sm text-destructive">{form.errors.KodeOrganisasi}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label>Email</Label>
          <Input
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
          className="block text-center text-sm text-muted-foreground hover:text-foreground"
        >
          Kembali ke halaman masuk
        </a>
      </form>
    </div>
  );
}
