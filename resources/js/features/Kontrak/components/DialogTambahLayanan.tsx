import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Kontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogTambahLayanan({ kontrak, wajib }: { kontrak: Kontrak; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nama: '', Deskripsi: '', Kuota: '', Satuan: '' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Deskripsi: data.Deskripsi || null,
      Kuota: data.Kuota || null,
      Satuan: data.Satuan || null,
    }));
    form.post(ruteKontrak.layanan(kontrak.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          <Plus /> Tambah Layanan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Layanan Kontrak</DialogTitle>
          <DialogDescription>
            Isi kuota bila layanan dibatasi; pemakaian yang melampaui kuota akan ditolak server.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="NamaLayanan" htmlFor="NamaLayanan">
                Nama Layanan
              </Label>
              <Input
                id="NamaLayanan"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Kuota" htmlFor="Kuota">
                  Kuota
                </Label>
                <Input
                  id="Kuota"
                  type="number"
                  min="0"
                  step="0.0001"
                  value={form.data.Kuota}
                  onChange={(event) => form.setData('Kuota', event.target.value)}
                />
                {form.errors.Kuota && <p className="text-sm text-destructive">{form.errors.Kuota}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="Satuan" htmlFor="Satuan">
                  Satuan
                </Label>
                <Input
                  id="Satuan"
                  placeholder="mis. kunjungan"
                  value={form.data.Satuan}
                  onChange={(event) => form.setData('Satuan', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="DeskripsiLayanan" htmlFor="DeskripsiLayanan">
                Deskripsi
              </Label>
              <Textarea
                id="DeskripsiLayanan"
                rows={2}
                value={form.data.Deskripsi}
                onChange={(event) => form.setData('Deskripsi', event.target.value)}
              />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Tambahkan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
