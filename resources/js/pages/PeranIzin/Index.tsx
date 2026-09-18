import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { useIzin } from '@/hooks/use-izin';
import type { Paginasi } from '@/types/global';
import type { Peran, KatalogIzin } from '@/features/PeranIzin/types';

interface Props {
  peran: Paginasi<Peran>;
  cari: string;
}

function DialogFormPeran({ peran }: { peran: Peran | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(peran
    ? { Kode: peran.Kode, Nama: peran.Nama, Keterangan: peran.Keterangan ?? '' }
    : { Kode: '', Nama: '', Keterangan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = { onSuccess: () => { setBuka(false); form.reset(); } };
    if (peran) {
      form.put(`/platform/peran/${peran.Id}`, opsi);
    } else {
      form.post('/platform/peran', opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={peran ? 'outline' : 'default'} size={peran ? 'sm' : 'default'}>
          {peran ? 'Ubah' : 'Tambah Peran'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>{peran ? 'Ubah Peran' : 'Tambah Peran'}</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
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
          <div className="space-y-2">
            <Label>Keterangan</Label>
            <Textarea value={form.data.Keterangan} onChange={(e) => form.setData('Keterangan', e.target.value)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogKelolaIzin({ peran }: { peran: Peran }) {
  const [buka, setBuka] = useState(false);
  const [katalog, setKatalog] = useState<KatalogIzin | null>(null);
  const [terpilih, setTerpilih] = useState<string[]>(peran.DaftarIzinId);
  const [menyimpan, setMenyimpan] = useState(false);

  useEffect(() => {
    if (buka && !katalog) {
      fetch('/platform/izin', { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((json) => setKatalog(json.data));
    }
  }, [buka, katalog]);

  const toggle = (izinId: string) => {
    setTerpilih((prev) => (prev.includes(izinId) ? prev.filter((id) => id !== izinId) : [...prev, izinId]));
  };

  const simpan = () => {
    setMenyimpan(true);
    router.put(`/platform/peran/${peran.Id}/izin`, { IzinId: terpilih }, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
      onFinish: () => setMenyimpan(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">Kelola Izin</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader><DialogTitle>Izin untuk {peran.Nama}</DialogTitle></DialogHeader>
        {!katalog && <p className="text-sm text-muted-foreground">Memuat katalog izin...</p>}
        {katalog && Object.entries(katalog).map(([modul, daftar]) => (
          <div key={modul} className="mb-4">
            <h3 className="mb-2 text-sm font-semibold text-foreground">{modul}</h3>
            <div className="space-y-2">
              {daftar.map((izin) => (
                <label key={izin.Id} className="flex items-center gap-2 text-sm">
                  <Checkbox checked={terpilih.includes(izin.Id)} onCheckedChange={() => toggle(izin.Id)} />
                  {izin.Nama}
                </label>
              ))}
            </div>
          </div>
        ))}
        <DialogFooter>
          <Button onClick={simpan} disabled={menyimpan || !katalog}>Simpan Izin</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function PeranIzinIndex({ peran, cari }: Props) {
  const { boleh } = useIzin();
  const [kataCari, setKataCari] = useState(cari);

  const cariSubmit = (e: FormEvent) => {
    e.preventDefault();
    router.get('/platform/peran', { cari: kataCari }, { preserveState: true });
  };

  const hapus = (item: Peran) => {
    if (!confirm(`Hapus peran "${item.Nama}"?`)) return;
    router.delete(`/platform/peran/${item.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Peran & Izin" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Peran & Izin</h1>
          <p className="text-sm text-muted-foreground">Kelola peran dan hak akses per organisasi.</p>
        </div>
        {boleh('Pengguna.Kelola') && <DialogFormPeran peran={null} />}
      </div>

      <form onSubmit={cariSubmit} className="mb-4 flex gap-2">
        <Input placeholder="Cari nama peran..." value={kataCari} onChange={(e) => setKataCari(e.target.value)} className="max-w-sm" />
        <Button type="submit" variant="outline">Cari</Button>
      </form>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Kode</TableHead>
              <TableHead>Nama</TableHead>
              <TableHead>Izin</TableHead>
              <TableHead>Pengguna</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {peran.data.map((item) => (
              <TableRow key={item.Id}>
                <TableCell className="font-mono text-sm">{item.Kode}</TableCell>
                <TableCell>
                  <div className="font-medium text-foreground">{item.Nama}</div>
                  {item.BawaanSistem && <Badge variant="secondary">Bawaan Sistem</Badge>}
                </TableCell>
                <TableCell>{item.JumlahIzin}</TableCell>
                <TableCell>{item.JumlahPengguna}</TableCell>
                <TableCell className="flex justify-end gap-2">
                  {boleh('Pengguna.Kelola') && (
                    <>
                      <DialogFormPeran peran={item} />
                      <DialogKelolaIzin peran={item} />
                      {!item.BawaanSistem && (
                        <Button variant="ghost" size="sm" onClick={() => hapus(item)}>Hapus</Button>
                      )}
                    </>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        <Pagination meta={peran.meta} onNavigasi={(h) => navigasiHalaman(h, { cari })} />
      </div>
    </AppLayout>
  );
}
