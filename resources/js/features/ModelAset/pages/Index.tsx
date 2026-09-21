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
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { KategoriAset, Merek, ModelAset } from '@/features/Aset/types';
import { ruteModelAset } from '@/features/ModelAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  modelAset: ModelAset[];
  kategoriAset: KategoriAset[];
  merek: Merek[];
}

const TANPA = '__tanpa__';

function DialogFormModelAset({
  model,
  kategoriAset,
  merek,
}: {
  model: ModelAset | null;
  kategoriAset: KategoriAset[];
  merek: Merek[];
}) {
  const [buka, setBuka] = useState(false);
  const [errorSpesifikasi, setErrorSpesifikasi] = useState<string | null>(null);
  const form = useForm(
    model
      ? {
          KategoriAsetId: model.KategoriAsetId,
          MerekId: model.MerekId ?? TANPA,
          KodeModel: model.KodeModel ?? '',
          Nama: model.Nama,
          Produsen: model.Produsen ?? '',
          Spesifikasi: model.Spesifikasi ? JSON.stringify(model.Spesifikasi, null, 2) : '',
          IntervalPemeliharaanHari: model.IntervalPemeliharaanHari?.toString() ?? '',
          IntervalKalibrasiHari: model.IntervalKalibrasiHari?.toString() ?? '',
          UmurManfaatBulan: model.UmurManfaatBulan?.toString() ?? '',
        }
      : {
          KategoriAsetId: kategoriAset[0]?.Id ?? '',
          MerekId: TANPA,
          KodeModel: '',
          Nama: '',
          Produsen: '',
          Spesifikasi: '',
          IntervalPemeliharaanHari: '',
          IntervalKalibrasiHari: '',
          UmurManfaatBulan: '',
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    let spesifikasi: Record<string, string | number | boolean | null> | null = null;
    if (form.data.Spesifikasi.trim() !== '') {
      try {
        spesifikasi = JSON.parse(form.data.Spesifikasi);
      } catch {
        setErrorSpesifikasi('Spesifikasi harus berupa JSON valid, mis. {"Daya": "5 kW"}');
        return;
      }
    }
    setErrorSpesifikasi(null);
    const payload = {
      ...form.data,
      MerekId: form.data.MerekId === TANPA ? null : form.data.MerekId,
      Spesifikasi: spesifikasi,
    };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!model) form.reset();
      },
    };
    if (model) {
      router.put(ruteModelAset.detail(model.Id), payload, opsi);
    } else {
      router.post(ruteModelAset.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={model ? 'outline' : 'default'} size={model ? 'sm' : 'default'}>
          {model ? 'Ubah' : 'Tambah Model'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{model ? 'Ubah Model Aset' : 'Tambah Model Aset'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kategori</Label>
              <Select
                value={form.data.KategoriAsetId}
                onValueChange={(v) => form.setData('KategoriAsetId', v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {kategoriAset.map((k) => (
                    <SelectItem key={k.Id} value={k.Id}>
                      {k.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.KategoriAsetId && (
                <p className="text-sm text-destructive">{form.errors.KategoriAsetId}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label>Merek</Label>
              <Select value={form.data.MerekId} onValueChange={(v) => form.setData('MerekId', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tanpa merek</SelectItem>
                  {merek.map((m) => (
                    <SelectItem key={m.Id} value={m.Id}>
                      {m.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode Model</Label>
              <Input
                value={form.data.KodeModel}
                onChange={(e) => form.setData('KodeModel', e.target.value)}
                className="font-mono"
              />
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>Produsen</Label>
            <Input value={form.data.Produsen} onChange={(e) => form.setData('Produsen', e.target.value)} />
          </div>
          <div className="grid grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Interval Pemeliharaan (hari)</Label>
              <Input
                type="number"
                min={1}
                value={form.data.IntervalPemeliharaanHari}
                onChange={(e) => form.setData('IntervalPemeliharaanHari', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Interval Kalibrasi (hari)</Label>
              <Input
                type="number"
                min={1}
                value={form.data.IntervalKalibrasiHari}
                onChange={(e) => form.setData('IntervalKalibrasiHari', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Umur Manfaat (bulan)</Label>
              <Input
                type="number"
                min={1}
                value={form.data.UmurManfaatBulan}
                onChange={(e) => form.setData('UmurManfaatBulan', e.target.value)}
              />
            </div>
          </div>
          <div className="space-y-2">
            <Label>Spesifikasi Teknis (JSON, opsional)</Label>
            <Textarea
              value={form.data.Spesifikasi}
              onChange={(e) => form.setData('Spesifikasi', e.target.value)}
              rows={4}
              placeholder='{"Daya": "5 kW", "Tegangan": "220V"}'
              className="font-mono text-xs"
            />
            {errorSpesifikasi && <p className="text-sm text-destructive">{errorSpesifikasi}</p>}
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

export default function ModelAsetIndex({ modelAset, kategoriAset, merek }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: ModelAset) => {
    if (
      !(await konfirmasi({
        judul: `Hapus model "${item.Nama}"?`,
        deskripsi: 'Model yang masih dipakai aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteModelAset.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<ModelAset>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.KodeModel ?? ''}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            {row.original.KodeModel && (
              <div className="font-mono text-xs text-muted-foreground">{row.original.KodeModel}</div>
            )}
          </div>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'NamaKategoriAset',
        accessorFn: (row) => row.NamaKategoriAset ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        meta: { label: 'Kategori' },
      },
      {
        id: 'NamaMerek',
        accessorFn: (row) => row.NamaMerek ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Merek" />,
        cell: ({ row }) => row.original.NamaMerek ?? '—',
        meta: { label: 'Merek' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormModelAset model={row.original} kategoriAset={kategoriAset} merek={merek} />
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
    [kategoriAset, merek],
  );

  return (
    <AppLayout>
      <Head title="Model Aset" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Model Aset</h1>
          <p className="text-sm text-muted-foreground">
            Master model/tipe aset lengkap dengan metadata teknis dan interval pemeliharaan/kalibrasi.
          </p>
        </div>
        <DialogFormModelAset model={null} kategoriAset={kategoriAset} merek={merek} />
      </div>

      <DataTable
        columns={columns}
        data={modelAset}
        pencarianPlaceholder="Cari nama atau kode model..."
        pesanKosong="Belum ada model aset."
      />
    </AppLayout>
  );
}
