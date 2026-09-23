import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Plus, ArrowRight } from 'lucide-react';
import type { TemplatDaftarPeriksa } from '@/features/PreventifInspeksi/types';
import { ruteDaftarPeriksa } from '@/features/DaftarPeriksa/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

const JENIS_TEMPLAT = ['Pemeliharaan', 'Inspeksi', 'Kalibrasi', 'Umum'] as const;

interface KategoriAsetRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  templat: Paginasi<TemplatDaftarPeriksa>;
  // Pemilih formulir memuat seluruh kategori dan model, bukan hanya baris halaman ini.
  kategoriAset: KategoriAsetRingkas[];
  modelAset: { Id: string; Nama: string; KategoriAsetId?: string }[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogBuatTemplat({
  kategoriAset,
  wajib,
}: {
  kategoriAset: KategoriAsetRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Kode: '',
    Nama: '',
    Jenis: 'Pemeliharaan',
    KategoriAsetId: '',
    ModelAsetId: '',
    Aktif: true,
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteDaftarPeriksa.index, {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="cursor-pointer gap-2 bg-teknisi-600 text-white hover:bg-teknisi-700">
          <Plus className="h-4 w-4" />
          Buat Templat Baru
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={onSubmit}>
            <DialogHeader>
              <DialogTitle>Buat Templat Daftar Periksa</DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 py-4">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
                label="Kode Templat"
                contoh="Misal: CK-POMPA-01"
              />

              <div className="space-y-1.5">
                <Label nama="Nama" htmlFor="Nama">
                  Nama Templat <span className="text-rose-500">*</span>
                </Label>
                <Input
                  id="Nama"
                  placeholder="Misal: Checklist Servis Rutin Pompa Sentrifugal"
                  value={form.data.Nama}
                  onChange={(e) => form.setData('Nama', e.target.value)}
                  required
                />
                {form.errors.Nama && <p className="text-xs text-rose-500">{form.errors.Nama}</p>}
              </div>

              <div className="space-y-1.5">
                <Label nama="Jenis" htmlFor="Jenis">
                  Jenis Operasi
                </Label>
                <Select value={form.data.Jenis} onValueChange={(val) => form.setData('Jenis', val)}>
                  <SelectTrigger id="Jenis" className="cursor-pointer">
                    <SelectValue placeholder="Pilih Jenis" />
                  </SelectTrigger>
                  <SelectContent>
                    {JENIS_TEMPLAT.map((jenis) => (
                      <SelectItem key={jenis} value={jenis}>
                        {jenis}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-1.5">
                <Label nama="KategoriAsetId" htmlFor="KategoriAsetId">
                  Kategori Aset Terkait (Opsional)
                </Label>
                <Combobox
                  nilai={form.data.KategoriAsetId || '__none__'}
                  onPilih={(val) => form.setData('KategoriAsetId', val === '__none__' ? '' : val)}
                  opsi={[
                    { nilai: '__none__', label: '-- Umum (Semua Kategori) --' },
                    ...opsiDari(kategoriAset, (k) => k.Nama),
                  ]}
                  placeholder="Semua Kategori"
                  className="cursor-pointer"
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
              <Button
                type="submit"
                className="cursor-pointer bg-teknisi-600 text-white hover:bg-teknisi-700"
                disabled={form.processing}
              >
                {form.processing ? 'Menyimpan...' : 'Simpan & Lanjutkan'}
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function DaftarPeriksaTemplatIndex({ templat, kategoriAset, filter, wajib }: Props) {
  const columns = useMemo<ColumnDef<TemplatDaftarPeriksa>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Templat" />,
        cell: ({ row }) => (
          <Link href={ruteDaftarPeriksa.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </Link>
        ),
        meta: { label: 'Templat' },
      },
      {
        id: 'Jenis',
        accessorFn: (row) => row.Jenis,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
        cell: ({ row }) => row.original.Jenis,
        meta: { label: 'Jenis' },
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
        id: 'VersiTemplat',
        accessorFn: (row) => row.VersiTemplat,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Versi" />,
        cell: ({ row }) => `v${row.original.VersiTemplat}`,
        meta: { label: 'Versi' },
      },
      {
        id: 'butir',
        accessorFn: (row) => row.butir_count ?? 0,
        header: 'Pertanyaan',
        cell: ({ row }) => `${row.original.butir_count ?? 0} butir`,
        // Hitungan relasi, bukan kolom yang dapat diurutkan server.
        enableSorting: false,
        meta: { label: 'Pertanyaan' },
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
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end">
            <Link
              href={ruteDaftarPeriksa.detail(row.original.Id)}
              className="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-teknisi-600 hover:text-teknisi-700"
            >
              <span>Buka Builder</span>
              <ArrowRight className="h-3.5 w-3.5" />
            </Link>
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
    <KerangkaAplikasi>
      <Head title="Templat Daftar Periksa (Checklist)" />

      <KepalaHalaman
        judul="Templat Daftar Periksa"
        deskripsi="Kelola lembar periksa terstandarisasi untuk inspeksi dan pemeliharaan preventif."
        aksi={<DialogBuatTemplat kategoriAset={kategoriAset} wajib={wajib.templat} />}
        className="mb-6"
      />

      {templat.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/berkas-dokumen.webp"
          judul="Belum Ada Templat Daftar Periksa"
          deskripsi="Katalog templat checklist yang dibuat akan muncul di sini untuk digunakan pada pemeliharaan dan inspeksi."
        />
      ) : (
        <DataTable
          columns={columns}
          data={templat.data}
          server={{ meta: templat.meta, filter }}
          ekspor="/preventif-inspeksi/templat-daftar-periksa/ekspor"
          facetedFilters={[
            {
              columnId: 'Jenis',
              title: 'Jenis',
              options: JENIS_TEMPLAT.map((jenis) => ({ label: jenis, value: jenis })),
            },
            {
              columnId: 'KategoriAsetId',
              title: 'Kategori Aset',
              options: kategoriAset.map((satu) => ({ label: satu.Nama, value: satu.Id })),
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
          pencarianPlaceholder="Cari nama atau kode templat..."
          pesanKosong="Tidak ada templat daftar periksa yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
