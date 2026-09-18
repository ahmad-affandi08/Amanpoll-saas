import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { useIzin } from '@/hooks/use-izin';
import type { Paginasi } from '@/types/global';
import type { Pengguna, PeranRingkas } from '@/features/Pengguna/types';

interface Props {
  pengguna: Paginasi<Pengguna>;
  peranTersedia: PeranRingkas[];
  cari: string;
}

const kosong = { Nama: '', Email: '', KataSandi: '', Telepon: '', NomorPegawai: '', Jabatan: '', JenisPengguna: 'Internal' as const };

function DialogFormPengguna({ pengguna, onSelesai }: { pengguna: Pengguna | null; onSelesai: () => void }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(pengguna ? {
    Nama: pengguna.Nama, Email: pengguna.Email, KataSandi: '', Telepon: pengguna.Telepon ?? '',
    NomorPegawai: pengguna.NomorPegawai ?? '', Jabatan: pengguna.Jabatan ?? '', JenisPengguna: pengguna.JenisPengguna,
  } : kosong);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => { setBuka(false); form.reset(); onSelesai(); },
    };
    if (pengguna) {
      form.put(`/platform/pengguna/${pengguna.Id}`, opsi);
    } else {
      form.post('/platform/pengguna', opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={pengguna ? 'outline' : 'default'} size={pengguna ? 'sm' : 'default'}>
          {pengguna ? 'Ubah' : 'Tambah Pengguna'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{pengguna ? 'Ubah Pengguna' : 'Tambah Pengguna'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-2">
              <Label>Email</Label>
              <Input type="email" value={form.data.Email} onChange={(e) => form.setData('Email', e.target.value)} />
              {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>{pengguna ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi'}</Label>
            <Input type="password" value={form.data.KataSandi} onChange={(e) => form.setData('KataSandi', e.target.value)} />
            {form.errors.KataSandi && <p className="text-sm text-destructive">{form.errors.KataSandi}</p>}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Telepon</Label>
              <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Nomor Pegawai</Label>
              <Input value={form.data.NomorPegawai} onChange={(e) => form.setData('NomorPegawai', e.target.value)} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Jabatan</Label>
              <Input value={form.data.Jabatan} onChange={(e) => form.setData('Jabatan', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Jenis Pengguna</Label>
              <Select value={form.data.JenisPengguna} onValueChange={(v) => form.setData('JenisPengguna', v as 'Internal' | 'Eksternal')}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="Internal">Internal</SelectItem>
                  <SelectItem value="Eksternal">Eksternal</SelectItem>
                </SelectContent>
              </Select>
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

function DialogKelolaPeran({ pengguna, peranTersedia }: { pengguna: Pengguna; peranTersedia: PeranRingkas[] }) {
  const [buka, setBuka] = useState(false);
  const [peranTerpilih, setPeranTerpilih] = useState('');

  const tambahkan = () => {
    if (!peranTerpilih) return;
    router.post(`/platform/pengguna/${pengguna.Id}/peran`, { PeranId: peranTerpilih }, {
      preserveScroll: true,
      onSuccess: () => setPeranTerpilih(''),
    });
  };

  const cabut = (penggunaPeranId: string) => {
    router.delete(`/platform/pengguna-peran/${penggunaPeranId}`, { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">Kelola Peran</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Peran untuk {pengguna.Nama}</DialogTitle>
        </DialogHeader>
        <div className="space-y-3">
          {pengguna.Peran.length === 0 && <p className="text-sm text-muted-foreground">Belum ada peran ditetapkan.</p>}
          {pengguna.Peran.map((p) => (
            <div key={p.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2">
              <span className="text-sm">{p.NamaPeran}</span>
              <Button variant="ghost" size="sm" onClick={() => cabut(p.Id)}>Cabut</Button>
            </div>
          ))}
          <div className="flex gap-2 pt-2">
            <Select value={peranTerpilih} onValueChange={setPeranTerpilih}>
              <SelectTrigger className="flex-1"><SelectValue placeholder="Pilih peran" /></SelectTrigger>
              <SelectContent>
                {peranTersedia.map((p) => (
                  <SelectItem key={p.Id} value={p.Id}>{p.Nama}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Button onClick={tambahkan}>Tetapkan</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default function PenggunaIndex({ pengguna, peranTersedia, cari }: Props) {
  const { boleh } = useIzin();
  const [kataCari, setKataCari] = useState(cari);

  const cariSubmit = (e: FormEvent) => {
    e.preventDefault();
    router.get('/platform/pengguna', { cari: kataCari }, { preserveState: true });
  };

  const ubahStatus = (item: Pengguna) => {
    const statusBaru = item.Status === 'Aktif' ? 'Nonaktif' : 'Aktif';
    router.put(`/platform/pengguna/${item.Id}/status`, { Status: statusBaru }, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Pengguna" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Pengguna</h1>
          <p className="text-sm text-muted-foreground">Kelola akun pengguna dan penetapan peran.</p>
        </div>
        {boleh('Pengguna.Kelola') && <DialogFormPengguna pengguna={null} onSelesai={() => {}} />}
      </div>

      <form onSubmit={cariSubmit} className="mb-4 flex gap-2">
        <Input placeholder="Cari nama atau email..." value={kataCari} onChange={(e) => setKataCari(e.target.value)} className="max-w-sm" />
        <Button type="submit" variant="outline">Cari</Button>
      </form>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nama</TableHead>
              <TableHead>Jabatan</TableHead>
              <TableHead>Peran</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {pengguna.data.map((item) => (
              <TableRow key={item.Id}>
                <TableCell>
                  <div className="font-medium text-foreground">{item.Nama}</div>
                  <div className="text-sm text-muted-foreground">{item.Email}</div>
                </TableCell>
                <TableCell>{item.Jabatan ?? '—'}</TableCell>
                <TableCell>
                  <div className="flex flex-wrap gap-1">
                    {item.Peran.map((p) => <Badge key={p.Id} variant="secondary">{p.NamaPeran}</Badge>)}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant={item.Status === 'Aktif' ? 'default' : 'outline'}>{item.Status}</Badge>
                </TableCell>
                <TableCell className="flex justify-end gap-2">
                  {boleh('Pengguna.Kelola') && (
                    <>
                      <DialogFormPengguna pengguna={item} onSelesai={() => {}} />
                      <DialogKelolaPeran pengguna={item} peranTersedia={peranTersedia} />
                      <Button variant="ghost" size="sm" onClick={() => ubahStatus(item)}>
                        {item.Status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                      </Button>
                    </>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        <Pagination meta={pengguna.meta} onNavigasi={(h) => navigasiHalaman(h, { cari })} />
      </div>
    </AppLayout>
  );
}
