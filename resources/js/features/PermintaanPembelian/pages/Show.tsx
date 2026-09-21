import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Send, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
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
import type { JenisItemPengadaan, PermintaanPembelian } from '@/features/PermintaanPembelian/types';
import { formatUang } from '@/lib/uang';
import { rutePermintaanPembelian } from '@/features/PermintaanPembelian/api';

interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
}
interface SukuCadangRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
interface Props {
  permintaan: PermintaanPembelian;
  aset: AsetRingkas[];
  sukuCadang: SukuCadangRingkas[];
}

const JENIS_ITEM: JenisItemPengadaan[] = ['Aset', 'SukuCadang', 'Jasa', 'Lainnya'];
const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'sukses',
  Ditolak: 'bahaya',
} as const;

function DialogTambahItem({ permintaan, aset, sukuCadang }: Props) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    JenisItem: 'Lainnya',
    AsetReferensiId: '',
    SukuCadangId: '',
    Deskripsi: '',
    Jumlah: '1',
    Satuan: 'unit',
    HargaEstimasi: '0',
    Spesifikasi: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      AsetReferensiId: data.JenisItem === 'Aset' ? data.AsetReferensiId || null : null,
      SukuCadangId: data.JenisItem === 'SukuCadang' ? data.SukuCadangId || null : null,
      Spesifikasi: data.Spesifikasi || null,
    }));
    form.post(rutePermintaanPembelian.detailItem(permintaan.Id), {
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
          <Plus /> Tambah Item
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Tambah Item Permintaan</DialogTitle>
          <DialogDescription>
            Item aset wajib memilih aset referensi agar kategori dapat diwariskan saat penerimaan.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Jenis Item</Label>
            <Select value={form.data.JenisItem} onValueChange={(value) => form.setData('JenisItem', value)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {JENIS_ITEM.map((item) => (
                  <SelectItem key={item} value={item}>
                    {item}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          {form.data.JenisItem === 'Aset' && (
            <div className="space-y-1.5">
              <Label>Aset Referensi</Label>
              <Select
                value={form.data.AsetReferensiId}
                onValueChange={(value) => form.setData('AsetReferensiId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih aset sejenis" />
                </SelectTrigger>
                <SelectContent>
                  {aset.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.KodeAset} — {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.AsetReferensiId && (
                <p className="text-sm text-destructive">{form.errors.AsetReferensiId}</p>
              )}
            </div>
          )}
          {form.data.JenisItem === 'SukuCadang' && (
            <div className="space-y-1.5">
              <Label>Suku Cadang</Label>
              <Select
                value={form.data.SukuCadangId}
                onValueChange={(value) => form.setData('SukuCadangId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih suku cadang" />
                </SelectTrigger>
                <SelectContent>
                  {sukuCadang.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Kode} — {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.SukuCadangId && (
                <p className="text-sm text-destructive">{form.errors.SukuCadangId}</p>
              )}
            </div>
          )}
          <div className="space-y-1.5">
            <Label htmlFor="Deskripsi">Deskripsi</Label>
            <Input
              id="Deskripsi"
              value={form.data.Deskripsi}
              onChange={(event) => form.setData('Deskripsi', event.target.value)}
            />
            {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5">
              <Label htmlFor="Jumlah">Jumlah</Label>
              <Input
                id="Jumlah"
                type="number"
                min="0.0001"
                step="0.0001"
                value={form.data.Jumlah}
                onChange={(event) => form.setData('Jumlah', event.target.value)}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="Satuan">Satuan</Label>
              <Input
                id="Satuan"
                value={form.data.Satuan}
                onChange={(event) => form.setData('Satuan', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="HargaEstimasi">Harga Estimasi</Label>
              <Input
                id="HargaEstimasi"
                type="number"
                min="0"
                step="0.01"
                value={form.data.HargaEstimasi}
                onChange={(event) => form.setData('HargaEstimasi', event.target.value)}
              />
              {form.errors.HargaEstimasi && (
                <p className="text-sm text-destructive">{form.errors.HargaEstimasi}</p>
              )}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="Spesifikasi">Spesifikasi</Label>
            <Input
              id="Spesifikasi"
              value={form.data.Spesifikasi}
              onChange={(event) => form.setData('Spesifikasi', event.target.value)}
            />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Tambahkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PermintaanPembelianShow(props: Props) {
  const { permintaan } = props;
  const [memproses, setMemproses] = useState(false);
  const draft = permintaan.Status === 'Draft';
  const detail = permintaan.Detail ?? [];

  function submitPermintaan(): void {
    router.post(
      rutePermintaanPembelian.submit(permintaan.Id),
      {},
      {
        preserveScroll: true,
        onStart: () => setMemproses(true),
        onFinish: () => setMemproses(false),
      },
    );
  }

  function hapusItem(detailId: string): void {
    router.delete(rutePermintaanPembelian.hapusItem(permintaan.Id, detailId), { preserveScroll: true });
  }

  return (
    <AppLayout>
      <Head title={permintaan.Nomor} />
      <div className="space-y-6 pb-28 sm:pb-6">
        <Link
          href={rutePermintaanPembelian.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali
        </Link>

        <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="font-mono text-2xl font-semibold">{permintaan.Nomor}</h1>
              <Badge variant={VARIAN_STATUS[permintaan.Status]}>{permintaan.Status}</Badge>
              <Badge variant="secondary">{permintaan.Prioritas}</Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              {permintaan.NamaPosAnggaran ?? 'Pos belum dipilih'} ·{' '}
              {permintaan.NamaUnitOrganisasi ?? 'Tanpa unit'}
              {permintaan.NamaPeminta ? ` · Diminta oleh ${permintaan.NamaPeminta}` : ''}
            </p>
          </div>
          {draft && (
            <div className="flex flex-wrap gap-2">
              <DialogTambahItem {...props} />
              <Button
                size="sm"
                className="min-h-11 sm:min-h-9"
                disabled={memproses}
                onClick={submitPermintaan}
              >
                <Send /> Ajukan Persetujuan
              </Button>
            </div>
          )}
        </header>

        <Card>
          <CardHeader>
            <CardTitle>Detail Item</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {detail.length === 0 ? (
              <EmptyState
                judul="Belum ada item."
                deskripsi="Tambahkan minimal satu item sebelum permintaan dapat diajukan."
              />
            ) : (
              detail.map((item) => (
                <div
                  key={item.Id}
                  className="grid gap-2 rounded-[9px] border border-border p-3 sm:grid-cols-[1fr_auto_auto] sm:items-center"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{item.Deskripsi}</p>
                    <p className="text-xs text-muted-foreground">
                      {item.JenisItem} · {item.Jumlah} {item.Satuan}
                      {item.NamaSukuCadang ? ` · ${item.NamaSukuCadang}` : ''}
                      {item.NamaAsetReferensi ? ` · referensi ${item.NamaAsetReferensi}` : ''}
                    </p>
                  </div>
                  <p className="font-mono font-medium">
                    {formatUang(Number(item.Jumlah) * Number(item.HargaEstimasi ?? 0))}
                  </p>
                  {draft && (
                    <Button
                      size="icon"
                      variant="ghost"
                      aria-label={`Hapus item ${item.Deskripsi}`}
                      onClick={() => hapusItem(item.Id)}
                    >
                      <Trash2 />
                    </Button>
                  )}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/95 p-4 backdrop-blur sm:static sm:rounded-[9px] sm:border sm:p-4">
          <div className="flex items-center justify-between">
            <span className="text-sm text-muted-foreground">Total estimasi (dihitung server)</span>
            <strong className="font-mono text-lg">{formatUang(permintaan.TotalEstimasi)}</strong>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
