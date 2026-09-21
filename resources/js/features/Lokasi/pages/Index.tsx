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
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import type { Lokasi, KategoriLokasi } from '@/features/Lokasi/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import { ruteLokasi } from '@/features/Lokasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  lokasi: Lokasi[];
  unitOrganisasi: UnitOrganisasi[];
  kategoriLokasi: KategoriLokasi[];
}

const TANPA = '__tanpa__';

function DialogKelolaKategori({ kategoriLokasi }: { kategoriLokasi: KategoriLokasi[] }) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '', Keterangan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteLokasi.kategori, { onSuccess: () => form.reset(), preserveScroll: true });
  };

  const hapus = async (kategori: KategoriLokasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kategori "${kategori.Nama}"?`,
        deskripsi: 'Lokasi yang memakai kategori ini kehilangan penandaannya.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteLokasi.kategoriDetail(kategori.Id), { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Kelola Kategori</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kategori Lokasi</DialogTitle>
        </DialogHeader>
        <div className="space-y-2">
          {kategoriLokasi.map((k) => (
            <div
              key={k.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div>
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                <span className="ml-2 font-mono text-xs text-muted-foreground">{k.Kode}</span>
              </div>
              <Button variant="ghost" size="sm" onClick={() => hapus(k)}>
                Hapus
              </Button>
            </div>
          ))}
          {kategoriLokasi.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada kategori.</p>
          )}
        </div>
        <form onSubmit={submit} className="flex gap-2 border-t border-border pt-4">
          <Input
            placeholder="Kode"
            value={form.data.Kode}
            onChange={(e) => form.setData('Kode', e.target.value)}
            className="w-28 font-mono"
          />
          <Input
            placeholder="Nama kategori"
            value={form.data.Nama}
            onChange={(e) => form.setData('Nama', e.target.value)}
            className="flex-1"
          />
          <Button type="submit" disabled={form.processing}>
            Tambah
          </Button>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogFormLokasi({
  lokasi,
  unitOrganisasi,
  kategoriLokasi,
}: {
  lokasi: Lokasi | null;
  unitOrganisasi: UnitOrganisasi[];
  kategoriLokasi: KategoriLokasi[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    lokasi
      ? {
          Kode: lokasi.Kode,
          Nama: lokasi.Nama,
          Alamat: lokasi.Alamat ?? '',
          Lantai: lokasi.Lantai ?? '',
          Status: lokasi.Status,
          UnitOrganisasiId: lokasi.UnitOrganisasiId ?? TANPA,
          KategoriLokasiId: lokasi.KategoriLokasiId ?? TANPA,
        }
      : {
          Kode: '',
          Nama: '',
          Alamat: '',
          Lantai: '',
          Status: 'Aktif' as const,
          UnitOrganisasiId: TANPA,
          KategoriLokasiId: TANPA,
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      UnitOrganisasiId: form.data.UnitOrganisasiId === TANPA ? null : form.data.UnitOrganisasiId,
      KategoriLokasiId: form.data.KategoriLokasiId === TANPA ? null : form.data.KategoriLokasiId,
    };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (lokasi) {
      router.put(ruteLokasi.detail(lokasi.Id), payload, opsi);
    } else {
      router.post(ruteLokasi.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={lokasi ? 'outline' : 'default'} size={lokasi ? 'sm' : 'default'}>
          {lokasi ? 'Ubah' : 'Tambah Lokasi'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{lokasi ? 'Ubah Lokasi' : 'Tambah Lokasi'}</DialogTitle>
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
              <Label>Unit Organisasi</Label>
              <Select
                value={form.data.UnitOrganisasiId}
                onValueChange={(v) => form.setData('UnitOrganisasiId', v)}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tidak ditautkan</SelectItem>
                  {unitOrganisasi.map((u) => (
                    <SelectItem key={u.Id} value={u.Id}>
                      {u.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Kategori</Label>
              <Select
                value={form.data.KategoriLokasiId}
                onValueChange={(v) => form.setData('KategoriLokasiId', v)}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tanpa kategori</SelectItem>
                  {kategoriLokasi.map((k) => (
                    <SelectItem key={k.Id} value={k.Id}>
                      {k.Nama}
                    </SelectItem>
                  ))}
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
            <Label>Alamat</Label>
            <Input value={form.data.Alamat} onChange={(e) => form.setData('Alamat', e.target.value)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
        {lokasi && <PanelKolaborasi jenisEntitas="Lokasi" entitasId={lokasi.Id} />}
      </DialogContent>
    </Dialog>
  );
}

export default function LokasiIndex({ lokasi, unitOrganisasi, kategoriLokasi }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: Lokasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus lokasi "${item.Nama}"?`,
        deskripsi: 'Lokasi yang masih memiliki sub-lokasi atau aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteLokasi.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Lokasi>[]>(
    () => [
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
        id: 'NamaKategoriLokasi',
        accessorFn: (row) => row.NamaKategoriLokasi ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => row.original.NamaKategoriLokasi ?? '—',
        meta: { label: 'Kategori' },
      },
      {
        id: 'NamaUnitOrganisasi',
        accessorFn: (row) => row.NamaUnitOrganisasi ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Unit" />,
        cell: ({ row }) => row.original.NamaUnitOrganisasi ?? '—',
        meta: { label: 'Unit' },
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
            <DialogFormLokasi
              lokasi={row.original}
              unitOrganisasi={unitOrganisasi}
              kategoriLokasi={kategoriLokasi}
            />
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
    [unitOrganisasi, kategoriLokasi],
  );

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

      <DataTable
        columns={columns}
        data={lokasi}
        pencarianPlaceholder="Cari nama atau kode lokasi..."
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
        pesanKosong="Belum ada lokasi."
        ilustrasiKosong="/assets/3d/lokasi.webp"
      />
    </AppLayout>
  );
}
