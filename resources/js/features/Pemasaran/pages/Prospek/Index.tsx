import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Download, Upload } from 'lucide-react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { Prospek, TahapRingkas } from '@/features/Pemasaran/types';

interface Props {
  prospek: Prospek[];
  tahap: TahapRingkas[];
  filter: { tahap?: string; cari?: string };
}

function DialogProspekBaru() {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nama: '', Email: '', Telepon: '', Perusahaan: '', Jabatan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/admin-platform/pemasaran/prospek', {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Prospek</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Prospek</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="grid gap-4">
          {(
            [
              ['Nama', 'Nama', true],
              ['Email', 'Email', false],
              ['Telepon', 'Telepon', false],
              ['Perusahaan', 'Perusahaan', false],
              ['Jabatan', 'Jabatan', false],
            ] as const
          ).map(([kunci, label, wajib]) => (
            <div key={kunci} className="grid gap-2">
              <Label htmlFor={kunci}>{label}</Label>
              <Input
                id={kunci}
                value={form.data[kunci]}
                onChange={(e) => form.setData(kunci, e.target.value)}
                required={wajib}
              />
              {form.errors[kunci] ? (
                <p className="text-sm text-destructive">{form.errors[kunci]}</p>
              ) : null}
            </div>
          ))}
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

function DialogImpor() {
  const [buka, setBuka] = useState(false);
  const form = useForm<{ Berkas: File | null }>({ Berkas: null });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/admin-platform/pemasaran/prospek/impor', {
      forceFormData: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <Upload aria-hidden="true" className="size-4" />
          Impor CSV
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Impor Prospek</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Berkas">Berkas CSV</Label>
            <Input
              id="Berkas"
              type="file"
              accept=".csv,text/csv"
              onChange={(e) => form.setData('Berkas', e.target.files?.[0] ?? null)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Kolom yang dibaca: Nama, Email, Telepon, WhatsApp, Jabatan, Perusahaan, Industri, Kota,
              Negara. Baris tanpa nama dilewati.
            </p>
            {form.errors.Berkas ? <p className="text-sm text-destructive">{form.errors.Berkas}</p> : null}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Impor
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PemasaranProspekIndex({ prospek, tahap, filter }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');

  const saring = (tahapKode: string | null) => {
    router.get(
      '/admin-platform/pemasaran/prospek',
      { tahap: tahapKode ?? undefined, cari: cari || undefined },
      { preserveState: true, preserveScroll: true },
    );
  };

  const columns = useMemo<ColumnDef<Prospek>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Email ?? ''}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Prospek" />,
        cell: ({ row }) => (
          <Link
            href={`/admin-platform/pemasaran/prospek/${row.original.Id}`}
            className="block hover:underline"
          >
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="text-xs text-muted-foreground">{row.original.Email ?? '—'}</div>
          </Link>
        ),
        meta: { label: 'Prospek', kartu: 'judul' },
      },
      {
        id: 'Perusahaan',
        accessorFn: (row) => row.Perusahaan ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Perusahaan" />,
        cell: ({ row }) => row.original.Perusahaan ?? '—',
        meta: { label: 'Perusahaan' },
      },
      {
        id: 'Tahap',
        accessorFn: (row) => row.Tahap ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tahap" />,
        cell: ({ row }) =>
          row.original.Tahap ? <Badge variant="secondary">{row.original.Tahap}</Badge> : '—',
        meta: { label: 'Tahap' },
      },
      {
        id: 'Sumber',
        accessorFn: (row) => row.Sumber,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Sumber" />,
        meta: { label: 'Sumber' },
      },
      {
        id: 'Skor',
        accessorFn: (row) => row.Skor,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Skor" />,
        cell: ({ row }) => <span className="font-medium tabular-nums">{row.original.Skor}</span>,
        meta: { label: 'Skor' },
      },
    ],
    [],
  );

  return (
    <KerangkaPlatform>
      <Head title="Prospek" />

      <KepalaHalaman
        judul="Prospek"
        deskripsi="Seluruh lead dari situs publik, impor, API, dan entri manual."
        tanpaBreadcrumb
        aksi={
          <div className="flex flex-wrap gap-2">
            <Button variant="ghost" asChild>
              <Link href="/admin-platform/pemasaran/prospek/aturan-skor">Aturan Skor</Link>
            </Button>
            <DialogImpor />
            <Button variant="outline" asChild>
              <a href="/admin-platform/pemasaran/prospek/ekspor/csv">
                <Download aria-hidden="true" className="size-4" />
                Ekspor
              </a>
            </Button>
            <DialogProspekBaru />
          </div>
        }
        className="mb-6"
      />

      <div className="mb-4 flex flex-wrap items-center gap-2">
        <Input
          value={cari}
          onChange={(e) => setCari(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') saring(filter.tahap ?? null);
          }}
          placeholder="Cari nama atau email..."
          className="max-w-xs"
          aria-label="Cari prospek"
        />
        <Button
          variant={filter.tahap ? 'ghost' : 'secondary'}
          size="sm"
          onClick={() => saring(null)}
        >
          Semua
        </Button>
        {tahap.map((satu) => (
          <Button
            key={satu.Kode}
            variant={filter.tahap === satu.Kode ? 'secondary' : 'ghost'}
            size="sm"
            onClick={() => saring(satu.Kode)}
          >
            {satu.Nama}
          </Button>
        ))}
      </div>

      <DataTable
        columns={columns}
        data={prospek}
        kartuDiPonsel
        pencarianPlaceholder="Cari prospek..."
        pesanKosong="Belum ada prospek."
      />
    </KerangkaPlatform>
  );
}
