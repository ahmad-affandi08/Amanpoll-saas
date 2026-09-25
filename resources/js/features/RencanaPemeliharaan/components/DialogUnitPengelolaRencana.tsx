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
import { Combobox } from '@/components/ui/combobox';
import type { RencanaPemeliharaan } from '@/features/PreventifInspeksi/types';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { ruteRencanaPemeliharaan } from '@/features/RencanaPemeliharaan/api';
import { TANPA_PILIHAN, opsiUnitPengelola } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

/**
 * Mengatur unit pengelola rencana preventif (PRD 8.21).
 *
 * Bila rencana belum punya unit pengelola dan seluruh asetnya dikelola unit
 * yang sama, unit itu diisikan sebagai bawaan -- tetapi baru tersimpan saat
 * koordinator menekan Simpan.
 */
export function DialogUnitPengelolaRencana({
  rencana,
  pilihanUnitPengelola,
  saranUnitPengelolaId,
  wajib,
}: {
  rencana: RencanaPemeliharaan;
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  saranUnitPengelolaId: string | null;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const pakaiSaran = !rencana.UnitPengelolaId && saranUnitPengelolaId !== null;
  const nilaiAwal = () => ({
    Nama: rencana.Nama,
    StrategiJadwal: rencana.StrategiJadwal,
    IntervalNilai: rencana.IntervalNilai,
    IntervalSatuan: rencana.IntervalSatuan,
    AmbangMeter: rencana.AmbangMeter ?? null,
    UnitPengelolaId: rencana.UnitPengelolaId ?? saranUnitPengelolaId ?? TANPA_PILIHAN,
  });
  const form = useForm(nilaiAwal());
  const unitSaran = pilihanUnitPengelola.find((unit) => unit.Id === saranUnitPengelolaId);

  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal());
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
    form.put(ruteRencanaPemeliharaan.detail(rencana.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Atur
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Unit Pengelola Rencana</DialogTitle>
          <DialogDescription>
            Tiket preventif mengambil unit pengelola asetnya lebih dulu. Unit di sini dipakai untuk aset yang
            belum punya unit pengelola, dan untuk menyaring daftar rencana.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="UnitPengelolaId">Unit Pengelola</Label>
              <Combobox
                nilai={form.data.UnitPengelolaId}
                onPilih={(val) => form.setData('UnitPengelolaId', val)}
                opsi={opsiUnitPengelola(pilihanUnitPengelola)}
                placeholder="Pilih unit pengelola"
                className="cursor-pointer"
              />
              {pakaiSaran && unitSaran && form.data.UnitPengelolaId === unitSaran.Id && (
                <p className="text-xs text-muted-foreground">
                  Disarankan dari aset: semua aset rencana ini dikelola {unitSaran.Nama}.
                </p>
              )}
              {form.errors.UnitPengelolaId && (
                <p className="text-sm text-destructive">{form.errors.UnitPengelolaId}</p>
              )}
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing} className="cursor-pointer">
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
