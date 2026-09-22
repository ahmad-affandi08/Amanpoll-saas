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
import type { PerintahKerja } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogDowntimeAset({
  perintahKerja,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const asetUtama = perintahKerja.Aset[0]?.Id ?? '';

  const form = useForm({
    AsetId: asetUtama,
    Aksi: 'Mulai' as 'Mulai' | 'Selesai',
    Jenis: 'TidakTerencana',
    Alasan: '',
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.waktuHenti(perintahKerja.Id), {
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
          Catat Downtime Aset
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Catat Downtime / Penghentian Aset</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AsetId">Aset</Label>
              <Select value={form.data.AsetId} onValueChange={(val) => form.setData('AsetId', val)}>
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {perintahKerja.Aset.map((a) => (
                    <SelectItem key={a.Id} value={a.Id} className="cursor-pointer">
                      {a.KodeAset} · {a.Nama} {a.Utama && '(Utama)'}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label nama="Aksi">Aksi Downtime</Label>
              <Select
                value={form.data.Aksi}
                onValueChange={(val) => form.setData('Aksi', val as 'Mulai' | 'Selesai')}
              >
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Mulai" className="cursor-pointer">
                    Mulai Penghentian Mesin (Downtime Mulai)
                  </SelectItem>
                  <SelectItem value="Selesai" className="cursor-pointer">
                    Akhiri Penghentian Mesin (Mesin Kembali Beroperasi)
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            {form.data.Aksi === 'Mulai' && (
              <div className="space-y-1.5">
                <Label nama="Jenis">Jenis Downtime</Label>
                <Select value={form.data.Jenis} onValueChange={(val) => form.setData('Jenis', val)}>
                  <SelectTrigger className="w-full cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="TidakTerencana" className="cursor-pointer">
                      Tidak Terencana (Breakdown / Kerusakan)
                    </SelectItem>
                    <SelectItem value="Terencana" className="cursor-pointer">
                      Terencana (Overhaul / Servis Rutin)
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            )}

            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan / Keterangan</Label>
              <Input
                value={form.data.Alasan}
                onChange={(e) => form.setData('Alasan', e.target.value)}
                placeholder="Contoh: Bearing macet, overheat, perbaikan elektrikal..."
              />
              {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing} className="cursor-pointer">
                Simpan Downtime
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
