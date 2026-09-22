import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { http } from '@/lib/http';
import type { Penyedia, KontakPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

export function TabKontak({ penyedia }: { penyedia: Penyedia }) {
  const konfirmasi = useKonfirmasi();
  const [data, setData] = useState<KontakPenyedia[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ Nama: '', Jabatan: '', Email: '', Telepon: '', Utama: false });

  const muat = () => {
    setMemuat(true);
    http
      .get(rutePenyedia.kontak(penyedia.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [penyedia.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(rutePenyedia.kontak(penyedia.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  const hapus = async (kontak: KontakPenyedia) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kontak "${kontak.Nama}"?`,
        deskripsi: 'Kontak tidak lagi muncul sebagai tujuan korespondensi penyedia.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(rutePenyedia.kontakDetail(kontak.Id), { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && <p className="text-sm text-muted-foreground">Belum ada kontak.</p>}
      <div className="space-y-2">
        {data.map((k) => (
          <div
            key={k.Id}
            className="flex items-center justify-between rounded-md border border-border px-3 py-2"
          >
            <div>
              <div className="flex items-center gap-2">
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                {k.Utama && <Badge variant="default">Utama</Badge>}
              </div>
              <div className="text-xs text-muted-foreground">
                {[k.Jabatan, k.Email, k.Telepon].filter(Boolean).join(' · ')}
              </div>
            </div>
            <Button variant="ghost" size="sm" onClick={() => hapus(k)}>
              Hapus
            </Button>
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="grid grid-cols-2 gap-2">
          <Input
            placeholder="Nama"
            value={form.data.Nama}
            onChange={(e) => form.setData('Nama', e.target.value)}
          />
          <Input
            placeholder="Jabatan"
            value={form.data.Jabatan}
            onChange={(e) => form.setData('Jabatan', e.target.value)}
          />
        </div>
        <div className="grid grid-cols-2 gap-2">
          <Input
            placeholder="Email"
            value={form.data.Email}
            onChange={(e) => form.setData('Email', e.target.value)}
          />
          <Input
            placeholder="Telepon"
            value={form.data.Telepon}
            onChange={(e) => form.setData('Telepon', e.target.value)}
          />
        </div>
        <label className="flex items-center gap-2 text-sm">
          <Checkbox checked={form.data.Utama} onCheckedChange={(v) => form.setData('Utama', v === true)} />
          Jadikan kontak utama
        </label>
        <Button type="submit" disabled={form.processing}>
          Tambah Kontak
        </Button>
      </form>
    </div>
  );
}
