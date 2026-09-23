import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ruteAuth } from '@/features/Auth/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface ResetKataSandiProps {
  penggunaId: string;
  token: string;
}

export default function AuthResetKataSandi({ penggunaId, token }: ResetKataSandiProps) {
  const form = useForm({ KataSandiBaru: '', KataSandiBaru_confirmation: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteAuth.resetKataSandi(penggunaId, token));
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-permukaan-100 p-6">
      <Head title="Reset Kata Sandi" />
      <form
        onSubmit={submit}
        className="w-full max-w-sm space-y-5 rounded-[10px] border border-border bg-card p-6 shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]"
      >
        <KepalaHalaman
          judul="Reset Kata Sandi"
          deskripsi="Masukkan kata sandi baru untuk akun Anda."
          tanpaBreadcrumb
        />
        <div className="space-y-1.5">
          <Label htmlFor="kata-sandi-baru">Kata Sandi Baru</Label>
          <Input
            id="kata-sandi-baru"
            type="password"
            autoComplete="new-password"
            value={form.data.KataSandiBaru}
            onChange={(e) => form.setData('KataSandiBaru', e.target.value)}
          />
          {form.errors.KataSandiBaru && (
            <p className="text-sm text-destructive">{form.errors.KataSandiBaru}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="konfirmasi-kata-sandi">Konfirmasi Kata Sandi Baru</Label>
          <Input
            id="konfirmasi-kata-sandi"
            type="password"
            autoComplete="new-password"
            value={form.data.KataSandiBaru_confirmation}
            onChange={(e) => form.setData('KataSandiBaru_confirmation', e.target.value)}
          />
        </div>
        <Button className="w-full" disabled={form.processing}>
          Reset Kata Sandi
        </Button>
      </form>
    </div>
  );
}
