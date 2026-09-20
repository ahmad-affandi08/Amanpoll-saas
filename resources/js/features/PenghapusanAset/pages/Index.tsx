import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { EmptyState } from '@/components/shared/EmptyState';
import type { Paginasi } from '@/types/global';
import type { PengajuanPenghapusanAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_PENGHAPUSAN } from '@/features/SiklusAset/status';

interface Props {
  pengajuan: Paginasi<PengajuanPenghapusanAset>;
  filter: { status?: string };
}

const SEMUA = '__semua__';
const DAFTAR_STATUS = ['Draft', 'Menunggu', 'Disetujui', 'Ditolak', 'Dibatalkan', 'Selesai'];
const DAFTAR_METODE = ['Dijual', 'Dimusnahkan', 'Hibah', 'Hilang', 'Lainnya'];

function DialogBuatPengajuan() {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Alasan: '', MetodePenghapusan: 'Dimusnahkan' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post('/penghapusan-aset', form.data, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="destructive">Ajukan Penghapusan</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>Ajukan Penghapusan Aset</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Alasan</Label>
            <Textarea value={form.data.Alasan} onChange={(e) => form.setData('Alasan', e.target.value)} rows={3} />
            {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
          </div>
          <div className="space-y-1.5">
            <Label>Metode Penghapusan</Label>
            <Select value={form.data.MetodePenghapusan} onValueChange={(v) => form.setData('MetodePenghapusan', v)}>
              <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                {DAFTAR_METODE.map((m) => <SelectItem key={m} value={m}>{m}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <p className="text-sm text-muted-foreground">Daftar aset dilengkapi setelah draft dibuat.</p>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Buat Draft</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PenghapusanAsetIndex({ pengajuan, filter }: Props) {
  const [status, setStatus] = useState(filter.status ?? SEMUA);

  const terapkanFilter = (v: string) => {
    setStatus(v);
    router.get('/penghapusan-aset', v === SEMUA ? {} : { status: v }, { preserveState: true, preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Penghapusan Aset" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">Penghapusan Aset</h1>
            <p className="text-sm text-muted-foreground">Pengajuan pelepasan aset -- draft, persetujuan, sampai eksekusi.</p>
          </div>
          <DialogBuatPengajuan />
        </div>

        <div className="w-56 space-y-1.5">
          <Label>Status</Label>
          <Select value={status} onValueChange={terapkanFilter}>
            <SelectTrigger><SelectValue placeholder="Semua" /></SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua</SelectItem>
              {DAFTAR_STATUS.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
            </SelectContent>
          </Select>
        </div>

        {pengajuan.data.length === 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <EmptyState judul="Belum ada pengajuan penghapusan." deskripsi="Pengajuan pelepasan aset akan muncul di sini." />
          </div>
        )}

        {pengajuan.data.length > 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nomor</TableHead>
                  <TableHead>Metode</TableHead>
                  <TableHead>Diajukan Oleh</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {pengajuan.data.map((p) => (
                  <TableRow key={p.Id} className="cursor-pointer" onClick={() => router.visit(`/penghapusan-aset/${p.Id}`)}>
                    <TableCell className="font-mono text-xs">{p.Nomor}</TableCell>
                    <TableCell>{p.MetodePenghapusan ?? '—'}</TableCell>
                    <TableCell>{p.NamaDiajukanOleh ?? '—'}</TableCell>
                    <TableCell><Badge variant={VARIAN_BADGE_STATUS_PENGHAPUSAN[p.Status]}>{p.Status}</Badge></TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <Pagination meta={pengajuan.meta} onNavigasi={(halaman) => navigasiHalaman(halaman, filter as Record<string, string>)} />
          </div>
        )}
      </div>
    </AppLayout>
  );
}
