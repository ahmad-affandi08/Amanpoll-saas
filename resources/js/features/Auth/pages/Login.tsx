import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { LogoLambang } from '@/components/shared/Logo';
import { ruteAuth } from '@/features/Auth/api';

export default function AuthLogin() {
  const form = useForm({ KodeOrganisasi: '', Email: '', KataSandi: '', IngatSaya: false });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.login, { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Masuk" />
      <form
        onSubmit={submit}
        className="w-full max-w-sm space-y-5 rounded-[10px] border border-border bg-card p-6 shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]"
      >
        <div className="flex flex-col items-center gap-3 pb-1 text-center">
          <LogoLambang className="size-14" />
          <div>
            <h1 className="text-xl font-semibold text-foreground">Amanpoll</h1>
            <p className="text-sm text-muted-foreground">Masuk untuk melanjutkan pekerjaan operasional.</p>
          </div>
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="kode-organisasi">Kode Organisasi</Label>
          <Input
            id="kode-organisasi"
            value={form.data.KodeOrganisasi}
            onChange={(e) => form.setData('KodeOrganisasi', e.target.value)}
            autoComplete="organization"
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
        <div className="space-y-1.5">
          <Label htmlFor="kata-sandi">Kata Sandi</Label>
          <Input
            id="kata-sandi"
            type="password"
            autoComplete="current-password"
            value={form.data.KataSandi}
            onChange={(e) => form.setData('KataSandi', e.target.value)}
          />
          {form.errors.KataSandi && <p className="text-sm text-destructive">{form.errors.KataSandi}</p>}
        </div>
        <label className="flex items-center gap-2 text-sm text-foreground">
          <Checkbox
            checked={form.data.IngatSaya}
            onCheckedChange={(v) => form.setData('IngatSaya', v === true)}
          />
          Ingat saya
        </label>
        <Button className="w-full" disabled={form.processing}>
          Masuk
        </Button>
      </form>
    </div>
  );
}
