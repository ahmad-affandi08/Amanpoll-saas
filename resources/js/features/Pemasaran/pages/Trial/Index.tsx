import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Check } from 'lucide-react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { Badge } from '@/components/ui/badge';
import { varianStatusTrial } from '@/features/Pemasaran/status';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { KonfigurasiTrial, PilihanTrial, Trial } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface Props {
  trial: Paginasi<Trial>;
  filter: FilterDaftar;
  konfigurasi: KonfigurasiTrial;
  pilihan: PilihanTrial;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const AKAR = rutePemasaran.trial;

export default function PemasaranTrialIndex({ trial, konfigurasi, pilihan, filter, wajib }: Props) {
  const columns = useMemo<ColumnDef<Trial>[]>(
    () => [
      {
        id: 'Organisasi',
        accessorFn: (row) => `${row.Organisasi ?? ''} ${row.Prospek ?? ''}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Organisasi" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Organisasi ?? '—'}</div>
            {row.original.ProspekId ? (
              <Link
                href={rutePemasaran.prospekDetail(row.original.ProspekId)}
                className="text-xs text-muted-foreground hover:underline"
              >
                {row.original.Prospek}
              </Link>
            ) : (
              <span className="text-xs text-muted-foreground">Tanpa prospek</span>
            )}
          </div>
        ),
        meta: { label: 'Organisasi', kartu: 'judul' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={varianStatusTrial(row.original.Status)}>{row.original.Status}</Badge>
        ),
        meta: { label: 'Status' },
      },
      {
        id: 'Aktivasi',
        header: 'Aktivasi',
        cell: ({ row }) => <Checklist trial={row.original} pilihan={pilihan} />,
        enableSorting: false,
        meta: { label: 'Aktivasi' },
      },
      {
        id: 'BerakhirPada',
        accessorFn: (row) => row.BerakhirPada,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Berakhir" />,
        cell: ({ row }) => (
          <div className="font-mono text-xs">
            {new Date(row.original.BerakhirPada).toLocaleDateString('id-ID')}
            {row.original.HariPerpanjangan > 0 ? (
              <span className="text-muted-foreground"> (+{row.original.HariPerpanjangan}h)</span>
            ) : null}
          </div>
        ),
        meta: { label: 'Berakhir' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogPerpanjang trial={row.original} konfigurasi={konfigurasi} wajib={wajib.perpanjang} />
            <DialogStatus trial={row.original} wajib={wajib.status} />
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    [pilihan, konfigurasi],
  );

  return (
    <KerangkaPlatform>
      <Head title="Trial" />

      <KepalaHalaman
        judul="Trial"
        deskripsi="Perjalanan tiap workspace percobaan, dari pendaftaran sampai konversi."
        tanpaBreadcrumb
        className="mb-6"
      />

      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="text-base">Setelan berlaku</CardTitle>
        </CardHeader>
        <CardContent>
          <dl className="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <Setelan label="Durasi" nilai={`${konfigurasi.DurasiHari} hari`} />
            <Setelan label="Paket" nilai={konfigurasi.NamaPaket ?? '—'} />
            <Setelan label="Kartu" nilai={konfigurasi.KartuDiperlukan ? 'Diperlukan' : 'Tidak'} />
            <Setelan label="Batas pengguna" nilai={batas(konfigurasi.BatasPengguna)} />
            <Setelan label="Batas lokasi" nilai={batas(konfigurasi.BatasLokasi)} />
            <Setelan label="Batas aset" nilai={batas(konfigurasi.BatasAset)} />
          </dl>
          <p className="mt-4 text-sm text-muted-foreground">
            Durasi, paket, batas, dan masa tenggang dibaca dari domain Langganan — ubah di sana, bukan di
            sini.
          </p>
        </CardContent>
      </Card>

      <DataTable
        columns={columns}
        data={trial.data}
        server={{ meta: trial.meta, filter }}
        facetedFilters={[
          {
            columnId: 'Status',
            title: 'Status',
            options: pilihan.Status.map((satu) => ({ label: satu, value: satu })),
          },
        ]}
        kartuDiPonsel
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada trial yang cocok.' : 'Belum ada trial.'}
      />
    </KerangkaPlatform>
  );
}

function batas(nilai: number | null): string {
  return nilai === null ? 'Tanpa batas' : String(nilai);
}

function Setelan({ label, nilai }: { label: string; nilai: string }) {
  return (
    <div>
      <dt className="text-sm text-muted-foreground">{label}</dt>
      <dd className="font-medium">{nilai}</dd>
    </div>
  );
}

function Checklist({ trial, pilihan }: { trial: Trial; pilihan: PilihanTrial }) {
  return (
    <div className="flex flex-wrap gap-1">
      {pilihan.Butir.map((butir) => {
        const selesai = trial.ButirSelesai.includes(butir.Kode);
        const wajib = pilihan.ButirWajib.includes(butir.Kode);

        return (
          <Badge
            key={butir.Kode}
            variant={selesai ? 'sukses' : 'outline'}
            title={`${butir.Label}${selesai ? ' (selesai)' : ' (belum)'}${wajib ? ' (wajib untuk aktivasi)' : ''}`}
            className={selesai ? undefined : 'border-dashed text-muted-foreground'}
          >
            {selesai ? <Check aria-hidden="true" /> : null}
            {butir.Label}
            <span className="sr-only">{selesai ? ', selesai' : ', belum'}</span>
          </Badge>
        );
      })}
    </div>
  );
}

function DialogPerpanjang({
  trial,
  konfigurasi,
  wajib,
}: {
  trial: Trial;
  konfigurasi: KonfigurasiTrial;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const sisa = konfigurasi.PerpanjanganMaksHari - trial.HariPerpanjangan;
  const form = useForm({ Hari: '7', Alasan: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({ ...data, Hari: Number(data.Hari) }));
    form.post(`${AKAR}/${trial.Id}/perpanjang`, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm" disabled={sisa < 1}>
          Perpanjang
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Perpanjang Trial</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={kirim} className="grid gap-4">
            <div className="grid content-start gap-2">
              <Label nama="Hari" htmlFor="Hari">
                Tambahan hari
              </Label>
              <Input
                id="Hari"
                type="number"
                min={1}
                max={sisa}
                value={form.data.Hari}
                onChange={(e) => form.setData('Hari', e.target.value)}
              />
              <p className="text-sm text-muted-foreground">Sisa jatah perpanjangan: {sisa} hari.</p>
              {form.errors.Hari ? <p className="text-sm text-destructive">{form.errors.Hari}</p> : null}
            </div>

            <div className="grid content-start gap-2">
              <Label nama="Alasan" htmlFor="Alasan">
                Alasan
              </Label>
              <Input
                id="Alasan"
                value={form.data.Alasan}
                onChange={(e) => form.setData('Alasan', e.target.value)}
              />
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

function DialogStatus({ trial, wajib }: { trial: Trial; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const pilihan = trial.StatusBerikutnya.filter((satu) => satu !== 'Diperpanjang');
  const form = useForm({ Status: pilihan[0] ?? '', Alasan: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(`${AKAR}/${trial.Id}/status`, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm" disabled={pilihan.length === 0}>
          Status
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Ubah Status Trial</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={kirim} className="grid gap-4">
            <div className="grid content-start gap-2">
              <Label nama="Status" htmlFor="Status">
                Status
              </Label>
              <Combobox
                nilai={form.data.Status}
                onPilih={(v) => form.setData('Status', v)}
                opsi={pilihan.map((satu) => ({ nilai: satu, label: satu }))}
              />
              {form.errors.Status ? <p className="text-sm text-destructive">{form.errors.Status}</p> : null}
            </div>

            <div className="grid content-start gap-2">
              <Label nama="AlasanStatus" htmlFor="AlasanStatus">
                Alasan
              </Label>
              <Input
                id="AlasanStatus"
                value={form.data.Alasan}
                onChange={(e) => form.setData('Alasan', e.target.value)}
              />
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
