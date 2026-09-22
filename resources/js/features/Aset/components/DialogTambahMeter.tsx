import { FormEvent, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import type { Aset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';

export function DialogTambahMeter({ aset, onSukses }: { aset: Aset; onSukses: () => void }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Nama: '',
    Satuan: '',
    Jenis: 'Kumulatif' as 'Kumulatif' | 'NonKumulatif',
    NilaiAwal: '0',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.meter(aset.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
        onSukses();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm">Tambah Meter</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Meter</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input
              placeholder="Nama (mis. Jam Operasi)"
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
            />
            <Input
              placeholder="Satuan (mis. Jam)"
              value={form.data.Satuan}
              onChange={(e) => form.setData('Satuan', e.target.value)}
            />
          </div>
          <Select
            value={form.data.Jenis}
            onValueChange={(v) => form.setData('Jenis', v as 'Kumulatif' | 'NonKumulatif')}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Kumulatif">Kumulatif (mis. odometer, jam operasi)</SelectItem>
              <SelectItem value="NonKumulatif">Non-Kumulatif (mis. suhu, tekanan)</SelectItem>
            </SelectContent>
          </Select>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
