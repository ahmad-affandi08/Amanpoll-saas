import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { Badge } from '@/components/ui/badge';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { KonfigurasiTrial, PilihanTrial, Trial } from '@/features/Pemasaran/types';

interface Props {
  trial: Trial[];
  konfigurasi: KonfigurasiTrial;
  pilihan: PilihanTrial;
}

const AKAR = '/admin-platform/pemasaran/trial';

const RAGAM_STATUS: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
  Konversi: 'default',
  Teraktivasi: 'default',
  Aktif: 'secondary',
  Diperpanjang: 'secondary',
  Setup: 'outline',
  Terdaftar: 'outline',
  Kadaluarsa: 'destructive',
  Dibatalkan: 'destructive',
};

export default function PemasaranTrialIndex({ trial, konfigurasi, pilihan }: Props) {
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
                href={`/admin-platform/pemasaran/prospek/${row.original.ProspekId}`}
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
          <Badge variant={RAGAM_STATUS[row.original.Status] ?? 'outline'}>{row.original.Status}</Badge>
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
            <DialogPerpanjang trial={row.original} konfigurasi={konfigurasi} />
            <DialogStatus trial={row.original} />
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
        data={trial}
        kartuDiPonsel
        pencarianPlaceholder="Cari organisasi atau prospek..."
        pesanKosong="Belum ada trial."
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
            variant={selesai ? 'default' : 'outline'}
            title={`${butir.Label}${wajib ? ' (wajib untuk aktivasi)' : ''}`}
            className={selesai ? undefined : 'text-muted-foreground'}
          >
            {butir.Label}
          </Badge>
        );
      })}
    </div>
  );
}

function DialogPerpanjang({ trial, konfigurasi }: { trial: Trial; konfigurasi: KonfigurasiTrial }) {
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

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Hari">Tambahan hari</Label>
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

          <div className="grid gap-2">
            <Label htmlFor="Alasan">Alasan</Label>
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
      </DialogContent>
    </Dialog>
  );
}

function DialogStatus({ trial }: { trial: Trial }) {
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

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Status">Status</Label>
            <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v)}>
              <SelectTrigger id="Status">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.Status ? <p className="text-sm text-destructive">{form.errors.Status}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="AlasanStatus">Alasan</Label>
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
      </DialogContent>
    </Dialog>
  );
}
