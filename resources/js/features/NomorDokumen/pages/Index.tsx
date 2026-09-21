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
import type { NomorDokumen } from '@/features/NomorDokumen/types';
import { ruteNomorDokumen } from '@/features/NomorDokumen/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  nomorDokumen: NomorDokumen[];
}

function DialogFormPola({ pola }: { pola: NomorDokumen | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    pola
      ? {
          JenisDokumen: pola.JenisDokumen,
          Awalan: pola.Awalan ?? '',
          FormatNomor: pola.FormatNomor,
          ResetPeriode: pola.ResetPeriode,
        }
      : {
          JenisDokumen: '',
          Awalan: '',
          FormatNomor: '{Awalan}/{Nomor:4}/{Tahun}',
          ResetPeriode: 'Tahunan' as const,
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (pola) {
      form.put(ruteNomorDokumen.detail(pola.Id), opsi);
    } else {
      form.post(ruteNomorDokumen.index, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={pola ? 'outline' : 'default'} size={pola ? 'sm' : 'default'}>
          {pola ? 'Ubah' : 'Tambah Pola'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{pola ? 'Ubah Pola Nomor Dokumen' : 'Tambah Pola Nomor Dokumen'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Jenis Dokumen</Label>
            <Input
              value={form.data.JenisDokumen}
              onChange={(e) => form.setData('JenisDokumen', e.target.value)}
              placeholder="PerintahKerja, PesananPembelian, dst."
              disabled={!!pola}
            />
            {form.errors.JenisDokumen && (
              <p className="text-sm text-destructive">{form.errors.JenisDokumen}</p>
            )}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Awalan</Label>
              <Input
                value={form.data.Awalan}
                onChange={(e) => form.setData('Awalan', e.target.value)}
                className="font-mono"
              />
            </div>
            <div className="space-y-2">
              <Label>Reset Periode</Label>
              <Select
                value={form.data.ResetPeriode}
                onValueChange={(v) => form.setData('ResetPeriode', v as 'Tahunan' | 'Bulanan' | 'TidakAda')}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Tahunan">Tahunan</SelectItem>
                  <SelectItem value="Bulanan">Bulanan</SelectItem>
                  <SelectItem value="TidakAda">Tidak Reset</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-2">
            <Label>Format Nomor</Label>
            <Input
              value={form.data.FormatNomor}
              onChange={(e) => form.setData('FormatNomor', e.target.value)}
              className="font-mono"
            />
            <p className="text-xs text-muted-foreground">
              Placeholder: {'{Awalan}'}, {'{Nomor}'} atau {'{Nomor:4}'} (padding), {'{Tahun}'},{' '}
              {'{TahunPendek}'}, {'{Bulan}'}, {'{Periode}'}.
            </p>
            {form.errors.FormatNomor && <p className="text-sm text-destructive">{form.errors.FormatNomor}</p>}
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

export default function NomorDokumenIndex({ nomorDokumen }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (pola: NomorDokumen) => {
    if (
      !(await konfirmasi({
        judul: `Hapus pola nomor "${pola.JenisDokumen}"?`,
        deskripsi: 'Dokumen baru memakai nomor cadangan sampai pola dibuat lagi.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteNomorDokumen.detail(pola.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<NomorDokumen>[]>(
    () => [
      {
        accessorKey: 'JenisDokumen',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis Dokumen" />,
        cell: ({ row }) => <span className="font-medium text-foreground">{row.original.JenisDokumen}</span>,
        meta: { label: 'Jenis Dokumen' },
      },
      {
        accessorKey: 'FormatNomor',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Format" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.FormatNomor}</span>,
        meta: { label: 'Format' },
      },
      {
        accessorKey: 'Pratinjau',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Pratinjau Berikutnya" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.Pratinjau}</span>,
        meta: { label: 'Pratinjau Berikutnya' },
      },
      {
        accessorKey: 'ResetPeriode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Reset" />,
        cell: ({ row }) => <Badge variant="outline">{row.original.ResetPeriode}</Badge>,
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Reset' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormPola pola={row.original} />
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
      <Head title="Nomor Dokumen" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Nomor Dokumen</h1>
          <p className="text-sm text-muted-foreground">Pola penomoran otomatis untuk dokumen operasional.</p>
        </div>
        <DialogFormPola pola={null} />
      </div>

      <DataTable
        columns={columns}
        data={nomorDokumen}
        pencarianPlaceholder="Cari jenis dokumen atau format..."
        facetedFilters={[
          {
            columnId: 'ResetPeriode',
            title: 'Reset',
            options: [
              { label: 'Tahunan', value: 'Tahunan' },
              { label: 'Bulanan', value: 'Bulanan' },
              { label: 'Tidak Reset', value: 'TidakAda' },
            ],
          },
        ]}
        pesanKosong="Belum ada pola nomor dokumen."
      />
    </AppLayout>
  );
}
