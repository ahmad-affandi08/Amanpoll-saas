import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { ruteKepatuhan } from '@/features/Kepatuhan/api';
import type { AsetRingkas, StandarKepatuhan } from '@/features/Kepatuhan/types';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

export function DialogTugaskan({
  standar,
  aset,
  wajib,
}: {
  standar: StandarKepatuhan[];
  aset: AsetRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', StandarKepatuhanId: '' });
  const standarAktif = standar.filter((item) => item.Aktif && (item.JumlahPersyaratan ?? 0) > 0);

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteKepatuhan.tugaskan, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" className="min-h-11 sm:min-h-9">
          <ShieldCheck /> Tugaskan ke Aset
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Tugaskan Standar</DialogTitle>
          <DialogDescription>
            Seluruh persyaratan pada standar akan menjadi kewajiban kepatuhan aset yang dipilih.
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
            <div className="space-y-1.5">
              <Label nama="StandarKepatuhanId">Standar</Label>
              {standarAktif.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  Belum ada standar aktif yang memiliki persyaratan.
                </p>
              ) : (
                <Combobox
                  nilai={form.data.StandarKepatuhanId}
                  onPilih={(value) => form.setData('StandarKepatuhanId', value)}
                  opsi={opsiDari(
                    standarAktif,
                    (item) => `${item.Kode} — ${item.Nama} (${item.JumlahPersyaratan} persyaratan)`,
                  )}
                  placeholder="Pilih standar"
                />
              )}
              {form.errors.StandarKepatuhanId && (
                <p className="text-sm text-destructive">{form.errors.StandarKepatuhanId}</p>
              )}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing || standarAktif.length === 0}>
                Tugaskan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
