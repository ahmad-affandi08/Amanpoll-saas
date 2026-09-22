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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { KepatuhanAset } from '@/features/Kepatuhan/types';
import { ruteKepatuhan } from '@/features/Kepatuhan/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogPemeriksaan({ kewajiban, wajib }: { kewajiban: KepatuhanAset; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Status: 'Patuh',
    TanggalPemeriksaan: new Date().toISOString().slice(0, 10),
    BerlakuSampai: '',
    Catatan: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      BerlakuSampai: data.BerlakuSampai || null,
      Catatan: data.Catatan || null,
    }));
    form.post(ruteKepatuhan.pemeriksaan(kewajiban.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          Periksa
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Pemeriksaan {kewajiban.KodePersyaratan}</DialogTitle>
          <DialogDescription>
            {kewajiban.BuktiYangDiperlukan
              ? `Bukti yang diperlukan: ${kewajiban.BuktiYangDiperlukan}`
              : 'Masa berlaku dihitung otomatis dari interval persyaratan bila dikosongkan.'}
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Status">Hasil</Label>
              <Select value={form.data.Status} onValueChange={(value) => form.setData('Status', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Patuh">Patuh</SelectItem>
                  <SelectItem value="TidakPatuh">Tidak patuh</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TanggalPemeriksaan" htmlFor="TanggalPemeriksaan">
                  Tanggal periksa
                </Label>
                <Input
                  id="TanggalPemeriksaan"
                  type="date"
                  value={form.data.TanggalPemeriksaan}
                  onChange={(event) => form.setData('TanggalPemeriksaan', event.target.value)}
                />
                {form.errors.TanggalPemeriksaan && (
                  <p className="text-sm text-destructive">{form.errors.TanggalPemeriksaan}</p>
                )}
              </div>
              <div className="space-y-1.5">
                <Label nama="BerlakuSampai" htmlFor="BerlakuSampai">
                  Berlaku sampai
                </Label>
                <Input
                  id="BerlakuSampai"
                  type="date"
                  value={form.data.BerlakuSampai}
                  onChange={(event) => form.setData('BerlakuSampai', event.target.value)}
                />
                {form.errors.BerlakuSampai && (
                  <p className="text-sm text-destructive">{form.errors.BerlakuSampai}</p>
                )}
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="CatatanPemeriksaan" htmlFor="CatatanPemeriksaan">
                Catatan
              </Label>
              <Textarea
                id="CatatanPemeriksaan"
                rows={2}
                value={form.data.Catatan}
                onChange={(event) => form.setData('Catatan', event.target.value)}
              />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Hasil
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
