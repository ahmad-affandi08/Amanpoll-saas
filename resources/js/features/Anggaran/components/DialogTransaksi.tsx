import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { ReceiptText } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import type { JenisTransaksiAnggaran, PosAnggaran } from '@/features/Anggaran/types';
import { ruteAnggaran } from '@/features/Anggaran/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { tanggalHariIni } from '@/lib/waktu';
import { InputUang } from '@/components/shared/InputUang';

export function DialogTransaksi({
  pos,
  mataUang,
  dapatMenyesuaikan,
  wajib,
}: {
  pos: PosAnggaran;
  mataUang: string;
  dapatMenyesuaikan: boolean;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Jenis: 'Komitmen' as JenisTransaksiAnggaran,
    Jumlah: '',
    Tanggal: tanggalHariIni(),
    ReferensiJenis: '',
    ReferensiId: '',
    Keterangan: '',
  });
  const jenis: JenisTransaksiAnggaran[] = [
    'Komitmen',
    'Realisasi',
    'PelepasanKomitmen',
    ...(dapatMenyesuaikan ? ['Penyesuaian' as const] : []),
  ];

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      ReferensiJenis: data.ReferensiJenis || null,
      ReferensiId: data.ReferensiId || null,
      Keterangan: data.Keterangan || null,
    }));
    form.post(ruteAnggaran.posTransaksi(pos.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset('Jumlah', 'ReferensiJenis', 'ReferensiId', 'Keterangan');
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <ReceiptText /> Catat
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Catat Transaksi — {pos.Nama}</DialogTitle>
          <DialogDescription>
            Saldo dihitung ulang dari ledger setelah transaksi tersimpan. Realisasi otomatis melepas komitmen
            yang tersedia.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Jenis">Jenis</Label>
              <Combobox
                nilai={form.data.Jenis}
                onPilih={(value) => form.setData('Jenis', value as JenisTransaksiAnggaran)}
                opsi={jenis.map((item) => ({
                  nilai: item,
                  label:
                    item === 'PelepasanKomitmen'
                      ? 'Pelepasan Komitmen'
                      : item === 'Penyesuaian'
                        ? 'Penyesuaian (izin khusus)'
                        : item,
                }))}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Jumlah" htmlFor={`jumlah-${pos.Id}`}>
                  Jumlah
                </Label>
                <InputUang
                  id={`jumlah-${pos.Id}`}
                  value={form.data.Jumlah}
                  onChange={(nilai) => form.setData('Jumlah', nilai)}
                  mataUang={mataUang}
                  bolehNegatif
                />
                {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="Tanggal" htmlFor={`tanggal-${pos.Id}`}>
                  Tanggal
                </Label>
                <DatePicker
                  value={form.data.Tanggal}
                  onChange={(nilai) => form.setData('Tanggal', nilai)}
                  id={`tanggal-${pos.Id}`}
                />
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="ReferensiJenis">Jenis Referensi</Label>
                <Input
                  value={form.data.ReferensiJenis}
                  onChange={(event) => form.setData('ReferensiJenis', event.target.value)}
                  placeholder="Contoh: RencanaPengadaan"
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="ReferensiId">ID Referensi</Label>
                <Input
                  value={form.data.ReferensiId}
                  onChange={(event) => form.setData('ReferensiId', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Jenis" htmlFor={`keterangan-${pos.Id}`}>
                Keterangan {form.data.Jenis === 'Penyesuaian' && '(wajib)'}
              </Label>
              <Textarea
                id={`keterangan-${pos.Id}`}
                rows={3}
                value={form.data.Keterangan}
                onChange={(event) => form.setData('Keterangan', event.target.value)}
              />
              {form.errors.Keterangan && <p className="text-sm text-destructive">{form.errors.Keterangan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Catat Transaksi
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
