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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Kontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';
import type { AsetRingkas } from '@/features/Kontrak/types';

export function DialogTambahAset({ kontrak, aset }: { kontrak: Kontrak; aset: AsetRingkas[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    AsetId: '',
    MulaiPada: kontrak.MulaiPada,
    BerakhirPada: kontrak.BerakhirPada,
    Catatan: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, Catatan: data.Catatan || null }));
    form.post(ruteKontrak.aset(kontrak.Id), {
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
        <Button size="sm" className="min-h-11 sm:min-h-9">
          <Plus /> Tambah Aset
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Cakupan Aset</DialogTitle>
          <DialogDescription>
            Periode cakupan harus berada di dalam periode kontrak {kontrak.MulaiPada} s.d.{' '}
            {kontrak.BerakhirPada}.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Aset</Label>
            <Select value={form.data.AsetId} onValueChange={(value) => form.setData('AsetId', value)}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih aset" />
              </SelectTrigger>
              <SelectContent>
                {aset.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.KodeAset} — {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="MulaiCakupan">Mulai</Label>
              <Input
                id="MulaiCakupan"
                type="date"
                value={form.data.MulaiPada}
                onChange={(event) => form.setData('MulaiPada', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="BerakhirCakupan">Berakhir</Label>
              <Input
                id="BerakhirCakupan"
                type="date"
                value={form.data.BerakhirPada}
                onChange={(event) => form.setData('BerakhirPada', event.target.value)}
              />
              {form.errors.BerakhirPada && (
                <p className="text-sm text-destructive">{form.errors.BerakhirPada}</p>
              )}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="CatatanCakupan">Catatan</Label>
            <Input
              id="CatatanCakupan"
              value={form.data.Catatan}
              onChange={(event) => form.setData('Catatan', event.target.value)}
            />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Tambahkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
