import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Handshake } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/** Masuk portal partner. */
export default function PartnerLogin() {
  const form = useForm({ Email: '', KataSandi: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post('/masuk', { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Masuk Portal Partner" />
      <form
        onSubmit={kirim}
        className="w-full max-w-sm space-y-5 rounded-[10px] border border-border bg-card p-6 shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]"
      >
        <div className="flex flex-col items-center gap-3 pb-1 text-center">
          <Handshake aria-hidden="true" className="size-12 text-primary" />
          <div>
            <h1 className="text-xl font-semibold text-foreground">Portal Partner</h1>
            <p className="text-sm text-muted-foreground">
              Kirim lead, pantau perjalanannya, dan lihat komisi Anda.
            </p>
          </div>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="email">Email PIC</Label>
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
