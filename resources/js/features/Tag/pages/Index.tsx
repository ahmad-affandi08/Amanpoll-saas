import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
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
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  tag: Paginasi<Tag>;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const WARNA_BAWAAN = '#64748b';

function DialogFormTag({ tag, wajib }: { tag: Tag | null; wajib: AturanWajib }) {
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label nama="Nama">Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-2">
              <Label nama="Warna">Warna</Label>
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
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function TagIndex({ tag, filter, wajib }: Props) {
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
            <DialogFormTag tag={row.original} wajib={wajib.tag} />
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
    [wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Tag" />
      <KepalaHalaman
        judul="Tag"
        deskripsi="Label bebas untuk menandai dan menyaring data lintas modul."
        aksi={
          <>
            <DialogFormTag tag={null} wajib={wajib.tag} />
          </>
        }
        className="mb-6"
      />

      {tag.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong judul="Belum ada tag." deskripsi="Tambahkan tag pertama untuk mulai menandai data." />
      ) : (
        <DataTable
          columns={columns}
          data={tag.data}
          server={{ meta: tag.meta, filter }}
          ekspor="/kolaborasi/tag/ekspor"
          pencarianPlaceholder="Cari nama tag..."
          pesanKosong="Tidak ada tag yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
