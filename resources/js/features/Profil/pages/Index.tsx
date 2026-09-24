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
import { KelolaTandaTangan } from '@/components/shared/KelolaTandaTangan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  pengguna: Pengguna;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function FormProfil({ pengguna, wajib }: { pengguna: Pengguna; wajib: AturanWajib }) {
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label nama="Nama">Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-2">
              <Label nama="Email">Email</Label>
              <Input
                type="email"
                value={form.data.Email}
                onChange={(e) => form.setData('Email', e.target.value)}
              />
              {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
            </div>
            <div className="space-y-2">
              <Label nama="Telepon">Telepon</Label>
              <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
            </div>
            <Button type="submit" disabled={form.processing}>
              Simpan Perubahan
            </Button>
          </form>
        </AturanWajibProvider>
      </CardContent>
    </Card>
  );
}

function FormKataSandi({ wajib }: { wajib: AturanWajib }) {
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label nama="KataSandiLama">Kata Sandi Lama</Label>
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
              <Label nama="KataSandiBaru">Kata Sandi Baru</Label>
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
              <Label nama="KataSandiBaru_confirmation">Konfirmasi Kata Sandi Baru</Label>
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
        </AturanWajibProvider>
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

export default function ProfilIndex({ pengguna, wajib }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title="Profil" />
      <KepalaHalaman
        judul="Profil"
        deskripsi="Kelola data diri, kata sandi, tanda tangan, dan perangkat Anda."
        className="mb-5"
      />
      <div className="grid gap-4 md:grid-cols-2">
        <FormProfil pengguna={pengguna} wajib={wajib.profil} />
        <FormKataSandi wajib={wajib.kataSandi} />
        <Card>
          <CardHeader>
            <CardTitle>Tanda Tangan Saya</CardTitle>
          </CardHeader>
          <CardContent>
            <KelolaTandaTangan varian="dasbor" />
          </CardContent>
        </Card>
        <div className="md:col-span-2">
          <KartuPerangkat pengguna={pengguna} />
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
