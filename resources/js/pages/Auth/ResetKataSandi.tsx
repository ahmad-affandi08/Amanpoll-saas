import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface ResetKataSandiProps {
  penggunaId: string;
  token: string;
}

export default function ResetKataSandi({ penggunaId, token }: ResetKataSandiProps) {
  const form = useForm({ KataSandiBaru: '', KataSandiBaru_confirmation: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/reset-kata-sandi/${penggunaId}/${token}`);
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-6">
      <Head title="Reset Kata Sandi" />
      <form onSubmit={submit} className="w-full max-w-sm space-y-5 rounded-lg border border-border bg-card p-6 shadow-sm">
        <div>
          <h1 className="text-2xl font-semibold text-foreground">Reset Kata Sandi</h1>
          <p className="text-sm text-muted-foreground">Masukkan kata sandi baru untuk akun Anda.</p>
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium text-foreground">Kata Sandi Baru</label>
          <input
            className="h-10 w-full rounded-md border border-input px-3"
            type="password"
            value={form.data.KataSandiBaru}
            onChange={(e) => form.setData('KataSandiBaru', e.target.value)}
          />
          {form.errors.KataSandiBaru && <p className="text-sm text-destructive">{form.errors.KataSandiBaru}</p>}
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium text-foreground">Konfirmasi Kata Sandi Baru</label>
          <input
            className="h-10 w-full rounded-md border border-input px-3"
            type="password"
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
