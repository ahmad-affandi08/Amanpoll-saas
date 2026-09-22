import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

interface Kampanye {
  Id: string;
  Kode: string;
  Nama: string;
  Objective: string;
  Status: string;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  Channel: string[];
  JumlahKunjungan: number;
}

interface Pilihan {
  Status: string[];
  Objective: string[];
  Channel: string[];
}

interface Props {
  kampanye: Kampanye[];
  pilihan: Pilihan;
}

function DialogFormKampanye({ kampanye, pilihan }: { kampanye: Kampanye | null; pilihan: Pilihan }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: kampanye?.Kode ?? '',
    Nama: kampanye?.Nama ?? '',
    Objective: kampanye?.Objective ?? pilihan.Objective[0],
    Status: kampanye?.Status ?? pilihan.Status[0],
    MulaiPada: kampanye?.MulaiPada ?? '',
    SelesaiPada: kampanye?.SelesaiPada ?? '',
    Channel: kampanye?.Channel ?? [],
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!kampanye) form.reset();
      },
    };

    if (kampanye) {
      router.put(`/admin-platform/pemasaran/kampanye/${kampanye.Id}`, form.data, opsi);
    } else {
      router.post('/admin-platform/pemasaran/kampanye', form.data, opsi);
    }
  };

  const ubahChannel = (channel: string, dipilih: boolean) => {
    form.setData(
      'Channel',
      dipilih ? [...form.data.Channel, channel] : form.data.Channel.filter((satu) => satu !== channel),
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={kampanye ? 'outline' : 'default'} size={kampanye ? 'sm' : 'default'}>
          {kampanye ? 'Ubah' : 'Tambah Kampanye'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{kampanye ? 'Ubah Kampanye' : 'Tambah Kampanye'}</DialogTitle>
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
            <p className="text-sm text-muted-foreground">
              Dipakai sebagai <code className="font-mono">utm_campaign</code> pada tautan iklan.
            </p>
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

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Objective">Objective</Label>
              <Select value={form.data.Objective} onValueChange={(v) => form.setData('Objective', v)}>
                <SelectTrigger id="Objective">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.Objective.map((satu) => (
                    <SelectItem key={satu} value={satu}>
                      {satu}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid gap-2">
              <Label htmlFor="Status">Status</Label>
              <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v)}>
                <SelectTrigger id="Status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.Status.map((satu) => (
                    <SelectItem key={satu} value={satu}>
                      {satu}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="MulaiPada">Mulai</Label>
              <Input
                id="MulaiPada"
                type="date"
                value={form.data.MulaiPada}
                onChange={(e) => form.setData('MulaiPada', e.target.value)}
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="SelesaiPada">Selesai</Label>
              <Input
                id="SelesaiPada"
                type="date"
                value={form.data.SelesaiPada}
                onChange={(e) => form.setData('SelesaiPada', e.target.value)}
              />
              {form.errors.SelesaiPada ? (
                <p className="text-sm text-destructive">{form.errors.SelesaiPada}</p>
              ) : null}
            </div>
          </div>

          <fieldset className="grid gap-2">
            <legend className="text-sm font-medium">Channel</legend>
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
              {pilihan.Channel.map((satu) => (
                <label key={satu} className="flex items-center gap-2 text-sm">
                  <Checkbox
                    checked={form.data.Channel.includes(satu)}
                    onCheckedChange={(nilai) => ubahChannel(satu, nilai === true)}
                  />
                  {satu}
                </label>
              ))}
            </div>
          </fieldset>

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

export default function KampanyeHalaman({ kampanye, pilihan }: Props) {
  const columns = useMemo<ColumnDef<Kampanye>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kampanye" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </div>
        ),
        meta: { label: 'Kampanye', kartu: 'judul' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => <Badge variant="secondary">{row.original.Status}</Badge>,
        meta: { label: 'Status' },
      },
      {
        id: 'Objective',
        accessorFn: (row) => row.Objective,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Objective" />,
        meta: { label: 'Objective' },
      },
      {
        id: 'Channel',
        header: 'Channel',
        cell: ({ row }) =>
          row.original.Channel.length === 0 ? (
            '—'
          ) : (
            <div className="flex flex-wrap gap-1">
              {row.original.Channel.map((satu) => (
                <Badge key={satu} variant="outline">
                  {satu}
                </Badge>
              ))}
            </div>
          ),
        enableSorting: false,
        meta: { label: 'Channel' },
      },
      {
        id: 'JumlahKunjungan',
        accessorFn: (row) => row.JumlahKunjungan,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kunjungan" />,
        meta: { label: 'Kunjungan' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end">
            <DialogFormKampanye kampanye={row.original} pilihan={pilihan} />
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    [pilihan],
  );

  return (
    <KerangkaPlatform>
      <Head title="Kampanye" />

      <PageHeader
        judul="Kampanye"
        deskripsi="Kode kampanye menjadi utm_campaign pada tautan iklan, sehingga kunjungannya tertaut otomatis."
        tanpaBreadcrumb
        aksi={<DialogFormKampanye kampanye={null} pilihan={pilihan} />}
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={kampanye}
        kartuDiPonsel
        pencarianPlaceholder="Cari nama atau kode kampanye..."
        pesanKosong="Belum ada kampanye."
      />
    </KerangkaPlatform>
  );
}
