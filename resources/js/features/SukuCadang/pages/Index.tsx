import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { EmptyState } from '@/components/shared/EmptyState';
import type { KategoriSukuCadang, StatusSukuCadang, SukuCadang } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_SUKU_CADANG } from '@/features/Persediaan/status';
import { ruteSukuCadang } from '@/features/SukuCadang/api';

interface KategoriRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  sukuCadang: SukuCadang[];
  kategoriSukuCadang: KategoriRingkas[];
}

const TANPA = '__tanpa__';

function DialogFormSukuCadang({ kategoriSukuCadang }: { kategoriSukuCadang: KategoriRingkas[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: '',
    Nama: '',
    KategoriSukuCadangId: TANPA,
    NomorBagian: '',
    KodeBatang: '',
    SatuanDasar: '',
    StokMinimum: '0',
    StokMaksimum: '',
    TitikPesanUlang: '',
    HargaRataRata: '0',
    MemakaiBatch: false,
    MemakaiKadaluarsa: false,
    Status: 'Aktif' as StatusSukuCadang,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      KategoriSukuCadangId: form.data.KategoriSukuCadangId === TANPA ? null : form.data.KategoriSukuCadangId,
    };
    router.post(ruteSukuCadang.index, payload, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Suku Cadang</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Suku Cadang</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode</Label>
              <Input
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
                className="font-mono"
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>Kategori</Label>
            <Select
              value={form.data.KategoriSukuCadangId}
              onValueChange={(v) => form.setData('KategoriSukuCadangId', v)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tidak diisi</SelectItem>
                {kategoriSukuCadang.map((k) => (
                  <SelectItem key={k.Id} value={k.Id}>
                    {k.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Nomor Bagian</Label>
              <Input
                value={form.data.NomorBagian}
                onChange={(e) => form.setData('NomorBagian', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Kode Batang/SKU</Label>
              <Input
                value={form.data.KodeBatang}
                onChange={(e) => form.setData('KodeBatang', e.target.value)}
              />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Satuan Dasar</Label>
              <Input
                value={form.data.SatuanDasar}
                onChange={(e) => form.setData('SatuanDasar', e.target.value)}
                placeholder="mis. Pcs, Liter"
              />
              {form.errors.SatuanDasar && (
                <p className="text-sm text-destructive">{form.errors.SatuanDasar}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label>Harga Rata-rata</Label>
              <Input
                type="number"
                min={0}
                value={form.data.HargaRataRata}
                onChange={(e) => form.setData('HargaRataRata', e.target.value)}
              />
            </div>
          </div>
          <div className="grid grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Stok Minimum</Label>
              <Input
                type="number"
                min={0}
                value={form.data.StokMinimum}
                onChange={(e) => form.setData('StokMinimum', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Stok Maksimum</Label>
              <Input
                type="number"
                min={0}
                value={form.data.StokMaksimum}
                onChange={(e) => form.setData('StokMaksimum', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Titik Pesan Ulang</Label>
              <Input
                type="number"
                min={0}
                value={form.data.TitikPesanUlang}
                onChange={(e) => form.setData('TitikPesanUlang', e.target.value)}
              />
            </div>
          </div>
          <div className="flex gap-6">
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.MemakaiBatch}
                onCheckedChange={(v) => form.setData('MemakaiBatch', v === true)}
              />
              Memakai Batch
            </label>
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.MemakaiKadaluarsa}
                onCheckedChange={(v) => form.setData('MemakaiKadaluarsa', v === true)}
              />
              Memakai Kadaluarsa
            </label>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function SukuCadangIndex({ sukuCadang, kategoriSukuCadang }: Props) {
  const columns = useMemo<ColumnDef<SukuCadang>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <Link href={ruteSukuCadang.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </Link>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'NamaKategori',
        accessorFn: (row) => row.NamaKategori ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => row.original.NamaKategori ?? '—',
        meta: { label: 'Kategori' },
      },
      {
        id: 'StokTersedia',
        accessorFn: (row) => row.JumlahTersediaBersih ?? 0,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Stok Tersedia" />,
        cell: ({ row }) => {
          const bersih = row.original.JumlahTersediaBersih ?? 0;
          const minimum = parseFloat(row.original.StokMinimum);
          const dibawahMinimum = bersih <= minimum;
          return (
            <div className="flex items-center gap-2">
              <span className={dibawahMinimum ? 'font-semibold text-destructive' : 'text-foreground'}>
                {bersih}
              </span>
              <span className="text-xs text-muted-foreground">{row.original.SatuanDasar}</span>
              {dibawahMinimum && <Badge variant="bahaya">Di bawah minimum</Badge>}
            </div>
          );
        },
        meta: { label: 'Stok Tersedia' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={VARIAN_BADGE_STATUS_SUKU_CADANG[row.original.Status]}>{row.original.Status}</Badge>
        ),
        meta: { label: 'Status' },
      },
    ],
    [],
  );

  const jumlahDibawahMinimum = sukuCadang.filter(
    (s) => (s.JumlahTersediaBersih ?? 0) <= parseFloat(s.StokMinimum),
  ).length;

  return (
    <AppLayout>
      <Head title="Suku Cadang" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Suku Cadang</h1>
          <p className="text-sm text-muted-foreground">
            Master data suku cadang beserta saldo stok bersih lintas gudang.
          </p>
        </div>
        <DialogFormSukuCadang kategoriSukuCadang={kategoriSukuCadang} />
      </div>

      {jumlahDibawahMinimum > 0 && (
        <div className="mb-4 rounded-[9px] border border-bahaya-600/25 bg-bahaya-600/10 px-4 py-3 text-sm text-bahaya-600">
          {jumlahDibawahMinimum} suku cadang berada di bawah atau sama dengan stok minimum.
        </div>
      )}

      {sukuCadang.length === 0 ? (
        <EmptyState
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada suku cadang."
          deskripsi="Tambahkan suku cadang pertama untuk mulai mencatat stok."
        />
      ) : (
        <DataTable
          columns={columns}
          data={sukuCadang}
          pencarianPlaceholder="Cari nama atau kode suku cadang..."
          pesanKosong="Belum ada suku cadang."
        />
      )}
    </AppLayout>
  );
}
