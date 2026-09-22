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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PerintahKerja, StokOpsi } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

export function DialogReservasiSukuCadang({
  perintahKerja,
  stok,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  stok: StokOpsi[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const [kombinasiPilihan, setKombinasiPilihan] = useState('');
  const form = useForm({
    SukuCadangId: '',
    GudangId: '',
    Jumlah: 1,
    Catatan: '',
  });

  const tanganiPilihStok = (val: string) => {
    setKombinasiPilihan(val);
    const [gudangId, sukuCadangId] = val.split(':');
    form.setData({
      ...form.data,
      GudangId: gudangId,
      SukuCadangId: sukuCadangId,
    });
  };

  const stokTerpilih = stok.find(
    (s) => s.GudangId === form.data.GudangId && s.SukuCadangId === form.data.SukuCadangId,
  );

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.reservasiSukuCadang(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
        setKombinasiPilihan('');
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Reservasi Suku Cadang
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Reservasi Suku Cadang</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label>Pilih Suku Cadang & Gudang</Label>
              <Combobox
                nilai={kombinasiPilihan}
                onPilih={tanganiPilihStok}
                placeholder="Pilih suku cadang tersedia"
                opsi={stok.map((s) => ({
                  nilai: `${s.GudangId}:${s.SukuCadangId}`,
                  label: `${s.KodeSukuCadang} · ${s.NamaSukuCadang}`,
                  keterangan: `${s.NamaGudang} — sisa ${s.TersediaBersih}`,
                }))}
              />
              {form.errors.SukuCadangId && (
                <p className="text-sm text-destructive">{form.errors.SukuCadangId}</p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label nama="Jumlah">Jumlah Dibutuhkan</Label>
              <Input
                type="number"
                min={1}
                max={stokTerpilih?.TersediaBersih ?? 9999}
                value={form.data.Jumlah}
                onChange={(e) => form.setData('Jumlah', Number(e.target.value))}
              />
              {stokTerpilih && (
                <p className="text-xs text-muted-foreground">
                  Tersedia bersih di gudang: {stokTerpilih.TersediaBersih} unit
                </p>
              )}
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>

            <div className="space-y-1.5">
              <Label nama="Catatan">Catatan (Opsional)</Label>
              <Input
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
                placeholder="Misal: untuk penggantian bearing motor A"
              />
            </div>

            <DialogFooter>
              <Button
                type="submit"
                disabled={form.processing || !form.data.SukuCadangId}
                className="cursor-pointer"
              >
                Simpan Reservasi
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
