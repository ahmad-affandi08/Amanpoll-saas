import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { rutePlatform } from '@/features/Platform/api';

/** Masuk konsol platform. */
export default function PlatformLogin() {
  const form = useForm({ Email: '', KataSandi: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePlatform.login, { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Masuk Konsol Platform" />
      <form
        onSubmit={kirim}
        className="w-full max-w-sm space-y-5 rounded-md border border-border bg-card p-5"
      >
        <div className="flex flex-col items-center gap-3 pb-1 text-center">
          <ShieldCheck aria-hidden="true" className="size-8 text-primary" />
          <div>
            <h1 className="text-[15px] font-semibold text-foreground">Konsol Platform</h1>
            <p className="text-sm text-muted-foreground">
              Kelola katalog paket dan langganan seluruh organisasi.
            </p>
          </div>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="email">Email</Label>
          <Input
            id="email"
            type="email"
            autoComplete="username"
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

        <Button type="submit" className="w-full" disabled={form.processing}>
          Masuk
        </Button>
      </form>
    </div>
  );
}
