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
import type { PerintahKerja, StatusPerintahKerja } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

export function DialogUbahStatus({
  perintahKerja,
  transisi,
  wajib,
  alasanVerifikasiDiblokir = null,
}: {
  perintahKerja: PerintahKerja;
  transisi: StatusPerintahKerja[];
  wajib: AturanWajib;
  /** Verifikasi ke Selesai dikunci setelan "Wajibkan konfirmasi penerima" (PRD 8.22). */
  alasanVerifikasiDiblokir?: string | null;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Status: (transisi[0] ?? perintahKerja.Status) as StatusPerintahKerja,
    Catatan: '',
    RingkasanPenyelesaian: perintahKerja.RingkasanPenyelesaian ?? '',
    Versi: perintahKerja.Versi,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(rutePerintahKerja.status(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  const butuhRingkasan = form.data.Status === 'Selesai' || form.data.Status === 'Ditutup';
  const verifikasiDiblokir = form.data.Status === 'Selesai' && Boolean(alasanVerifikasiDiblokir);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button disabled={transisi.length === 0} className="cursor-pointer">
          Ubah Status
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Ubah Status Perintah Kerja</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Status">Status Baru</Label>
              <Combobox
                nilai={form.data.Status}
                onPilih={(val) => form.setData('Status', val as StatusPerintahKerja)}
                opsi={transisi.map((s) => ({ nilai: s, label: s }))}
                className="cursor-pointer"
              />
              {form.errors.Status && <p className="text-sm text-destructive">{form.errors.Status}</p>}
              {verifikasiDiblokir && (
                <p
                  role="alert"
                  className="rounded-md border border-safety-600/30 bg-safety-500/10 p-2.5 text-sm text-safety-700"
                >
                  {alasanVerifikasiDiblokir}
                </p>
              )}
            </div>

            {butuhRingkasan && (
              <div className="space-y-1.5">
                <Label nama="RingkasanPenyelesaian">Ringkasan Penyelesaian Pekerjaan</Label>
                <Textarea
                  rows={3}
                  value={form.data.RingkasanPenyelesaian}
                  onChange={(e) => form.setData('RingkasanPenyelesaian', e.target.value)}
                  placeholder="Rangkum hasil perbaikan, penggantian komponen, atau pengujian yang dilakukan..."
                />
                {form.errors.RingkasanPenyelesaian && (
                  <p className="text-sm text-destructive">{form.errors.RingkasanPenyelesaian}</p>
                )}
              </div>
            )}

            <div className="space-y-1.5">
              <Label nama="Catatan">Catatan Perubahan Status</Label>
              <Textarea
                rows={3}
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
                placeholder="Catatan opsional mengenai status baru ini..."
              />
              {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
            </div>

            <DialogFooter>
              <Button
                type="submit"
                disabled={form.processing || verifikasiDiblokir}
                className="cursor-pointer"
              >
                Simpan Perubahan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
