import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { KategoriSukuCadang } from '@/features/Persediaan/types';

interface Props {
  kategoriSukuCadang: KategoriSukuCadang[];
}

const TANPA = '__tanpa__';

function DialogFormKategori({ kategori, semuaKategori }: { kategori: KategoriSukuCadang | null; semuaKategori: KategoriSukuCadang[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(kategori
    ? { Kode: kategori.Kode, Nama: kategori.Nama, IndukId: kategori.IndukId ?? TANPA }
    : { Kode: '', Nama: '', IndukId: TANPA });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, IndukId: form.data.IndukId === TANPA ? null : form.data.IndukId };
    const opsi = { onSuccess: () => { setBuka(false); if (!kategori) form.reset(); } };
    if (kategori) {
      router.put(`/kategori-suku-cadang/${kategori.Id}`, payload, opsi);
    } else {
      router.post('/kategori-suku-cadang', payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={kategori ? 'outline' : 'default'} size={kategori ? 'sm' : 'default'}>{kategori ? 'Ubah' : 'Tambah Kategori'}</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>{kategori ? 'Ubah Kategori Suku Cadang' : 'Tambah Kategori Suku Cadang'}</DialogTitle></DialogHeader>
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
          <div className="space-y-2">
            <Label>Kategori Induk</Label>
            <Select value={form.data.IndukId} onValueChange={(v) => form.setData('IndukId', v)}>
              <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tidak ada (kategori utama)</SelectItem>
                {semuaKategori.filter((k) => k.Id !== kategori?.Id).map((k) => <SelectItem key={k.Id} value={k.Id}>{k.Nama}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KategoriSukuCadangIndex({ kategoriSukuCadang }: Props) {
  const hapus = (item: KategoriSukuCadang) => {
    if (!confirm(`Hapus kategori "${item.Nama}"?`)) return;
    router.delete(`/kategori-suku-cadang/${item.Id}`, { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<KategoriSukuCadang>[]>(() => [
    {
      id: 'Nama',
      accessorFn: (row) => `${row.Nama} ${row.Kode}`,
      header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
      cell: ({ row }) => (
        <div>
          <div className="font-medium text-foreground">{row.original.Nama}</div>
          <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
        </div>
      ),
      meta: { label: 'Nama' },
    },
    {
      id: 'NamaInduk',
      accessorFn: (row) => row.NamaInduk ?? '',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Induk" />,
      cell: ({ row }) => row.original.NamaInduk ?? '—',
      meta: { label: 'Induk' },
    },
    {
      id: 'aksi',
      header: 'Aksi',
      cell: ({ row }) => (
        <div className="flex justify-end gap-2">
          <DialogFormKategori kategori={row.original} semuaKategori={kategoriSukuCadang} />
          <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>Hapus</Button>
        </div>
      ),
      enableSorting: false,
      enableHiding: false,
      meta: { label: 'Aksi' },
    },
  ], [kategoriSukuCadang]);

  return (
    <AppLayout>
      <Head title="Kategori Suku Cadang" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Kategori Suku Cadang</h1>
          <p className="text-sm text-muted-foreground">Klasifikasi suku cadang, mendukung hierarki sub-kategori.</p>
        </div>
        <DialogFormKategori kategori={null} semuaKategori={kategoriSukuCadang} />
      </div>

      <DataTable
        columns={columns}
        data={kategoriSukuCadang}
        pencarianPlaceholder="Cari nama atau kode kategori..."
        pesanKosong="Belum ada kategori suku cadang."
      />
    </AppLayout>
  );
}
