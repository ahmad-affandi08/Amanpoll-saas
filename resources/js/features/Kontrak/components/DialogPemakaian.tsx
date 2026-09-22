import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
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
import type { Kontrak, LayananKontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';

export function DialogPemakaian({ kontrak, layanan }: { kontrak: Kontrak; layanan: LayananKontrak }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Jumlah: '1' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteKontrak.layananPemakaian(kontrak.Id, layanan.Id), {
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
          Catat Pemakaian
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Pemakaian {layanan.Nama}</DialogTitle>
          <DialogDescription>
            Terpakai {layanan.Terpakai}
            {layanan.Kuota ? ` dari kuota ${layanan.Kuota}` : ''} {layanan.Satuan ?? ''}.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="JumlahPemakaian">Jumlah</Label>
            <Input
              id="JumlahPemakaian"
              type="number"
              min="0.0001"
              step="0.0001"
              value={form.data.Jumlah}
              onChange={(event) => form.setData('Jumlah', event.target.value)}
            />
            {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Catat
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
