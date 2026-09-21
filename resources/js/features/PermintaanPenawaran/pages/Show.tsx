import { type FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ChevronRight, Paperclip, Send } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { LampiranTab } from '@/components/kolaborasi/LampiranTab';
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
import type { PermintaanPenawaran } from '@/features/PermintaanPenawaran/types';
import { formatUang } from '@/lib/uang';
import { rutePermintaanPenawaran } from '@/features/PermintaanPenawaran/api';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  rfq: PermintaanPenawaran;
}

interface BarisPenawaran {
  DetailPermintaanPembelianId: string;
  Jumlah: string;
  HargaSatuan: string;
  Diskon: string;
  Pajak: string;
  WaktuPengirimanHari: string;
}

const VARIAN_STATUS = { Draft: 'netral', Dibuka: 'proses', Ditutup: 'sukses' } as const;
const VARIAN_PENAWARAN = { Diajukan: 'info', Terpilih: 'sukses', Ditolak: 'bahaya' } as const;

function DialogCatatPenawaran({ rfq }: Props) {
  const [buka, setBuka] = useState(false);
  const itemPermintaan = rfq.PermintaanPembelian?.Detail ?? [];
  const form = useForm({
    PenyediaId: '',
    NomorPenawaran: '',
    TanggalPenawaran: new Date().toISOString().slice(0, 10),
    BerlakuSampai: '',
    MataUang: 'IDR',
    Catatan: '',
    Detail: itemPermintaan.map((item): BarisPenawaran => ({
      DetailPermintaanPembelianId: item.Id,
      Jumlah: item.Jumlah,
      HargaSatuan: item.HargaEstimasi ?? '0',
      Diskon: '0',
      Pajak: '0',
      WaktuPengirimanHari: '0',
    })),
  });

  const total = useMemo(
    () =>
      form.data.Detail.reduce(
        (jumlah, baris) =>
          jumlah +
          Number(baris.Jumlah) * Number(baris.HargaSatuan) -
          Number(baris.Diskon) +
          Number(baris.Pajak),
        0,
      ),
    [form.data.Detail],
  );

  function ubahBaris(indeks: number, kolom: keyof BarisPenawaran, nilai: string): void {
    const detail = [...form.data.Detail];
    detail[indeks] = { ...detail[indeks], [kolom]: nilai };
    form.setData('Detail', detail);
  }

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      NomorPenawaran: data.NomorPenawaran || null,
      BerlakuSampai: data.BerlakuSampai || null,
      Catatan: data.Catatan || null,
    }));
    form.post(rutePermintaanPenawaran.penawaran(rfq.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" className="min-h-11 sm:min-h-9">
          Catat Penawaran
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle>Penawaran Penyedia</DialogTitle>
          <DialogDescription>
            Harga, diskon, dan pajak per baris dihitung ulang server sebelum disimpan.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5">
              <Label>Penyedia</Label>
              <Select
                value={form.data.PenyediaId}
                onValueChange={(value) => form.setData('PenyediaId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih penyedia" />
                </SelectTrigger>
                <SelectContent>
                  {(rfq.PenyediaDiundang ?? []).map((item) => (
                    <SelectItem key={item.Id} value={item.PenyediaId}>
                      {item.NamaPenyedia}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.PenyediaId && <p className="text-sm text-destructive">{form.errors.PenyediaId}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="NomorPenawaran">Nomor Penawaran</Label>
              <Input
                id="NomorPenawaran"
                value={form.data.NomorPenawaran}
                onChange={(event) => form.setData('NomorPenawaran', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="TanggalPenawaran">Tanggal</Label>
              <Input
                id="TanggalPenawaran"
                type="date"
                value={form.data.TanggalPenawaran}
                onChange={(event) => form.setData('TanggalPenawaran', event.target.value)}
              />
              {form.errors.TanggalPenawaran && (
                <p className="text-sm text-destructive">{form.errors.TanggalPenawaran}</p>
              )}
            </div>
          </div>

          <div className="space-y-3">
            {itemPermintaan.map((item, indeks) => (
              <div
                key={item.Id}
                className="grid gap-3 rounded-[9px] border border-border p-3 sm:grid-cols-[1fr_repeat(4,7rem)]"
              >
                <div className="min-w-0">
                  <p className="text-sm font-medium">{item.Deskripsi}</p>
                  <p className="text-xs text-muted-foreground">
                    {item.Jumlah} {item.Satuan}
                  </p>
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Harga</Label>
                  <Input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.data.Detail[indeks].HargaSatuan}
                    onChange={(event) => ubahBaris(indeks, 'HargaSatuan', event.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Diskon</Label>
                  <Input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.data.Detail[indeks].Diskon}
                    onChange={(event) => ubahBaris(indeks, 'Diskon', event.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Pajak</Label>
                  <Input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.data.Detail[indeks].Pajak}
                    onChange={(event) => ubahBaris(indeks, 'Pajak', event.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Kirim (hari)</Label>
                  <Input
                    type="number"
                    min="0"
                    value={form.data.Detail[indeks].WaktuPengirimanHari}
                    onChange={(event) => ubahBaris(indeks, 'WaktuPengirimanHari', event.target.value)}
                  />
                </div>
              </div>
            ))}
          </div>

          <DialogFooter className="items-center gap-3 sm:justify-between">
            <span className="font-mono text-sm">Estimasi total {formatUang(total)}</span>
            <Button type="submit" disabled={form.processing}>
              Simpan Penawaran
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PermintaanPenawaranShow({ rfq }: Props) {
  const [memproses, setMemproses] = useState(false);
  const penawaran = rfq.Penawaran ?? [];

  function jalankanAksi(url: string): void {
    router.post(
      url,
      {},
      {
        preserveScroll: true,
        onStart: () => setMemproses(true),
        onFinish: () => setMemproses(false),
      },
    );
  }

  return (
    <AppLayout>
      <Head title={rfq.Nomor} />
      <div className="space-y-6">
        <Link
          href={rutePermintaanPenawaran.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali
        </Link>

        <PageHeader
          judul={<span className="font-mono">{rfq.Nomor}</span>}
          labelBreadcrumb={rfq.Nomor}
          lencana={<Badge variant={VARIAN_STATUS[rfq.Status]}>{rfq.Status}</Badge>}
          deskripsi={
            <>
              Sumber {rfq.PermintaanPembelian?.Nomor ?? '-'} · {(rfq.PenyediaDiundang ?? []).length} penyedia
              diundang
            </>
          }
          aksi={
            <>
              {rfq.Status === 'Draft' && (
                <Button
                  size="sm"
                  className="min-h-11 sm:min-h-9"
                  disabled={memproses}
                  onClick={() => jalankanAksi(rutePermintaanPenawaran.buka(rfq.Id))}
                >
                  <Send /> Buka RFQ
                </Button>
              )}
              {rfq.Status === 'Dibuka' && <DialogCatatPenawaran rfq={rfq} />}
            </>
          }
        />

        <Card>
          <CardHeader>
            <CardTitle>Penyedia Diundang</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-2 sm:grid-cols-2">
            {(rfq.PenyediaDiundang ?? []).map((item) => (
              <div
                key={item.Id}
                className="flex items-center justify-between rounded-[9px] border border-border p-3"
              >
                <span className="text-sm">{item.NamaPenyedia}</span>
                <Badge variant="netral">{item.Status}</Badge>
              </div>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Evaluasi Penawaran</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {penawaran.length === 0 ? (
              <EmptyState
                judul="Belum ada penawaran masuk."
                deskripsi="Catat penawaran penyedia selama RFQ masih dibuka."
              />
            ) : (
              penawaran.map((item) => (
                <div key={item.Id} className="space-y-3 rounded-[9px] border border-border p-3">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <p className="font-medium">{item.NamaPenyedia}</p>
                      <p className="text-xs text-muted-foreground">
                        {item.NomorPenawaran ?? 'Tanpa nomor'} · {item.TanggalPenawaran ?? '-'}
                      </p>
                    </div>
                    <div className="flex items-center gap-3">
                      <strong className="font-mono">{formatUang(item.Total)}</strong>
                      <Badge variant={VARIAN_PENAWARAN[item.Status]}>{item.Status}</Badge>
                      {rfq.Status === 'Dibuka' && item.Status === 'Diajukan' && (
                        <Button
                          size="sm"
                          variant="outline"
                          className="min-h-11 sm:min-h-9"
                          disabled={memproses}
                          onClick={() =>
                            jalankanAksi(rutePermintaanPenawaran.pilihPenawaran(rfq.Id, item.Id))
                          }
                        >
                          <CheckCircle2 /> Pilih
                        </Button>
                      )}
                    </div>
                  </div>
                  <Collapsible>
                    <CollapsibleTrigger className="flex min-h-11 items-center gap-2 text-sm text-muted-foreground hover:text-foreground sm:min-h-9 [&[data-state=open]>svg]:rotate-90">
                      <ChevronRight className="size-4 transition-transform" />
                      <Paperclip className="size-4" /> Dokumen penawaran
                    </CollapsibleTrigger>
                    <CollapsibleContent className="pt-3">
                      <LampiranTab jenisEntitas="PenawaranPenyedia" entitasId={item.Id} />
                    </CollapsibleContent>
                  </Collapsible>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
