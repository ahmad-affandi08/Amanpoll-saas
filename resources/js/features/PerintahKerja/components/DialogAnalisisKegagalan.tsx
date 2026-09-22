import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { KodeKegagalan, PerintahKerja } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

export function DialogAnalisisKegagalan({
  perintahKerja,
  kodeKegagalan,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  kodeKegagalan: KodeKegagalan[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const analisis = perintahKerja.AnalisisKegagalan;

  const form = useForm({
    KodeMasalahId: analisis?.KodeMasalahId ?? '',
    KodePenyebabId: analisis?.KodePenyebabId ?? '',
    KodeTindakanId: analisis?.KodeTindakanId ?? '',
    AkarMasalah: analisis?.AkarMasalah ?? '',
    TindakanKorektif: analisis?.TindakanKorektif ?? '',
    TindakanPencegahan: analisis?.TindakanPencegahan ?? '',
  });

  const daftarMasalah = kodeKegagalan.filter((k) => k.Jenis === 'Masalah');
  const daftarPenyebab = kodeKegagalan.filter((k) => k.Jenis === 'Penyebab');
  const daftarTindakan = kodeKegagalan.filter((k) => k.Jenis === 'Tindakan');

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KodeMasalahId: data.KodeMasalahId || null,
      KodePenyebabId: data.KodePenyebabId || null,
      KodeTindakanId: data.KodeTindakanId || null,
    }));
    form.put(rutePerintahKerja.analisisKegagalan(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          {analisis ? 'Edit Analisis' : 'Isi Analisis Kegagalan'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Analisis Kegagalan (Problem / Cause / Remedy)</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="KodeMasalahId">Kode Masalah (Problem)</Label>
              <Combobox
                nilai={form.data.KodeMasalahId}
                onPilih={(val) => form.setData('KodeMasalahId', val)}
                opsi={opsiDari(daftarMasalah, (k) => `${k.Kode} · ${k.Nama}`)}
                placeholder="Pilih kode masalah"
                className="cursor-pointer"
              />
            </div>

            <div className="space-y-1.5">
              <Label nama="KodePenyebabId">Kode Penyebab (Cause)</Label>
              <Combobox
                nilai={form.data.KodePenyebabId}
                onPilih={(val) => form.setData('KodePenyebabId', val)}
                opsi={opsiDari(daftarPenyebab, (k) => `${k.Kode} · ${k.Nama}`)}
                placeholder="Pilih kode penyebab"
                className="cursor-pointer"
              />
            </div>

            <div className="space-y-1.5">
              <Label nama="KodeTindakanId">Kode Tindakan (Remedy)</Label>
              <Combobox
                nilai={form.data.KodeTindakanId}
                onPilih={(val) => form.setData('KodeTindakanId', val)}
                opsi={opsiDari(daftarTindakan, (k) => `${k.Kode} · ${k.Nama}`)}
                placeholder="Pilih kode tindakan"
                className="cursor-pointer"
              />
            </div>

            <div className="space-y-1.5">
              <Label nama="AkarMasalah">Akar Masalah (Root Cause)</Label>
              <Textarea
                rows={2}
                value={form.data.AkarMasalah}
                onChange={(e) => form.setData('AkarMasalah', e.target.value)}
                placeholder="Uraian akar penyebab fisik, manusia, atau laten..."
              />
            </div>

            <div className="space-y-1.5">
              <Label nama="TindakanKorektif">Tindakan Korektif</Label>
              <Textarea
                rows={2}
                value={form.data.TindakanKorektif}
                onChange={(e) => form.setData('TindakanKorektif', e.target.value)}
                placeholder="Tindakan yang telah dilakukan untuk memulihkan aset..."
              />
            </div>

            <div className="space-y-1.5">
              <Label nama="TindakanPencegahan">Tindakan Pencegahan (Preventive Action)</Label>
              <Textarea
                rows={2}
                value={form.data.TindakanPencegahan}
                onChange={(e) => form.setData('TindakanPencegahan', e.target.value)}
                placeholder="Rekomendasi inspeksi berkala, penggantian pelumas, atau SOP..."
              />
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing} className="cursor-pointer">
                Simpan Analisis
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
