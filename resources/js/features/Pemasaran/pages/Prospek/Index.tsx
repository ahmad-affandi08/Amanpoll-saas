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
import { rutePemasaran } from '@/features/Pemasaran/api';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  prospek: Paginasi<Prospek>;
  tahap: TahapRingkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogProspekBaru({ wajib }: { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nama: '', Email: '', Telepon: '', Perusahaan: '', Jabatan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePemasaran.prospek, {
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
        <AturanWajibProvider aturan={wajib}>
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
              <div key={kunci} className="grid content-start gap-2">
                <Label htmlFor={kunci} nama={kunci}>
                  {label}
                </Label>
                <Input
                  id={kunci}
                  value={form.data[kunci]}
                  onChange={(e) => form.setData(kunci, e.target.value)}
                />
                {form.errors[kunci] ? <p className="text-sm text-destructive">{form.errors[kunci]}</p> : null}
              </div>
            ))}
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

function DialogImpor() {
  const [buka, setBuka] = useState(false);
  const form = useForm<{ Berkas: File | null }>({ Berkas: null });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePemasaran.prospekImpor, {
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
          <div className="grid content-start gap-2">
            <Label nama="Berkas" htmlFor="Berkas">
              Berkas CSV
            </Label>
            <Input
              id="Berkas"
              type="file"
              accept=".csv,text/csv"
              onChange={(e) => form.setData('Berkas', e.target.files?.[0] ?? null)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Kolom yang dibaca: Nama, Email, Telepon, WhatsApp, Jabatan, Perusahaan, Industri, Kota, Negara.
              Baris tanpa nama dilewati.
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

export default function PemasaranProspekIndex({ prospek, tahap, filter, wajib }: Props) {
  // Pencarian dan urutan ikut dibawa; pindah tahap tidak boleh membuang keduanya.
  const saring = (tahapKode: string | null) => {
    router.get(
      rutePemasaran.prospek,
      { ...filter, tahap: tahapKode ?? undefined, page: undefined },
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
          <Link href={rutePemasaran.prospekDetail(row.original.Id)} className="block hover:underline">
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
        // Turunan relasi organisasi prospek, bukan kolom Prospek.
        enableSorting: false,
        meta: { label: 'Perusahaan' },
      },
      {
        id: 'Tahap',
        accessorFn: (row) => row.Tahap ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tahap" />,
        // Disaring lewat tombol tahap di atas tabel; urutannya ada di tabel lain.
        enableSorting: false,
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
              <Link href={rutePemasaran.aturanSkor}>Aturan Skor</Link>
            </Button>
            <DialogImpor />
            <Button variant="outline" asChild>
              <a href={rutePemasaran.prospekEksporCsv}>
                <Download aria-hidden="true" className="size-4" />
                Ekspor
              </a>
            </Button>
            <DialogProspekBaru wajib={wajib.prospek} />
          </div>
        }
        className="mb-6"
      />

      <div className="mb-4 flex flex-wrap items-center gap-2">
        <Button variant={filter.tahap ? 'ghost' : 'secondary'} size="sm" onClick={() => saring(null)}>
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
        data={prospek.data}
        server={{ meta: prospek.meta, filter }}
        kartuDiPonsel
        pencarianPlaceholder="Cari nama atau email..."
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada prospek yang cocok.' : 'Belum ada prospek.'}
      />
    </KerangkaPlatform>
  );
}
