import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
import type { KanalNotifikasi, TemplatNotifikasi } from '@/features/Notifikasi/types';
import { KANAL_NOTIFIKASI } from '@/features/Notifikasi/status';
import { ruteTemplatNotifikasi } from '@/features/TemplatNotifikasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  templatNotifikasi: Paginasi<TemplatNotifikasi>;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormTemplat({ templat, wajib }: { templat: TemplatNotifikasi | null; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: templat?.Kode ?? '',
    Kanal: templat?.Kanal ?? 'InApp',
    JudulTemplat: templat?.JudulTemplat ?? '',
    IsiTemplat: templat?.IsiTemplat ?? '',
    Aktif: templat?.Aktif ?? true,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!templat) form.reset();
      },
    };
    if (templat) {
      router.put(ruteTemplatNotifikasi.detail(templat.Id), form.data, opsi);
    } else {
      router.post(ruteTemplatNotifikasi.index, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={templat ? 'outline' : 'default'} size={templat ? 'sm' : 'default'}>
          {templat ? 'Ubah' : 'Tambah Templat'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{templat ? 'Ubah Templat' : 'Tambah Templat'}</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label nama="Kode">Kode</Label>
              <Input value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-2">
              <Label nama="Kanal">Kanal</Label>
              <Select
                value={form.data.Kanal}
                onValueChange={(v) => form.setData('Kanal', v as KanalNotifikasi)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {KANAL_NOTIFIKASI.map((kanal) => (
                    <SelectItem key={kanal.value} value={kanal.value}>
                      {kanal.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label nama="JudulTemplat">Judul Templat</Label>
              <Input
                value={form.data.JudulTemplat}
                onChange={(e) => form.setData('JudulTemplat', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label nama="IsiTemplat">Isi Templat</Label>
              <Textarea
                value={form.data.IsiTemplat}
                onChange={(e) => form.setData('IsiTemplat', e.target.value)}
                rows={4}
              />
              {form.errors.IsiTemplat && <p className="text-sm text-destructive">{form.errors.IsiTemplat}</p>}
            </div>
            <div className="flex items-center gap-2">
              <Switch checked={form.data.Aktif} onCheckedChange={(v) => form.setData('Aktif', v)} />
              <Label>Aktif</Label>
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

export default function TemplatNotifikasiIndex({ templatNotifikasi, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: TemplatNotifikasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus templat "${item.Kode}"?`,
        deskripsi: 'Notifikasi yang memakai kode ini akan gagal dikirim sampai templat diganti.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteTemplatNotifikasi.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<TemplatNotifikasi>[]>(
    () => [
      {
        accessorKey: 'Kode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        meta: { label: 'Kode' },
      },
      {
        accessorKey: 'Kanal',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kanal" />,
        cell: ({ row }) => <Badge variant="secondary">{row.original.Kanal}</Badge>,
        meta: { label: 'Kanal' },
      },
      {
        accessorKey: 'Aktif',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Aktif ? 'default' : 'outline'}>
            {row.original.Aktif ? 'Aktif' : 'Nonaktif'}
          </Badge>
        ),
        meta: { label: 'Status' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormTemplat templat={row.original} wajib={wajib.templat} />
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
      <Head title="Templat Notifikasi" />
      <KepalaHalaman
        judul="Templat Notifikasi"
        deskripsi="Kelola isi pesan notifikasi per peristiwa dan kanal."
        aksi={
          <>
            <DialogFormTemplat templat={null} wajib={wajib.templat} />
          </>
        }
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={templatNotifikasi.data}
        server={{ meta: templatNotifikasi.meta, filter }}
        ekspor={ruteTemplatNotifikasi.ekspor}
        facetedFilters={[
          {
            columnId: 'Kanal',
            title: 'Kanal',
            options: KANAL_NOTIFIKASI,
          },
        ]}
        pencarianPlaceholder="Cari kode atau judul templat..."
        pesanKosong={
          adaPenyaringAktif(filter) ? 'Tidak ada templat yang cocok.' : 'Belum ada templat notifikasi.'
        }
        ilustrasiKosong="/assets/3d/notifikasi.webp"
      />
    </KerangkaAplikasi>
  );
}
