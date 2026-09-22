import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
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
import type { Anggaran } from '@/features/Anggaran/types';
import { ruteAnggaran } from '@/features/Anggaran/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogUbahAnggaran({ anggaran, wajib }: { anggaran: Anggaran; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: anggaran.Kode,
    Nama: anggaran.Nama,
    Tahun: anggaran.Tahun.toString(),
    MataUang: anggaran.MataUang,
    Jumlah: anggaran.Jumlah,
  });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.put(ruteAnggaran.detail(anggaran.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Pencil /> Ubah
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Anggaran</DialogTitle>
          <DialogDescription>Total tidak dapat diturunkan melewati alokasi pos utama.</DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-1.5">
                <Label nama="Tahun">Tahun</Label>
                <Input
                  type="number"
                  value={form.data.Tahun}
                  onChange={(event) => form.setData('Tahun', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Nama">Nama</Label>
              <Input value={form.data.Nama} onChange={(event) => form.setData('Nama', event.target.value)} />
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-1.5 sm:col-span-2">
                <Label nama="Jumlah">Total</Label>
                <Input
                  type="number"
                  step="0.01"
                  value={form.data.Jumlah}
                  onChange={(event) => form.setData('Jumlah', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="MataUang">Mata Uang</Label>
                <Input
                  maxLength={3}
                  value={form.data.MataUang}
                  onChange={(event) => form.setData('MataUang', event.target.value.toUpperCase())}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Perubahan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
