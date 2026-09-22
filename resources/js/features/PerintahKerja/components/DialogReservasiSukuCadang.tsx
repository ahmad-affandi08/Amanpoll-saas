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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PerintahKerja, StokOpsi } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';

export function DialogReservasiSukuCadang({
  perintahKerja,
  stok,
}: {
  perintahKerja: PerintahKerja;
  stok: StokOpsi[];
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
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Pilih Suku Cadang & Gudang</Label>
            <Select value={kombinasiPilihan} onValueChange={tanganiPilihStok}>
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue placeholder="Pilih suku cadang tersedia" />
              </SelectTrigger>
              <SelectContent>
                {stok.map((s) => (
                  <SelectItem
                    key={`${s.GudangId}:${s.SukuCadangId}`}
                    value={`${s.GudangId}:${s.SukuCadangId}`}
                    className="cursor-pointer"
                  >
                    {s.KodeSukuCadang} · {s.NamaSukuCadang} ({s.NamaGudang} - sisa {s.TersediaBersih})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.SukuCadangId && (
              <p className="text-sm text-destructive">{form.errors.SukuCadangId}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label>Jumlah Dibutuhkan</Label>
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
            <Label>Catatan (Opsional)</Label>
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
      </DialogContent>
    </Dialog>
  );
}
