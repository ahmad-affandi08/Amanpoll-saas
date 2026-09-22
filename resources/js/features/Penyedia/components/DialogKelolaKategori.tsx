import { FormEvent, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import type { Penyedia, KategoriPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

export function DialogKelolaKategori({ kategoriPenyedia }: { kategoriPenyedia: KategoriPenyedia[] }) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePenyedia.kategori, { onSuccess: () => form.reset(), preserveScroll: true });
  };

  const hapus = async (kategori: KategoriPenyedia) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kategori "${kategori.Nama}"?`,
        deskripsi: 'Penyedia yang memakai kategori ini kehilangan penandaannya.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(rutePenyedia.kategoriDetail(kategori.Id), { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Kelola Kategori</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kategori Penyedia</DialogTitle>
        </DialogHeader>
        <div className="space-y-2">
          {kategoriPenyedia.map((k) => (
            <div
              key={k.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div>
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                <span className="ml-2 font-mono text-xs text-muted-foreground">{k.Kode}</span>
              </div>
              <Button variant="ghost" size="sm" onClick={() => hapus(k)}>
                Hapus
              </Button>
            </div>
          ))}
          {kategoriPenyedia.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada kategori.</p>
          )}
        </div>
        <form onSubmit={submit} className="flex gap-2 border-t border-border pt-4">
          <Input
            placeholder="Kode"
            value={form.data.Kode}
            onChange={(e) => form.setData('Kode', e.target.value)}
            className="w-28 font-mono"
          />
          <Input
            placeholder="Nama kategori"
            value={form.data.Nama}
            onChange={(e) => form.setData('Nama', e.target.value)}
            className="flex-1"
          />
          <Button type="submit" disabled={form.processing}>
            Tambah
          </Button>
        </form>
      </DialogContent>
    </Dialog>
  );
}
