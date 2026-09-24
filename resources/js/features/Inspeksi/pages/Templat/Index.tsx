import { FormEvent, useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
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
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Plus } from 'lucide-react';
import type { BarisTemplatInspeksi } from '@/features/PreventifInspeksi/types';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { ruteInspeksi } from '@/features/Inspeksi/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

interface KategoriAsetRingkas {
  Id: string;
  Nama: string;
}

interface TemplatDaftarPeriksaRingkas {
  Id: string;
  Nama: string;
  Kode: string;
}

interface Props {
  templat: Paginasi<BarisTemplatInspeksi>;
  // Pemilih formulir memuat seluruh kategori dan checklist, bukan hanya baris halaman ini.
  kategoriAset: KategoriAsetRingkas[];
  templatDaftarPeriksa: TemplatDaftarPeriksaRingkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogBuatTemplatInspeksi({
  kategoriAset,
  templatDaftarPeriksa,
  wajib,
}: {
  kategoriAset: KategoriAsetRingkas[];
  templatDaftarPeriksa: TemplatDaftarPeriksaRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Kode: '',
    Nama: '',
    KategoriAsetId: '',
    TemplatDaftarPeriksaId: '',
    IntervalHari: 30,
    Aktif: true,
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteInspeksi.templat, {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="cursor-pointer">
          <Plus className="h-4 w-4" />
          Buat Templat Inspeksi
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={onSubmit}>
            <DialogHeader>
              <DialogTitle>Buat Templat Inspeksi</DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 py-4">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
                label="Kode Templat"
                contoh="Misal: INSP-HVAC-BULANAN"
              />

              <div className="space-y-1.5">
                <Label nama="Nama" htmlFor="Nama">
                  Nama Templat <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="Nama"
                  placeholder="Misal: Inspeksi Visual & Kelistrikan HVAC"
                  value={form.data.Nama}
                  onChange={(e) => form.setData('Nama', e.target.value)}
                  required
                />
                {form.errors.Nama && <p className="text-xs text-destructive">{form.errors.Nama}</p>}
              </div>

              <div className="space-y-1.5">
                <Label nama="KategoriAsetId" htmlFor="KategoriAsetId">
                  Kategori Aset Terkait
                </Label>
                <Combobox
                  nilai={form.data.KategoriAsetId || '__none__'}
                  onPilih={(val) => form.setData('KategoriAsetId', val === '__none__' ? '' : val)}
                  opsi={[
                    { nilai: '__none__', label: '-- Semua Kategori --' },
                    ...opsiDari(kategoriAset, (k) => k.Nama),
                  ]}
                  placeholder="Pilih Kategori..."
                  className="cursor-pointer"
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="TemplatDaftarPeriksaId" htmlFor="TemplatDaftarPeriksaId">
                  Hubungkan Checklist Lapangan
                </Label>
                <Combobox
                  nilai={form.data.TemplatDaftarPeriksaId || '__none__'}
                  onPilih={(val) => form.setData('TemplatDaftarPeriksaId', val === '__none__' ? '' : val)}
                  opsi={[
                    { nilai: '__none__', label: '-- Tanpa Lembar Checklist --' },
                    ...opsiDari(templatDaftarPeriksa, (t) => `${t.Kode} - ${t.Nama}`),
                  ]}
                  placeholder="Pilih Checklist..."
                  className="cursor-pointer"
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="IntervalHari" htmlFor="IntervalHari">
                  Interval Siklus (Hari) <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="IntervalHari"
                  type="number"
                  min={1}
                  value={form.data.IntervalHari}
                  onChange={(e) => form.setData('IntervalHari', Number(e.target.value))}
                  required
                />
              </div>
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                className="cursor-pointer"
                onClick={() => setBuka(false)}
              >
                Batal
              </Button>
              <Button type="submit" className="cursor-pointer" disabled={form.processing}>
                {form.processing ? 'Menyimpan...' : 'Simpan Templat'}
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function InspeksiTemplatIndex({
  templat,
  kategoriAset,
  templatDaftarPeriksa,
  filter,
  wajib,
}: Props) {
  const columns = useMemo<ColumnDef<BarisTemplatInspeksi>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Templat" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </div>
        ),
        meta: { label: 'Templat' },
      },
      {
        id: 'kategoriAset',
        accessorFn: (row) => row.kategoriAset?.Nama ?? '',
        header: 'Kategori Aset',
        cell: ({ row }) => row.original.kategoriAset?.Nama ?? 'Semua Kategori',
        // Nama kategori datang dari relasi; server hanya menyaringnya lewat KategoriAsetId.
        enableSorting: false,
        meta: { label: 'Kategori Aset' },
      },
      {
        id: 'checklist',
        accessorFn: (row) => row.templatDaftarPeriksa?.Nama ?? '',
        header: 'Checklist Lapangan',
        cell: ({ row }) => row.original.templatDaftarPeriksa?.Nama ?? '—',
        // Nama checklist datang dari relasi; penyaringnya lewat TemplatDaftarPeriksaId.
        enableSorting: false,
        meta: { label: 'Checklist Lapangan' },
      },
      {
        id: 'IntervalHari',
        accessorFn: (row) => row.IntervalHari,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Interval" />,
        cell: ({ row }) => `Setiap ${row.original.IntervalHari} hari`,
        meta: { label: 'Interval' },
      },
      {
        id: 'inspeksi',
        accessorFn: (row) => row.inspeksi_count ?? 0,
        header: 'Inspeksi Terbit',
        cell: ({ row }) => row.original.inspeksi_count ?? 0,
        // Hitungan relasi, bukan kolom yang dapat diurutkan server.
        enableSorting: false,
        meta: { label: 'Inspeksi Terbit' },
      },
      {
        id: 'Aktif',
        accessorFn: (row) => (row.Aktif ? '1' : '0'),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Aktif ? 'sukses' : 'netral'}>
            {row.original.Aktif ? 'Aktif' : 'Nonaktif'}
          </Badge>
        ),
        meta: { label: 'Status' },
      },
    ],
    [],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Templat Inspeksi Aset" />

      <KepalaHalaman
        judul="Templat Inspeksi Aset"
        deskripsi="Konfigurasi siklus inspeksi rutin dan lembar periksa per kategori aset."
        aksi={
          <DialogBuatTemplatInspeksi
            kategoriAset={kategoriAset}
            templatDaftarPeriksa={templatDaftarPeriksa}
            wajib={wajib.templat}
          />
        }
        className="mb-5"
      />

      {templat.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/berkas-dokumen.webp"
          judul="Belum Ada Templat Inspeksi"
          deskripsi="Templat inspeksi berkala yang dibuat akan muncul di sini untuk menentukan standar pemeriksaan aset."
        />
      ) : (
        <DataTable
          columns={columns}
          data={templat.data}
          server={{ meta: templat.meta, filter }}
          ekspor="/preventif-inspeksi/templat-inspeksi/ekspor"
          facetedFilters={[
            {
              columnId: 'KategoriAsetId',
              title: 'Kategori Aset',
              options: kategoriAset.map((satu) => ({ label: satu.Nama, value: satu.Id })),
            },
            {
              columnId: 'TemplatDaftarPeriksaId',
              title: 'Checklist',
              options: templatDaftarPeriksa.map((satu) => ({ label: satu.Nama, value: satu.Id })),
            },
            {
              columnId: 'Aktif',
              title: 'Status',
              options: [
                { label: 'Aktif', value: '1' },
                { label: 'Nonaktif', value: '0' },
              ],
            },
          ]}
          pencarianPlaceholder="Cari nama atau kode templat inspeksi..."
          pesanKosong="Tidak ada templat inspeksi yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
