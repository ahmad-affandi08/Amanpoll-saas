import { type FormEvent, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { FileText } from 'lucide-react';
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
import type { PesananPembelian } from '@/features/PesananPembelian/types';
import { formatUang } from '@/lib/uang';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { DatePicker } from '@/components/ui/date-picker';
import { tanggalHariIni } from '@/lib/waktu';

export function DialogCatatTagihan({ pesanan, wajib }: { pesanan: PesananPembelian; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    NomorTagihan: '',
    TanggalTagihan: tanggalHariIni(),
    JatuhTempo: '',
    Subtotal: '0',
    Pajak: '0',
  });
  const total = useMemo(
    () => Number(form.data.Subtotal) + Number(form.data.Pajak),
    [form.data.Subtotal, form.data.Pajak],
  );

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, JatuhTempo: data.JatuhTempo || null }));
    form.post(ruteTagihanPenyedia.simpanDariPesanan(pesanan.Id), { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          <FileText /> Catat Tagihan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Tagihan Penyedia</DialogTitle>
          <DialogDescription>
            Server menolak tagihan yang melebihi nilai barang yang sudah diterima pada PO ini.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="NomorTagihan" htmlFor="NomorTagihan">
                Nomor Tagihan
              </Label>
              <Input
                id="NomorTagihan"
                value={form.data.NomorTagihan}
                onChange={(event) => form.setData('NomorTagihan', event.target.value)}
              />
              {form.errors.NomorTagihan && (
                <p className="text-sm text-destructive">{form.errors.NomorTagihan}</p>
              )}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TanggalTagihan" htmlFor="TanggalTagihan">
                  Tanggal Tagihan
                </Label>
                <DatePicker
                  value={form.data.TanggalTagihan}
                  onChange={(nilai) => form.setData('TanggalTagihan', nilai)}
                  id="TanggalTagihan"
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="JatuhTempo" htmlFor="JatuhTempo">
                  Jatuh Tempo
                </Label>
                <DatePicker
                  value={form.data.JatuhTempo}
                  onChange={(nilai) => form.setData('JatuhTempo', nilai)}
                  id="JatuhTempo"
                />
                {form.errors.JatuhTempo && (
                  <p className="text-sm text-destructive">{form.errors.JatuhTempo}</p>
                )}
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Subtotal" htmlFor="Subtotal">
                  Subtotal
                </Label>
                <Input
                  id="Subtotal"
                  type="number"
                  min="0"
                  step="0.01"
                  value={form.data.Subtotal}
                  onChange={(event) => form.setData('Subtotal', event.target.value)}
                />
                {form.errors.Subtotal && <p className="text-sm text-destructive">{form.errors.Subtotal}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="Pajak" htmlFor="Pajak">
                  Pajak
                </Label>
                <Input
                  id="Pajak"
                  type="number"
                  min="0"
                  step="0.01"
                  value={form.data.Pajak}
                  onChange={(event) => form.setData('Pajak', event.target.value)}
                />
              </div>
            </div>
            <DialogFooter className="items-center gap-3 sm:justify-between">
              <span className="font-mono text-sm">Total {formatUang(total)}</span>
              <Button type="submit" disabled={form.processing}>
                Simpan Tagihan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
