import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { formatUang } from '@/lib/uang';
import type { Paginasi } from '@/types/global';
import type { Aset, FilterAset } from '@/features/Aset/types';
import type { KategoriAset } from '@/features/Aset/types';
import type { Lokasi } from '@/features/Lokasi/types';

interface Props {
  aset: Paginasi<Aset>;
  filter: FilterAset;
  kategoriAset: KategoriAset[];
  lokasi: Lokasi[];
}

const SEMUA = '__semua__';

function badgeStatus(status: Aset['Status']) {
  const varian = status === 'Aktif' ? 'default' : status === 'Rusak' ? 'destructive' : 'outline';
  return <Badge variant={varian}>{status}</Badge>;
}

function DialogTambahAset({ kategoriAset, lokasi }: { kategoriAset: KategoriAset[]; lokasi: Lokasi[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    KategoriAsetId: kategoriAset[0]?.Id ?? '', LokasiId: SEMUA, KodeAset: '', Nama: '', NomorSeri: '',
    HargaPerolehan: '', Status: 'Aktif', Kondisi: 'Baik', TingkatKritis: 'Normal',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, LokasiId: form.data.LokasiId === SEMUA ? null : form.data.LokasiId };
    router.post('/aset', payload, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Daftarkan Aset</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader><DialogTitle>Daftarkan Aset</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode Aset</Label>
              <Input value={form.data.KodeAset} onChange={(e) => form.setData('KodeAset', e.target.value)} className="font-mono" />
              {form.errors.KodeAset && <p className="text-sm text-destructive">{form.errors.KodeAset}</p>}
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kategori</Label>
              <Select value={form.data.KategoriAsetId} onValueChange={(v) => form.setData('KategoriAsetId', v)}>
                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {kategoriAset.map((k) => <SelectItem key={k.Id} value={k.Id}>{k.Nama}</SelectItem>)}
                </SelectContent>
              </Select>
              {form.errors.KategoriAsetId && <p className="text-sm text-destructive">{form.errors.KategoriAsetId}</p>}
            </div>
            <div className="space-y-2">
              <Label>Lokasi Awal</Label>
              <Select value={form.data.LokasiId} onValueChange={(v) => form.setData('LokasiId', v)}>
                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value={SEMUA}>Belum ditentukan</SelectItem>
                  {lokasi.map((l) => <SelectItem key={l.Id} value={l.Id}>{l.Nama}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Nomor Seri</Label>
              <Input value={form.data.NomorSeri} onChange={(e) => form.setData('NomorSeri', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Harga Perolehan</Label>
              <Input type="number" min={0} value={form.data.HargaPerolehan} onChange={(e) => form.setData('HargaPerolehan', e.target.value)} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function AsetIndex({ aset, filter, kategoriAset, lokasi }: Props) {
  const [form, setForm] = useState<FilterAset>(filter);

  const terapkanFilter = (e: FormEvent) => {
    e.preventDefault();
    router.get('/aset', { ...form }, { preserveState: true, preserveScroll: true });
  };

  const resetFilter = () => {
    setForm({});
    router.get('/aset', {}, { preserveState: true, preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Aset" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">Aset</h1>
            <p className="text-sm text-muted-foreground">Daftar induk aset organisasi -- identitas, lokasi, dan status.</p>
          </div>
          <DialogTambahAset kategoriAset={kategoriAset} lokasi={lokasi} />
        </div>

        <form onSubmit={terapkanFilter} className="grid grid-cols-1 gap-4 rounded-lg border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5">
          <div className="space-y-1.5 lg:col-span-2">
            <Label>Cari</Label>
            <Input
              value={form.cari ?? ''}
              onChange={(e) => setForm((f) => ({ ...f, cari: e.target.value || undefined }))}
              placeholder="Nama, kode, atau nomor seri..."
            />
          </div>
          <div className="space-y-1.5">
            <Label>Kategori</Label>
            <Select value={form.kategoriAsetId ?? SEMUA} onValueChange={(v) => setForm((f) => ({ ...f, kategoriAsetId: v === SEMUA ? undefined : v }))}>
              <SelectTrigger><SelectValue placeholder="Semua" /></SelectTrigger>
              <SelectContent>
                <SelectItem value={SEMUA}>Semua</SelectItem>
                {kategoriAset.map((k) => <SelectItem key={k.Id} value={k.Id}>{k.Nama}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Lokasi</Label>
            <Select value={form.lokasiId ?? SEMUA} onValueChange={(v) => setForm((f) => ({ ...f, lokasiId: v === SEMUA ? undefined : v }))}>
              <SelectTrigger><SelectValue placeholder="Semua" /></SelectTrigger>
              <SelectContent>
                <SelectItem value={SEMUA}>Semua</SelectItem>
                {lokasi.map((l) => <SelectItem key={l.Id} value={l.Id}>{l.Nama}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Status</Label>
            <Select value={form.status ?? SEMUA} onValueChange={(v) => setForm((f) => ({ ...f, status: v === SEMUA ? undefined : v }))}>
              <SelectTrigger><SelectValue placeholder="Semua" /></SelectTrigger>
              <SelectContent>
                <SelectItem value={SEMUA}>Semua</SelectItem>
                {['Aktif', 'Nonaktif', 'Dipinjam', 'Rusak', 'Diarsipkan'].map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="flex items-end gap-2 lg:col-span-5">
            <Button type="submit">Terapkan</Button>
            <Button type="button" variant="outline" onClick={resetFilter}>Reset</Button>
          </div>
        </form>

        <div className="rounded-lg border border-border bg-card">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nama</TableHead>
                <TableHead>Kategori</TableHead>
                <TableHead>Lokasi</TableHead>
                <TableHead>Harga Perolehan</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {aset.data.length === 0 && (
                <TableRow><TableCell colSpan={5} className="text-center text-muted-foreground">Belum ada aset.</TableCell></TableRow>
              )}
              {aset.data.map((a) => (
                <TableRow key={a.Id} className="cursor-pointer" onClick={() => router.visit(`/aset/${a.Id}`)}>
                  <TableCell>
                    <div className="font-medium text-foreground">{a.Nama}</div>
                    <div className="font-mono text-xs text-muted-foreground">{a.KodeAset}</div>
                  </TableCell>
                  <TableCell>{a.NamaKategoriAset ?? '—'}</TableCell>
                  <TableCell>{a.NamaLokasi ?? '—'}</TableCell>
                  <TableCell>{a.HargaPerolehan ? formatUang(a.HargaPerolehan, a.MataUang) : '—'}</TableCell>
                  <TableCell>{badgeStatus(a.Status)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <Pagination meta={aset.meta} onNavigasi={(halaman) => navigasiHalaman(halaman, form as Record<string, string>)} />
        </div>
      </div>
    </AppLayout>
  );
}
