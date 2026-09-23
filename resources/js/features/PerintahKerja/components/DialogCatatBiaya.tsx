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
import { DatePicker } from '@/components/ui/date-picker';
import type { PerintahKerja } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { tanggalHariIni } from '@/lib/waktu';

export function DialogCatatBiaya({
  perintahKerja,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    JenisBiaya: 'Vendor',
    Deskripsi: '',
    Jumlah: 0,
    MataUang: 'IDR',
    TanggalBiaya: tanggalHariIni(),
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.biaya(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Catat Biaya
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Catat Biaya Pekerjaan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="JenisBiaya">Jenis Biaya</Label>
              <Select value={form.data.JenisBiaya} onValueChange={(val) => form.setData('JenisBiaya', val)}>
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Vendor" className="cursor-pointer">
                    Jasa Vendor / Eksternal
                  </SelectItem>
                  <SelectItem value="TenagaKerja" className="cursor-pointer">
                    Tenaga Kerja
                  </SelectItem>
                  <SelectItem value="Sparepart" className="cursor-pointer">
                    Suku Cadang
                  </SelectItem>
                  <SelectItem value="Lainnya" className="cursor-pointer">
                    Biaya Lainnya
                  </SelectItem>
                </SelectContent>
              </Select>
              {form.errors.JenisBiaya && <p className="text-sm text-destructive">{form.errors.JenisBiaya}</p>}
            </div>

            <div className="space-y-1.5">
              <Label nama="Deskripsi">Deskripsi Biaya</Label>
              <Input
                value={form.data.Deskripsi}
                onChange={(e) => form.setData('Deskripsi', e.target.value)}
                placeholder="Contoh: Jasa teknisi rewinding motor dinamo"
              />
              {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Jumlah">Nominal (IDR)</Label>
                <Input
                  type="number"
                  min={0}
                  value={form.data.Jumlah}
                  onChange={(e) => form.setData('Jumlah', Number(e.target.value))}
                />
                {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
              </div>

              <div className="space-y-1.5">
                <Label nama="TanggalBiaya">Tanggal Biaya</Label>
                <DatePicker
                  value={form.data.TanggalBiaya}
                  onChange={(val) => form.setData('TanggalBiaya', val)}
                  placeholder="Pilih tanggal biaya..."
                />
              </div>
            </div>

            <DialogFooter>
              <Button
                type="submit"
                disabled={form.processing || form.data.Jumlah <= 0}
                className="cursor-pointer"
              >
                Simpan Biaya
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
