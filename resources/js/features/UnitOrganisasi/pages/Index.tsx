import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import { ruteUnitOrganisasi } from '@/features/UnitOrganisasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  unitOrganisasi: UnitOrganisasi[];
}

const TANPA_INDUK = '__tanpa_induk__';

function DialogFormUnit({ unit, semuaUnit }: { unit: UnitOrganisasi | null; semuaUnit: UnitOrganisasi[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    unit
      ? {
          Kode: unit.Kode,
          Nama: unit.Nama,
          Jenis: unit.Jenis,
          Status: unit.Status,
          IndukId: unit.IndukId ?? TANPA_INDUK,
        }
      : { Kode: '', Nama: '', Jenis: 'Unit', Status: 'Aktif' as const, IndukId: TANPA_INDUK },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, IndukId: form.data.IndukId === TANPA_INDUK ? null : form.data.IndukId };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (unit) {
      router.put(ruteUnitOrganisasi.detail(unit.Id), payload, opsi);
    } else {
      router.post(ruteUnitOrganisasi.index, payload, opsi);
    }
  };

  const pilihanInduk = semuaUnit.filter((u) => u.Id !== unit?.Id);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={unit ? 'outline' : 'default'} size={unit ? 'sm' : 'default'}>
          {unit ? 'Ubah' : 'Tambah Unit'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{unit ? 'Ubah Unit Organisasi' : 'Tambah Unit Organisasi'}</DialogTitle>
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
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Jenis</Label>
              <Input
                value={form.data.Jenis}
                onChange={(e) => form.setData('Jenis', e.target.value)}
                placeholder="Divisi, Departemen, dst."
              />
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select
                value={form.data.Status}
                onValueChange={(v) => form.setData('Status', v as 'Aktif' | 'Nonaktif')}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Aktif">Aktif</SelectItem>
                  <SelectItem value="Nonaktif">Nonaktif</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-2">
            <Label>Induk</Label>
            <Select value={form.data.IndukId} onValueChange={(v) => form.setData('IndukId', v)}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_INDUK}>Tanpa induk</SelectItem>
                {pilihanInduk.map((u) => (
                  <SelectItem key={u.Id} value={u.Id}>
                    {u.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.IndukId && <p className="text-sm text-destructive">{form.errors.IndukId}</p>}
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

export default function UnitOrganisasiIndex({ unitOrganisasi }: Props) {
  const konfirmasi = useKonfirmasi();
  const namaIndukDari = useMemo(() => {
    const peta = new Map(unitOrganisasi.map((u) => [u.Id, u.Nama]));
    return (indukId: string | null) => (indukId ? (peta.get(indukId) ?? '—') : '—');
  }, [unitOrganisasi]);

  const hapus = async (unit: UnitOrganisasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus unit "${unit.Nama}"?`,
        deskripsi: 'Unit yang masih memiliki sub-unit, pengguna, atau aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteUnitOrganisasi.detail(unit.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<UnitOrganisasi>[]>(
    () => [
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        meta: { label: 'Nama' },
      },
      {
        accessorKey: 'Kode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.Kode}</span>,
        meta: { label: 'Kode' },
      },
      {
        accessorKey: 'Jenis',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
        meta: { label: 'Jenis' },
      },
      {
        id: 'Induk',
        accessorFn: (row) => namaIndukDari(row.IndukId),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Induk" />,
        meta: { label: 'Induk' },
      },
      {
        accessorKey: 'Status',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Status === 'Aktif' ? 'default' : 'outline'}>
            {row.original.Status}
          </Badge>
        ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Status' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormUnit unit={row.original} semuaUnit={unitOrganisasi} />
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
    [unitOrganisasi, namaIndukDari],
  );

  return (
    <AppLayout>
      <Head title="Unit Organisasi" />
      <PageHeader
        judul="Unit Organisasi"
        deskripsi="Kelola struktur divisi dan hierarki organisasi."
        aksi={
          <>
            <DialogFormUnit unit={null} semuaUnit={unitOrganisasi} />
          </>
        }
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={unitOrganisasi}
        pencarianPlaceholder="Cari nama, kode, atau jenis unit..."
        facetedFilters={[
          {
            columnId: 'Status',
            title: 'Status',
            options: [
              { label: 'Aktif', value: 'Aktif' },
              { label: 'Nonaktif', value: 'Nonaktif' },
            ],
          },
        ]}
        pesanKosong="Belum ada unit organisasi."
      />
    </AppLayout>
  );
}
