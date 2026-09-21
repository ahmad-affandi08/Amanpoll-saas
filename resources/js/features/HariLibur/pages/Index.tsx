import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
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
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { HariLibur } from '@/features/HariLibur/types';
import { ruteHariLibur } from '@/features/HariLibur/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  hariLibur: HariLibur[];
}

function DialogTambahHariLibur() {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Tanggal: '', Nama: '', BerulangTahunan: false });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteHariLibur.index, {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Hari Libur</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Hari Libur</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Tanggal</Label>
            <DatePicker
              value={form.data.Tanggal}
              onChange={(v) => form.setData('Tanggal', v)}
              placeholder="Pilih tanggal libur"
            />
            {form.errors.Tanggal && <p className="text-sm text-destructive">{form.errors.Tanggal}</p>}
          </div>
          <div className="space-y-2">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.BerulangTahunan}
              onCheckedChange={(v) => form.setData('BerulangTahunan', Boolean(v))}
            />
            Berulang setiap tahun (tanggal-bulan yang sama)
          </label>
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

function formatTanggal(tanggal: string): string {
  const [tahun, bulan, hari] = tanggal.split('-').map(Number);
  return new Date(tahun, bulan - 1, hari).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

export default function HariLiburIndex({ hariLibur }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (libur: HariLibur) => {
    if (
      !(await konfirmasi({
        judul: `Hapus hari libur "${libur.Nama}"?`,
        deskripsi: 'Perhitungan tenggat SLA pada tanggal itu kembali dihitung sebagai hari kerja.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteHariLibur.detail(libur.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<HariLibur>[]>(
    () => [
      {
        accessorKey: 'Tanggal',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tanggal" />,
        cell: ({ row }) => <span className="font-mono text-sm">{formatTanggal(row.original.Tanggal)}</span>,
        meta: { label: 'Tanggal' },
      },
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => <span className="font-medium text-foreground">{row.original.Nama}</span>,
        meta: { label: 'Nama' },
      },
      {
        id: 'BerulangTahunan',
        accessorFn: (row) => (row.BerulangTahunan ? 'Setiap Tahun' : 'Sekali'),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Berulang" />,
        cell: ({ row }) =>
          row.original.BerulangTahunan ? (
            <Badge variant="secondary">Setiap Tahun</Badge>
          ) : (
            <span className="text-sm text-muted-foreground">Sekali</span>
          ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Berulang' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="text-right">
            <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>
              Hapus
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi' },
      },
    ],
    [],
  );

  return (
    <AppLayout>
      <Head title="Hari Libur" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Hari Libur</h1>
          <p className="text-sm text-muted-foreground">
            Dipakai untuk menghindari penjadwalan pekerjaan di hari libur.
          </p>
        </div>
        <DialogTambahHariLibur />
      </div>

      <DataTable
        columns={columns}
        data={hariLibur}
        pencarianPlaceholder="Cari nama hari libur..."
        facetedFilters={[
          {
            columnId: 'BerulangTahunan',
            title: 'Berulang',
            options: [
              { label: 'Setiap Tahun', value: 'Setiap Tahun' },
              { label: 'Sekali', value: 'Sekali' },
            ],
          },
        ]}
        pesanKosong="Belum ada hari libur."
      />
    </AppLayout>
  );
}
