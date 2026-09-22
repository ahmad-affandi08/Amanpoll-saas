import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { Pengguna } from '@/features/Pengguna/types';
import { ruteProfil } from '@/features/Profil/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface Props {
  pengguna: Pengguna;
}

function FormProfil({ pengguna }: { pengguna: Pengguna }) {
  const form = useForm({ Nama: pengguna.Nama, Email: pengguna.Email, Telepon: pengguna.Telepon ?? '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(ruteProfil.index);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Data Diri</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <div className="space-y-2">
            <Label>Email</Label>
            <Input
              type="email"
              value={form.data.Email}
              onChange={(e) => form.setData('Email', e.target.value)}
            />
            {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
          </div>
          <div className="space-y-2">
            <Label>Telepon</Label>
            <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
          </div>
          <Button type="submit" disabled={form.processing}>
            Simpan Perubahan
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

function FormKataSandi() {
  const form = useForm({ KataSandiLama: '', KataSandiBaru: '', KataSandiBaru_confirmation: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(ruteProfil.kataSandi, { onSuccess: () => form.reset() });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Ganti Kata Sandi</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Kata Sandi Lama</Label>
            <Input
              type="password"
              value={form.data.KataSandiLama}
              onChange={(e) => form.setData('KataSandiLama', e.target.value)}
            />
            {form.errors.KataSandiLama && (
              <p className="text-sm text-destructive">{form.errors.KataSandiLama}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label>Kata Sandi Baru</Label>
            <Input
              type="password"
              value={form.data.KataSandiBaru}
              onChange={(e) => form.setData('KataSandiBaru', e.target.value)}
            />
            {form.errors.KataSandiBaru && (
              <p className="text-sm text-destructive">{form.errors.KataSandiBaru}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label>Konfirmasi Kata Sandi Baru</Label>
            <Input
              type="password"
              value={form.data.KataSandiBaru_confirmation}
              onChange={(e) => form.setData('KataSandiBaru_confirmation', e.target.value)}
            />
          </div>
          <Button type="submit" disabled={form.processing}>
            Ganti Kata Sandi
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

function KartuPerangkat({ pengguna }: { pengguna: Pengguna }) {
  const hapus = (perangkatId: string) => {
    router.delete(ruteProfil.perangkatDetail(perangkatId), { preserveScroll: true });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Perangkat Aktif</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {pengguna.Perangkat.length === 0 && (
          <p className="text-sm text-muted-foreground">Belum ada perangkat tercatat.</p>
        )}
        {pengguna.Perangkat.map((perangkat) => (
          <div
            key={perangkat.Id}
            className="flex items-center justify-between rounded-md border border-border px-3 py-2"
          >
            <div>
              <div className="text-sm font-medium text-foreground">
                {perangkat.NamaPerangkat ?? 'Perangkat tanpa nama'}
              </div>
              <div className="text-xs text-muted-foreground">
                {perangkat.Platform ?? '—'}{' '}
                <Badge variant="outline" className="ml-2">
                  {perangkat.Status}
                </Badge>
              </div>
            </div>
            <Button variant="ghost" size="sm" onClick={() => hapus(perangkat.Id)}>
              Hapus
            </Button>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

export default function ProfilIndex({ pengguna }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title="Profil" />
      <KepalaHalaman judul="Profil" deskripsi="Kelola data diri, kata sandi, dan perangkat Anda." />
      <div className="grid gap-4 md:grid-cols-2">
        <FormProfil pengguna={pengguna} />
        <FormKataSandi />
        <div className="md:col-span-2">
          <KartuPerangkat pengguna={pengguna} />
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
