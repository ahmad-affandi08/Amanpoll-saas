import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function Login() {
  const form = useForm({ KodeOrganisasi: '', Email: '', KataSandi: '', IngatSaya: false });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/login', { onFinish: () => form.reset('KataSandi') });
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-zinc-50 p-6">
      <Head title="Masuk" />
      <form onSubmit={submit} className="w-full max-w-sm space-y-5 rounded-xl border bg-white p-6 shadow-sm">
        <div>
          <h1 className="text-2xl font-semibold">Amanpoll</h1>
          <p className="text-sm text-zinc-500">Masuk ke sistem Asset & Maintenance Management.</p>
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium">Kode Organisasi</label>
          <input className="h-10 w-full rounded-md border px-3" value={form.data.KodeOrganisasi} onChange={(e) => form.setData('KodeOrganisasi', e.target.value)} autoComplete="organization" />
          {form.errors.KodeOrganisasi && <p className="text-sm text-red-600">{form.errors.KodeOrganisasi}</p>}
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium">Email</label>
          <input className="h-10 w-full rounded-md border px-3" type="email" value={form.data.Email} onChange={(e) => form.setData('Email', e.target.value)} />
          {form.errors.Email && <p className="text-sm text-red-600">{form.errors.Email}</p>}
        </div>
        <div className="space-y-2">
          <label className="text-sm font-medium">Kata Sandi</label>
          <input className="h-10 w-full rounded-md border px-3" type="password" value={form.data.KataSandi} onChange={(e) => form.setData('KataSandi', e.target.value)} />
          {form.errors.KataSandi && <p className="text-sm text-red-600">{form.errors.KataSandi}</p>}
        </div>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={form.data.IngatSaya} onChange={(e) => form.setData('IngatSaya', e.target.checked)} />
          Ingat saya
        </label>
        <Button className="w-full" disabled={form.processing}>Masuk</Button>
      </form>
    </div>
  );
}
