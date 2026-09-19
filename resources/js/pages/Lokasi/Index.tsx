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
import type { Paginasi } from '@/types/global';
import type { Lokasi, KategoriLokasi } from '@/features/Lokasi/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';

interface Props {
  lokasi: Paginasi<Lokasi>;
  unitOrganisasi: UnitOrganisasi[];
  kategoriLokasi: KategoriLokasi[];
  cari: string;
  status: string;
}

const TANPA = '__tanpa__';

function DialogKelolaKategori({ kategoriLokasi }: { kategoriLokasi: KategoriLokasi[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '', Keterangan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/platform/kategori-lokasi', { onSuccess: () => form.reset(), preserveScroll: true });
  };

  const hapus = (kategori: KategoriLokasi) => {
    if (!confirm(`Hapus kategori "${kategori.Nama}"?`)) return;
    router.delete(`/platform/kategori-lokasi/${kategori.Id}`, { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Kelola Kategori</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>Kategori Lokasi</DialogTitle></DialogHeader>
        <div className="space-y-2">
          {kategoriLokasi.map((k) => (
            <div key={k.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2">
              <div>
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                <span className="ml-2 font-mono text-xs text-muted-foreground">{k.Kode}</span>
              </div>
              <Button variant="ghost" size="sm" onClick={() => hapus(k)}>Hapus</Button>
            </div>
          ))}
          {kategoriLokasi.length === 0 && <p className="text-sm text-muted-foreground">Belum ada kategori.</p>}
        </div>
        <form onSubmit={submit} className="flex gap-2 border-t border-border pt-4">
          <Input placeholder="Kode" value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} className="w-28 font-mono" />
          <Input placeholder="Nama kategori" value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} className="flex-1" />
          <Button type="submit" disabled={form.processing}>Tambah</Button>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogFormLokasi({ lokasi, unitOrganisasi, kategoriLokasi }: { lokasi: Lokasi | null; unitOrganisasi: UnitOrganisasi[]; kategoriLokasi: KategoriLokasi[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(lokasi
    ? {
        Kode: lokasi.Kode, Nama: lokasi.Nama, Alamat: lokasi.Alamat ?? '', Lantai: lokasi.Lantai ?? '',
        Status: lokasi.Status, UnitOrganisasiId: lokasi.UnitOrganisasiId ?? TANPA, KategoriLokasiId: lokasi.KategoriLokasiId ?? TANPA,
      }
    : { Kode: '', Nama: '', Alamat: '', Lantai: '', Status: 'Aktif' as const, UnitOrganisasiId: TANPA, KategoriLokasiId: TANPA });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      UnitOrganisasiId: form.data.UnitOrganisasiId === TANPA ? null : form.data.UnitOrganisasiId,
      KategoriLokasiId: form.data.KategoriLokasiId === TANPA ? null : form.data.KategoriLokasiId,
    };
    const opsi = { onSuccess: () => { setBuka(false); form.reset(); } };
    if (lokasi) {
      router.put(`/platform/lokasi/${lokasi.Id}`, payload, opsi);
    } else {
      router.post('/platform/lokasi', payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={lokasi ? 'outline' : 'default'} size={lokasi ? 'sm' : 'default'}>{lokasi ? 'Ubah' : 'Tambah Lokasi'}</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>{lokasi ? 'Ubah Lokasi' : 'Tambah Lokasi'}</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode</Label>
              <Input value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} className="font-mono" />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Unit Organisasi</Label>
              <Select value={form.data.UnitOrganisasiId} onValueChange={(v) => form.setData('UnitOrganisasiId', v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tidak ditautkan</SelectItem>
                  {unitOrganisasi.map((u) => <SelectItem key={u.Id} value={u.Id}>{u.Nama}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Kategori</Label>
              <Select value={form.data.KategoriLokasiId} onValueChange={(v) => form.setData('KategoriLokasiId', v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tanpa kategori</SelectItem>
                  {kategoriLokasi.map((k) => <SelectItem key={k.Id} value={k.Id}>{k.Nama}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Lantai</Label>
              <Input value={form.data.Lantai} onChange={(e) => form.setData('Lantai', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v as 'Aktif' | 'Nonaktif')}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="Aktif">Aktif</SelectItem>
                  <SelectItem value="Nonaktif">Nonaktif</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-2">
            <Label>Alamat</Label>
            <Input value={form.data.Alamat} onChange={(e) => form.setData('Alamat', e.target.value)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function LokasiIndex({ lokasi, unitOrganisasi, kategoriLokasi, cari, status }: Props) {
  const [kataCari, setKataCari] = useState(cari);

  const cariSubmit = (e: FormEvent) => {
    e.preventDefault();
    router.get('/platform/lokasi', { cari: kataCari, status }, { preserveState: true });
  };

  const hapus = (item: Lokasi) => {
    if (!confirm(`Hapus lokasi "${item.Nama}"?`)) return;
    router.delete(`/platform/lokasi/${item.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Lokasi" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Lokasi</h1>
          <p className="text-sm text-muted-foreground">Kelola lokasi fisik aset dan fasilitas.</p>
        </div>
        <div className="flex gap-2">
          <DialogKelolaKategori kategoriLokasi={kategoriLokasi} />
          <DialogFormLokasi lokasi={null} unitOrganisasi={unitOrganisasi} kategoriLokasi={kategoriLokasi} />
        </div>
      </div>

      <form onSubmit={cariSubmit} className="mb-4 flex gap-2">
        <Input placeholder="Cari nama atau kode..." value={kataCari} onChange={(e) => setKataCari(e.target.value)} className="max-w-sm" />
        <Button type="submit" variant="outline">Cari</Button>
      </form>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nama</TableHead>
              <TableHead>Kategori</TableHead>
              <TableHead>Unit</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {lokasi.data.map((item) => (
              <TableRow key={item.Id}>
                <TableCell>
                  <div className="font-medium text-foreground">{item.Nama}</div>
                  <div className="font-mono text-xs text-muted-foreground">{item.Kode}</div>
                </TableCell>
                <TableCell>{item.NamaKategoriLokasi ?? '—'}</TableCell>
                <TableCell>{item.NamaUnitOrganisasi ?? '—'}</TableCell>
                <TableCell><Badge variant={item.Status === 'Aktif' ? 'default' : 'outline'}>{item.Status}</Badge></TableCell>
                <TableCell className="flex justify-end gap-2">
                  <DialogFormLokasi lokasi={item} unitOrganisasi={unitOrganisasi} kategoriLokasi={kategoriLokasi} />
                  <Button variant="ghost" size="sm" onClick={() => hapus(item)}>Hapus</Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        <Pagination meta={lokasi.meta} onNavigasi={(h) => navigasiHalaman(h, { cari, status })} />
      </div>
    </AppLayout>
  );
}
