import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import type { Merek } from '@/features/Aset/types';
import { ruteMerek } from '@/features/Merek/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  merek: Paginasi<Merek>;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormMerek({ merek, wajib }: { merek: Merek | null; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    merek
      ? { Nama: merek.Nama, NegaraAsal: merek.NegaraAsal ?? '', Website: merek.Website ?? '' }
      : { Nama: '', NegaraAsal: '', Website: '' },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!merek) form.reset();
      },
    };
    if (merek) {
      router.put(ruteMerek.detail(merek.Id), form.data, opsi);
    } else {
      router.post(ruteMerek.index, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={merek ? 'outline' : 'default'} size={merek ? 'sm' : 'default'}>
          {merek ? 'Ubah' : 'Tambah Merek'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{merek ? 'Ubah Merek' : 'Tambah Merek'}</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label nama="Nama">Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-2">
              <Label nama="NegaraAsal">Negara Asal</Label>
              <Input
                value={form.data.NegaraAsal}
                onChange={(e) => form.setData('NegaraAsal', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label nama="Website">Website</Label>
              <Input
                value={form.data.Website}
                onChange={(e) => form.setData('Website', e.target.value)}
                placeholder="https://"
              />
              {form.errors.Website && <p className="text-sm text-destructive">{form.errors.Website}</p>}
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

export default function MerekIndex({ merek, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: Merek) => {
    if (
      !(await konfirmasi({
        judul: `Hapus merek "${item.Nama}"?`,
        deskripsi: 'Merek yang masih dipakai model aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteMerek.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Merek>[]>(
    () => [
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        meta: { label: 'Nama' },
      },
      {
        id: 'NegaraAsal',
        accessorFn: (row) => row.NegaraAsal ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Negara Asal" />,
        cell: ({ row }) => row.original.NegaraAsal ?? '—',
        meta: { label: 'Negara Asal' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormMerek merek={row.original} wajib={wajib.merek} />
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
      <Head title="Merek" />
      <KepalaHalaman
        judul="Merek"
        deskripsi="Katalog merek/produsen untuk model aset."
        aksi={
          <>
            <DialogFormMerek merek={null} wajib={wajib.merek} />
          </>
        }
        className="mb-6"
      />

      {merek.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          judul="Belum ada merek."
          deskripsi="Tambahkan merek pertama untuk dipakai model aset."
        />
      ) : (
        <DataTable
          columns={columns}
          data={merek.data}
          server={{ meta: merek.meta, filter }}
          pencarianPlaceholder="Cari nama merek..."
          pesanKosong="Tidak ada merek yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
