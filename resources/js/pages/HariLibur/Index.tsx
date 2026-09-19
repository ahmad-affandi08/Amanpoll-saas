import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { HariLibur } from '@/features/HariLibur/types';

interface Props {
  hariLibur: HariLibur[];
}

function DialogTambahHariLibur() {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Tanggal: '', Nama: '', BerulangTahunan: false });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/platform/hari-libur', { onSuccess: () => { setBuka(false); form.reset(); } });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Hari Libur</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>Tambah Hari Libur</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Tanggal</Label>
            <Input type="date" value={form.data.Tanggal} onChange={(e) => form.setData('Tanggal', e.target.value)} />
            {form.errors.Tanggal && <p className="text-sm text-destructive">{form.errors.Tanggal}</p>}
          </div>
          <div className="space-y-2">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <label className="flex items-center gap-2 text-sm">
            <Checkbox checked={form.data.BerulangTahunan} onCheckedChange={(v) => form.setData('BerulangTahunan', Boolean(v))} />
            Berulang setiap tahun (tanggal-bulan yang sama)
          </label>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function formatTanggal(tanggal: string): string {
  const [tahun, bulan, hari] = tanggal.split('-').map(Number);
  return new Date(tahun, bulan - 1, hari).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

export default function HariLiburIndex({ hariLibur }: Props) {
  const hapus = (libur: HariLibur) => {
    if (!confirm(`Hapus hari libur "${libur.Nama}"?`)) return;
    router.delete(`/platform/hari-libur/${libur.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Hari Libur" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Hari Libur</h1>
          <p className="text-sm text-muted-foreground">Dipakai untuk menghindari penjadwalan pekerjaan di hari libur.</p>
        </div>
        <DialogTambahHariLibur />
      </div>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead>Nama</TableHead>
              <TableHead>Berulang</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {hariLibur.map((libur) => (
              <TableRow key={libur.Id}>
                <TableCell className="font-mono text-sm">{formatTanggal(libur.Tanggal)}</TableCell>
                <TableCell className="font-medium text-foreground">{libur.Nama}</TableCell>
                <TableCell>{libur.BerulangTahunan ? <Badge variant="secondary">Setiap Tahun</Badge> : <span className="text-sm text-muted-foreground">Sekali</span>}</TableCell>
                <TableCell className="text-right">
                  <Button variant="ghost" size="sm" onClick={() => hapus(libur)}>Hapus</Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
