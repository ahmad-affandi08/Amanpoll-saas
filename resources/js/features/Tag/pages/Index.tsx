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
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { Tag } from '@/features/Kolaborasi/types';
import { ruteTag } from '@/features/Tag/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  tag: Tag[];
}

const WARNA_BAWAAN = '#64748b';

function DialogFormTag({ tag }: { tag: Tag | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nama: tag?.Nama ?? '', Warna: tag?.Warna ?? WARNA_BAWAAN });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!tag) form.reset();
      },
    };
    if (tag) {
      router.put(ruteTag.detail(tag.Id), form.data, opsi);
    } else {
      router.post(ruteTag.index, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={tag ? 'outline' : 'default'} size={tag ? 'sm' : 'default'}>
          {tag ? 'Ubah' : 'Tambah Tag'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{tag ? 'Ubah Tag' : 'Tambah Tag'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <div className="space-y-2">
            <Label>Warna</Label>
            <div className="flex items-center gap-2">
              <input
                type="color"
                value={form.data.Warna}
                onChange={(e) => form.setData('Warna', e.target.value)}
                className="h-10 w-14 rounded-md border border-input"
              />
              <Input
                value={form.data.Warna}
                onChange={(e) => form.setData('Warna', e.target.value)}
                className="font-mono"
              />
            </div>
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

export default function TagIndex({ tag }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: Tag) => {
    if (
      !(await konfirmasi({
        judul: `Hapus tag "${item.Nama}"?`,
        deskripsi: `Semua penandaan pada entitas lain akan ikut terhapus.`,
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteTag.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Tag>[]>(
    () => [
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <Badge
            style={{ backgroundColor: row.original.Warna ?? undefined, color: '#fff' }}
            variant={row.original.Warna ? undefined : 'secondary'}
          >
            {row.original.Nama}
          </Badge>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormTag tag={row.original} />
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
      <Head title="Tag" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Tag</h1>
          <p className="text-sm text-muted-foreground">
            Label bebas untuk menandai dan menyaring data lintas modul.
          </p>
        </div>
        <DialogFormTag tag={null} />
      </div>

      <DataTable
        columns={columns}
        data={tag}
        pencarianPlaceholder="Cari nama tag..."
        pesanKosong="Belum ada tag."
      />
    </AppLayout>
  );
}
