import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { KategoriKeluhan, PrioritasKeluhan } from '@/features/Keluhan/types';
import { ruteKategoriKeluhan } from '@/features/KategoriKeluhan/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';

interface Ringkas {
  Id: string;
  Nama: string;
}
interface Props {
  kategori: KategoriKeluhan[];
  tingkatLayanan: Ringkas[];
  peran: Ringkas[];
}
const TANPA = '__tanpa__';
const PRIORITAS: PrioritasKeluhan[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];

function DialogKategori({
  item,
  kategori,
  tingkatLayanan,
  peran,
}: {
  item: KategoriKeluhan | null;
  kategori: KategoriKeluhan[];
  tingkatLayanan: Ringkas[];
  peran: Ringkas[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: item?.Kode ?? '',
    Nama: item?.Nama ?? '',
    IndukId: item?.IndukId ?? TANPA,
    TingkatLayananId: item?.TingkatLayananId ?? TANPA,
    PrioritasBawaan: item?.PrioritasBawaan ?? ('Normal' as PrioritasKeluhan),
    AsetWajib: item?.AsetWajib ?? false,
    PeranPenanggungJawabId: item?.PeranPenanggungJawabId ?? TANPA,
    Aktif: item?.Aktif ?? true,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    form.transform((data) => ({
      ...data,
      IndukId: data.IndukId === TANPA ? null : data.IndukId,
      TingkatLayananId: data.TingkatLayananId === TANPA ? null : data.TingkatLayananId,
      PeranPenanggungJawabId: data.PeranPenanggungJawabId === TANPA ? null : data.PeranPenanggungJawabId,
    }));
    item ? form.put(ruteKategoriKeluhan.detail(item.Id), opsi) : form.post(ruteKategoriKeluhan.index, opsi);
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={item ? 'outline' : 'default'} size={item ? 'sm' : 'default'}>
          {item ? 'Ubah' : 'Tambah Kategori'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{item ? 'Ubah' : 'Tambah'} Kategori Keluhan</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Kode</Label>
              <Input value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Kategori Induk</Label>
            <Select value={form.data.IndukId} onValueChange={(v) => form.setData('IndukId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tanpa induk</SelectItem>
                {kategori
                  .filter((k) => k.Id !== item?.Id)
                  .map((k) => (
                    <SelectItem key={k.Id} value={k.Id}>
                      {k.Nama}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Tingkat Layanan</Label>
              <Select
                value={form.data.TingkatLayananId}
                onValueChange={(v) => form.setData('TingkatLayananId', v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tanpa SLA</SelectItem>
                  {tingkatLayanan.map((sla) => (
                    <SelectItem key={sla.Id} value={sla.Id}>
                      {sla.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Prioritas Bawaan</Label>
              <Select
                value={form.data.PrioritasBawaan}
                onValueChange={(v) => form.setData('PrioritasBawaan', v as PrioritasKeluhan)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PRIORITAS.map((p) => (
                    <SelectItem key={p} value={p}>
                      {p}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Routing ke Peran</Label>
            <Select
              value={form.data.PeranPenanggungJawabId}
              onValueChange={(v) => form.setData('PeranPenanggungJawabId', v)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tanpa routing</SelectItem>
                {peran.map((p) => (
                  <SelectItem key={p.Id} value={p.Id}>
                    {p.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="flex flex-wrap gap-6">
            <label className="flex items-center gap-2 text-sm">
              <Switch checked={form.data.AsetWajib} onCheckedChange={(v) => form.setData('AsetWajib', v)} />{' '}
              Aset wajib
            </label>
            <label className="flex items-center gap-2 text-sm">
              <Switch checked={form.data.Aktif} onCheckedChange={(v) => form.setData('Aktif', v)} /> Aktif
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

export default function KategoriKeluhanIndex({ kategori, tingkatLayanan, peran }: Props) {
  const konfirmasi = useKonfirmasi();
  const columns = useMemo<ColumnDef<KategoriKeluhan>[]>(
    () => [
      {
        id: 'nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </div>
        ),
        meta: { label: 'Kategori' },
      },
      {
        accessorKey: 'PrioritasBawaan',
        header: 'Prioritas',
        cell: ({ row }) => <Badge>{row.original.PrioritasBawaan}</Badge>,
      },
      {
        id: 'sla',
        accessorFn: (row) => row.NamaTingkatLayanan ?? '',
        header: 'SLA',
        cell: ({ row }) => row.original.NamaTingkatLayanan ?? '—',
      },
      {
        id: 'aturan',
        header: 'Aturan',
        cell: ({ row }) => (
          <div className="text-sm">
            <div>{row.original.AsetWajib ? 'Aset wajib' : 'Aset opsional'}</div>
            <div className="text-muted-foreground">
              {row.original.NamaPeranPenanggungJawab ?? 'Tanpa routing'}
            </div>
          </div>
        ),
      },
      {
        id: 'status',
        accessorFn: (row) => (row.Aktif ? 'Aktif' : 'Nonaktif'),
        header: 'Status',
        cell: ({ row }) => (
          <Badge variant={row.original.Aktif ? 'sukses' : 'netral'}>
            {row.original.Aktif ? 'Aktif' : 'Nonaktif'}
          </Badge>
        ),
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogKategori
              item={row.original}
              kategori={kategori}
              tingkatLayanan={tingkatLayanan}
              peran={peran}
            />
            <Button
              variant="ghost"
              size="sm"
              onClick={async () => {
                const lanjut = await konfirmasi({
                  judul: `Hapus kategori "${row.original.Nama}"?`,
                  deskripsi:
                    'Keluhan yang sudah memakai kategori ini tetap tersimpan dengan kategori kosong.',
                  ragam: 'bahaya',
                });
                if (lanjut) router.delete(ruteKategoriKeluhan.detail(row.original.Id));
              }}
            >
              Hapus
            </Button>
          </div>
        ),
      },
    ],
    [kategori, tingkatLayanan, peran],
  );

  return (
    <AppLayout>
      <Head title="Kategori Keluhan" />
      <PageHeader
        judul="Kategori Keluhan"
        deskripsi="Atur prioritas bawaan, kebutuhan aset, SLA, dan routing triage."
        aksi={
          <>
            <DialogKategori item={null} kategori={kategori} tingkatLayanan={tingkatLayanan} peran={peran} />
          </>
        }
        className="mb-6"
      />
      <DataTable
        columns={columns}
        data={kategori}
        pencarianPlaceholder="Cari kategori..."
        pesanKosong="Belum ada kategori keluhan."
      />
    </AppLayout>
  );
}
