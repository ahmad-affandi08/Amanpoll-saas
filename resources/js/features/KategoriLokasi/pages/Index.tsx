import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { PageHeader } from '@/components/shared/PageHeader';
import { ruteKategoriLokasi } from '@/features/KategoriLokasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface KategoriLokasi {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  DibuatPada: string;
}

interface Props {
  kategoriLokasi: KategoriLokasi[];
}

function DialogFormKategoriLokasi({ kategori }: { kategori: KategoriLokasi | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: kategori?.Kode ?? '',
    Nama: kategori?.Nama ?? '',
    Keterangan: kategori?.Keterangan ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!kategori) form.reset();
      },
    };

    if (kategori) {
      router.put(ruteKategoriLokasi.detail(kategori.Id), form.data, opsi);
    } else {
      router.post(ruteKategoriLokasi.index, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={kategori ? 'outline' : 'default'} size={kategori ? 'sm' : 'default'}>
          {kategori ? 'Ubah' : 'Tambah Kategori'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{kategori ? 'Ubah Kategori Lokasi' : 'Tambah Kategori Lokasi'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Kode">Kode</Label>
            <Input
              id="Kode"
              value={form.data.Kode}
              onChange={(e) => form.setData('Kode', e.target.value)}
              required
            />
            {form.errors.Kode ? <p className="text-sm text-destructive">{form.errors.Kode}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Nama">Nama</Label>
            <Input
              id="Nama"
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
              required
            />
            {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Keterangan">Keterangan</Label>
            <Textarea
              id="Keterangan"
              rows={3}
              value={form.data.Keterangan}
              onChange={(e) => form.setData('Keterangan', e.target.value)}
            />
            {form.errors.Keterangan ? (
              <p className="text-sm text-destructive">{form.errors.Keterangan}</p>
            ) : null}
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

export default function Index({ kategoriLokasi }: Props) {
  const konfirmasi = useKonfirmasi();

  const hapus = async (kategori: KategoriLokasi) => {
    const lanjut = await konfirmasi({
      judul: `Hapus kategori "${kategori.Nama}"?`,
      deskripsi: 'Kategori yang masih dipakai lokasi tidak dapat dihapus.',
      ragam: 'bahaya',
    });

    if (!lanjut) {
      return;
    }

    router.delete(ruteKategoriLokasi.detail(kategori.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<KategoriLokasi>[]>(
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
        meta: { label: 'Nama', kartu: 'judul' },
      },
      {
        id: 'Keterangan',
        accessorFn: (row) => row.Keterangan ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Keterangan" />,
        cell: ({ row }) => row.original.Keterangan ?? '—',
        meta: { label: 'Keterangan' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormKategoriLokasi kategori={row.original} />
            <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>
              Hapus
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    [],
  );

  return (
    <AppLayout>
      <Head title="Kategori Lokasi" />
      <PageHeader
        judul="Kategori Lokasi"
        deskripsi="Klasifikasi lokasi, misalnya gedung, lantai, atau ruangan."
        aksi={<DialogFormKategoriLokasi kategori={null} />}
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={kategoriLokasi}
        pencarianPlaceholder="Cari nama atau kode kategori..."
        pesanKosong="Belum ada kategori lokasi."
      />
    </AppLayout>
  );
}
