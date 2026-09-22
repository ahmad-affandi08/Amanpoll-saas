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
import type { Kontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';
import type { AsetRingkas } from '@/features/Kontrak/types';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

export function DialogTambahAset({
  kontrak,
  aset,
  wajib,
}: {
  kontrak: Kontrak;
  aset: AsetRingkas[];
  wajib: AturanWajib;
}) {
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AsetId">Aset</Label>
              <Combobox
                nilai={form.data.AsetId}
                onPilih={(value) => form.setData('AsetId', value)}
                opsi={opsiDari(aset, (item) => `${item.KodeAset} — ${item.Nama}`)}
                placeholder="Pilih aset"
              />
              {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="MulaiCakupan" htmlFor="MulaiCakupan">
                  Mulai
                </Label>
                <Input
                  id="MulaiCakupan"
                  type="date"
                  value={form.data.MulaiPada}
                  onChange={(event) => form.setData('MulaiPada', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="BerakhirCakupan" htmlFor="BerakhirCakupan">
                  Berakhir
                </Label>
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
              <Label nama="CatatanCakupan" htmlFor="CatatanCakupan">
                Catatan
              </Label>
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
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
