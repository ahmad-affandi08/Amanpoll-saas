import { FormEvent, useState } from 'react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Combobox } from '@/components/ui/combobox';
import type { PerintahKerja } from '@/features/PerintahKerja/types';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { TANPA_PILIHAN, opsiUnitPengelola } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

function nilaiAwal(perintahKerja: PerintahKerja) {
  return { UnitPengelolaId: perintahKerja.UnitPengelolaId ?? TANPA_PILIHAN, Alasan: '' };
}

/**
 * Memindahkan perintah kerja ke antrian unit pengelola lain (PRD 8.21).
 *
 * Server menolak selama masih ada teknisi aktif yang lingkupnya tidak mencakup
 * unit tujuan; penolakannya tampil di isian Unit Pengelola.
 */
export function DialogAlihkanUnitPengelola({
  perintahKerja,
  pilihanUnitPengelola,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(nilaiAwal(perintahKerja));

  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal(perintahKerja));
      form.clearErrors();
    }
    setBuka(terbuka);
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      UnitPengelolaId: data.UnitPengelolaId === TANPA_PILIHAN ? null : data.UnitPengelolaId,
    }));
    form.put(rutePerintahKerja.unitPengelola(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Alihkan
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Alihkan Unit Pengelola</DialogTitle>
          <DialogDescription>
            Pindahkan perintah kerja ini ke antrian bagian lain. Saat ini:{' '}
            {perintahKerja.UnitPengelola?.Nama ?? 'tanpa unit pengelola'}.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="UnitPengelolaId">Unit Pengelola Tujuan</Label>
              <Combobox
                nilai={form.data.UnitPengelolaId}
                onPilih={(val) => form.setData('UnitPengelolaId', val)}
                opsi={opsiUnitPengelola(pilihanUnitPengelola)}
                placeholder="Pilih unit pengelola"
                className="cursor-pointer"
              />
              {form.errors.UnitPengelolaId && (
                <p className="text-sm text-destructive">{form.errors.UnitPengelolaId}</p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan</Label>
              <Textarea
                rows={3}
                value={form.data.Alasan}
                onChange={(e) => form.setData('Alasan', e.target.value)}
                placeholder="Contoh: printer dikelola bagian IT, bukan IPSRS."
              />
              {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing} className="cursor-pointer">
                Alihkan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
