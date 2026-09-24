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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { ruteKodeKegagalan } from '@/features/KodeKegagalan/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { Combobox } from '@/components/ui/combobox';

type JenisKodeKegagalan = 'Masalah' | 'Penyebab' | 'Tindakan';

const JENIS_KODE: JenisKodeKegagalan[] = ['Masalah', 'Penyebab', 'Tindakan'];

const VARIAN_JENIS: Record<JenisKodeKegagalan, 'perhatian' | 'destructive' | 'sukses'> = {
  Masalah: 'perhatian',
  Penyebab: 'destructive',
  Tindakan: 'sukses',
};

interface KategoriAsetRingkas {
  Id: string;
  Nama: string;
}

interface ItemKodeKegagalan {
  Id: string;
  Kode: string;
  Nama: string;
  Jenis: JenisKodeKegagalan;
  KategoriAsetId: string | null;
  NamaKategoriAset: string | null;
  Keterangan: string | null;
  Aktif: boolean;
}

interface Props {
  kodeKegagalan: Paginasi<ItemKodeKegagalan>;
  // Pemilih formulir memuat seluruh kategori, bukan hanya baris halaman ini.
  kategoriAset: KategoriAsetRingkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormKodeKegagalan({
  kategoriAset,
  itemEdit,
  pemicu,
  wajib,
}: {
  kategoriAset: KategoriAsetRingkas[];
  itemEdit?: ItemKodeKegagalan;
  pemicu?: React.ReactNode;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const sedangEdit = Boolean(itemEdit);

  const form = useForm({
    Jenis: itemEdit?.Jenis ?? ('Masalah' as JenisKodeKegagalan),
    Kode: itemEdit?.Kode ?? '',
    Nama: itemEdit?.Nama ?? '',
    KategoriAsetId: itemEdit?.KategoriAsetId ?? TANPA_PILIHAN,
    Keterangan: itemEdit?.Keterangan ?? '',
    Aktif: itemEdit?.Aktif ?? true,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KategoriAsetId: data.KategoriAsetId === TANPA_PILIHAN ? null : data.KategoriAsetId,
    }));

    if (sedangEdit && itemEdit) {
      form.put(ruteKodeKegagalan.detail(itemEdit.Id), {
        preserveScroll: true,
        onSuccess: () => setBuka(false),
      });
    } else {
      form.post(ruteKodeKegagalan.index, {
        preserveScroll: true,
        onSuccess: () => {
          setBuka(false);
          form.reset();
        },
      });
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        {pemicu ? pemicu : <Button className="cursor-pointer">Tambah Kode Kegagalan</Button>}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{sedangEdit ? 'Edit Kode Kegagalan' : 'Tambah Kode Kegagalan'}</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Jenis">Jenis Taksonomi</Label>
              <Select
                value={form.data.Jenis}
                onValueChange={(val) => form.setData('Jenis', val as JenisKodeKegagalan)}
              >
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Masalah" className="cursor-pointer">
                    Masalah (Problem / Symptom)
                  </SelectItem>
                  <SelectItem value="Penyebab" className="cursor-pointer">
                    Penyebab (Cause / Mechanism)
                  </SelectItem>
                  <SelectItem value="Tindakan" className="cursor-pointer">
                    Tindakan (Remedy / Action)
                  </SelectItem>
                </SelectContent>
              </Select>
              {form.errors.Jenis && <p className="text-sm text-destructive">{form.errors.Jenis}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
                contoh="Misal: MSL-001"
              />

              <div className="space-y-1.5">
                <Label nama="KategoriAsetId">Kategori Aset (Opsional)</Label>
                <Combobox
                  nilai={form.data.KategoriAsetId}
                  onPilih={(val) => form.setData('KategoriAsetId', val)}
                  opsi={[opsiKosong('Semua Kategori Aset'), ...opsiDari(kategoriAset, (k) => k.Nama)]}
                  placeholder="Semua kategori"
                  className="cursor-pointer"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label nama="Nama">Nama / Deskripsi Ringkas</Label>
              <Input
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                placeholder="Contoh: Kebocoran Oli Seal, Overheat, Kalibrasi Sensor..."
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>

            <div className="space-y-1.5">
              <Label nama="Keterangan">Keterangan Tambahan</Label>
              <Textarea
                rows={3}
                value={form.data.Keterangan}
                onChange={(e) => form.setData('Keterangan', e.target.value)}
                placeholder="Penjelasan konteks atau panduan diagnosa..."
              />
              {form.errors.Keterangan && <p className="text-sm text-destructive">{form.errors.Keterangan}</p>}
            </div>

            <div className="flex items-center gap-2 pt-1">
              <input
                type="checkbox"
                id="kode-aktif"
                checked={form.data.Aktif}
                onChange={(e) => form.setData('Aktif', e.target.checked)}
                className="cursor-pointer rounded border-input text-teknisi-700 focus:ring-teknisi-600"
              />
              <label htmlFor="kode-aktif" className="text-sm font-medium cursor-pointer">
                Aktif dan dapat dipilih pada perintah kerja
              </label>
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing} className="cursor-pointer">
                {sedangEdit ? 'Perbarui Kode' : 'Simpan Kode'}
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function KodeKegagalanIndex({ kodeKegagalan, kategoriAset, filter, wajib }: Props) {
  const columns = useMemo<ColumnDef<ItemKodeKegagalan>[]>(
    () => [
      {
        id: 'Kode',
        accessorFn: (row) => row.Kode,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => (
          <span className="font-mono text-xs font-semibold text-foreground">{row.original.Kode}</span>
        ),
        meta: { label: 'Kode' },
      },
      {
        id: 'Nama',
        accessorFn: (row) => row.Nama,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => <span className="font-medium text-foreground">{row.original.Nama}</span>,
        meta: { label: 'Nama' },
      },
      {
        id: 'Jenis',
        accessorFn: (row) => row.Jenis,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
        cell: ({ row }) => <Badge variant={VARIAN_JENIS[row.original.Jenis]}>{row.original.Jenis}</Badge>,
        meta: { label: 'Jenis' },
      },
      {
        id: 'kategoriAset',
        accessorFn: (row) => row.NamaKategoriAset ?? '',
        header: 'Kategori Aset',
        cell: ({ row }) => (
          <span className="text-xs text-muted-foreground">
            {row.original.NamaKategoriAset ?? 'Semua Kategori'}
          </span>
        ),
        // Nama kategori datang dari relasi; server hanya menyaringnya lewat KategoriAsetId.
        enableSorting: false,
        meta: { label: 'Kategori Aset' },
      },
      {
        id: 'Keterangan',
        accessorFn: (row) => row.Keterangan ?? '',
        header: 'Keterangan',
        cell: ({ row }) => (
          <span className="block max-w-xs truncate text-xs text-muted-foreground">
            {row.original.Keterangan ?? '—'}
          </span>
        ),
        enableSorting: false,
        meta: { label: 'Keterangan' },
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
            <DialogFormKodeKegagalan
              kategoriAset={kategoriAset}
              itemEdit={row.original}
              pemicu={
                <Button size="sm" variant="outline" className="h-7 cursor-pointer text-xs">
                  Edit
                </Button>
              }
              wajib={wajib.kodeKegagalan}
            />
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi' },
      },
    ],
    [kategoriAset, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kode Kegagalan" />

      <KepalaHalaman
        judul="Kode Kegagalan"
        deskripsi="Katalog taksonomi Problem-Cause-Remedy untuk standarisasi analisis kegagalan aset."
        aksi={<DialogFormKodeKegagalan kategoriAset={kategoriAset} wajib={wajib.kodeKegagalan} />}
        className="mb-5"
      />

      {kodeKegagalan.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          judul="Belum ada kode kegagalan"
          deskripsi="Tambahkan master data kode masalah, penyebab, atau tindakan untuk memudahkan teknisi."
        />
      ) : (
        <DataTable
          columns={columns}
          data={kodeKegagalan.data}
          server={{ meta: kodeKegagalan.meta, filter }}
          ekspor="/pemeliharaan/kode-kegagalan/ekspor"
          facetedFilters={[
            {
              columnId: 'Jenis',
              title: 'Jenis',
              options: JENIS_KODE.map((jenis) => ({ label: jenis, value: jenis })),
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
          pencarianPlaceholder="Cari kode, nama, atau keterangan..."
          pesanKosong="Tidak ada kode kegagalan yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
