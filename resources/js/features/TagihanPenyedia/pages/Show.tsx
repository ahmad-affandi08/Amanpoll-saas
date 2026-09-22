import { type FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Banknote } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { LampiranTab } from '@/components/kolaborasi/LampiranTab';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { TagihanPenyedia } from '@/features/TagihanPenyedia/types';
import { formatUang } from '@/lib/uang';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { DatePicker } from '@/components/ui/date-picker';

interface Props {
  tagihan: TagihanPenyedia;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const METODE = ['Transfer', 'Tunai', 'Giro', 'KartuKredit'];
const VARIAN_STATUS = { BelumDibayar: 'perhatian', DibayarSebagian: 'proses', Dibayar: 'sukses' } as const;

function DialogCatatPembayaran({ tagihan, wajib }: { tagihan: Props['tagihan']; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    NomorPembayaran: '',
    TanggalBayar: new Date().toISOString().slice(0, 10),
    Jumlah: tagihan.Sisa,
    Metode: 'Transfer',
    Referensi: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, Referensi: data.Referensi || null }));
    form.post(ruteTagihanPenyedia.bayar(tagihan.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" className="min-h-11 sm:min-h-9">
          <Banknote /> Catat Pembayaran
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Pembayaran Penyedia</DialogTitle>
          <DialogDescription>
            Pembayaran sebagian diperbolehkan; server menolak nilai yang melebihi sisa{' '}
            {formatUang(tagihan.Sisa)}.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="NomorPembayaran" htmlFor="NomorPembayaran">
                Nomor Pembayaran
              </Label>
              <Input
                id="NomorPembayaran"
                value={form.data.NomorPembayaran}
                onChange={(event) => form.setData('NomorPembayaran', event.target.value)}
              />
              {form.errors.NomorPembayaran && (
                <p className="text-sm text-destructive">{form.errors.NomorPembayaran}</p>
              )}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TanggalBayar" htmlFor="TanggalBayar">
                  Tanggal Bayar
                </Label>
                <DatePicker
                  value={form.data.TanggalBayar}
                  onChange={(nilai) => form.setData('TanggalBayar', nilai)}
                  id="TanggalBayar"
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="Jumlah" htmlFor="Jumlah">
                  Jumlah
                </Label>
                <Input
                  id="Jumlah"
                  type="number"
                  min="0.01"
                  step="0.01"
                  value={form.data.Jumlah}
                  onChange={(event) => form.setData('Jumlah', event.target.value)}
                />
                {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Metode">Metode</Label>
                <Select value={form.data.Metode} onValueChange={(value) => form.setData('Metode', value)}>
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {METODE.map((item) => (
                      <SelectItem key={item} value={item}>
                        {item}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label nama="Referensi" htmlFor="Referensi">
                  Referensi
                </Label>
                <Input
                  id="Referensi"
                  value={form.data.Referensi}
                  onChange={(event) => form.setData('Referensi', event.target.value)}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Pembayaran
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function TagihanPenyediaShow({ tagihan, wajib }: Props) {
  const pembayaran = tagihan.Pembayaran ?? [];

  return (
    <KerangkaAplikasi>
      <Head title={tagihan.NomorTagihan} />
      <div className="space-y-6">
        <Link
          href={ruteTagihanPenyedia.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali
        </Link>

        <KepalaHalaman
          judul={<span className="font-mono">{tagihan.NomorTagihan}</span>}
          labelBreadcrumb={tagihan.NomorTagihan}
          lencana={<Badge variant={VARIAN_STATUS[tagihan.Status]}>{tagihan.Status}</Badge>}
          deskripsi={
            <>
              {tagihan.NamaPenyedia} ·{' '}
              {tagihan.PesananPembelianId ? (
                <Link
                  className="font-mono hover:text-primary"
                  href={rutePesananPembelian.detail(tagihan.PesananPembelianId)}
                >
                  {tagihan.NomorPesananPembelian ?? 'PO terkait'}
                </Link>
              ) : (
                'Tanpa PO'
              )}
            </>
          }
          aksi={
            tagihan.Status !== 'Dibayar' ? (
              <DialogCatatPembayaran tagihan={tagihan} wajib={wajib.pembayaran} />
            ) : undefined
          }
        />

        <Card>
          <CardHeader>
            <CardTitle>Ringkasan Tagihan</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-4">
            <div>
              <p className="text-xs text-muted-foreground">Subtotal</p>
              <p className="font-mono font-medium">{formatUang(tagihan.Subtotal)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Pajak</p>
              <p className="font-mono font-medium">{formatUang(tagihan.Pajak)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Total</p>
              <p className="font-mono font-medium">{formatUang(tagihan.Total)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Sisa</p>
              <p className="font-mono text-lg font-semibold">{formatUang(tagihan.Sisa)}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Dokumen Tagihan</CardTitle>
          </CardHeader>
          <CardContent>
            <LampiranTab jenisEntitas="TagihanPenyedia" entitasId={tagihan.Id} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Riwayat Pembayaran</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {pembayaran.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada pembayaran."
                deskripsi="Catat pembayaran penuh atau sebagian."
              />
            ) : (
              pembayaran.map((item) => (
                <div
                  key={item.Id}
                  className="flex flex-col gap-2 rounded-[9px] border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-mono font-medium">{item.NomorPembayaran}</p>
                    <p className="text-xs text-muted-foreground">
                      {item.TanggalBayar ?? '-'} · {item.Metode ?? '-'} ·{' '}
                      {item.Referensi ?? 'Tanpa referensi'}
                      {item.NamaPembuat ? ` · ${item.NamaPembuat}` : ''}
                    </p>
                  </div>
                  <strong className="font-mono">{formatUang(item.Jumlah)}</strong>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </KerangkaAplikasi>
  );
}
