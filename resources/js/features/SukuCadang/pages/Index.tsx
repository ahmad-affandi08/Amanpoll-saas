import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { StatusSukuCadang, SukuCadang } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_SUKU_CADANG } from '@/features/Persediaan/status';
import { ruteSukuCadang } from '@/features/SukuCadang/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface KategoriRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  sukuCadang: Paginasi<SukuCadang>;
  kategoriSukuCadang: KategoriRingkas[];
  filter: FilterDaftar;
  jumlahDibawahMinimum: number;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormSukuCadang({
  kategoriSukuCadang,
  wajib,
}: {
  kategoriSukuCadang: KategoriRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: '',
    Nama: '',
    KategoriSukuCadangId: TANPA_PILIHAN,
    NomorBagian: '',
    KodeBatang: '',
    SatuanDasar: '',
    StokMinimum: '0',
    StokMaksimum: '',
    TitikPesanUlang: '',
    HargaRataRata: '0',
    MemakaiBatch: false,
    MemakaiKadaluarsa: false,
    Status: 'Aktif' as StatusSukuCadang,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      KategoriSukuCadangId:
        form.data.KategoriSukuCadangId === TANPA_PILIHAN ? null : form.data.KategoriSukuCadangId,
    };
    router.post(ruteSukuCadang.index, payload, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Suku Cadang</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Suku Cadang</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-2">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="space-y-2">
              <Label nama="KategoriSukuCadangId">Kategori</Label>
              <Select
                value={form.data.KategoriSukuCadangId}
                onValueChange={(v) => form.setData('KategoriSukuCadangId', v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_PILIHAN}>Tidak diisi</SelectItem>
                  {kategoriSukuCadang.map((k) => (
                    <SelectItem key={k.Id} value={k.Id}>
                      {k.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="NomorBagian">Nomor Bagian</Label>
                <Input
                  value={form.data.NomorBagian}
                  onChange={(e) => form.setData('NomorBagian', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label nama="KodeBatang">Kode Batang/SKU</Label>
                <Input
                  value={form.data.KodeBatang}
                  onChange={(e) => form.setData('KodeBatang', e.target.value)}
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="SatuanDasar">Satuan Dasar</Label>
                <Input
                  value={form.data.SatuanDasar}
                  onChange={(e) => form.setData('SatuanDasar', e.target.value)}
                  placeholder="mis. Pcs, Liter"
                />
                {form.errors.SatuanDasar && (
                  <p className="text-sm text-destructive">{form.errors.SatuanDasar}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label nama="HargaRataRata">Harga Rata-rata</Label>
                <Input
                  type="number"
                  min={0}
                  value={form.data.HargaRataRata}
                  onChange={(e) => form.setData('HargaRataRata', e.target.value)}
                />
              </div>
            </div>
            <div className="grid grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label nama="StokMinimum">Stok Minimum</Label>
                <Input
                  type="number"
                  min={0}
                  value={form.data.StokMinimum}
                  onChange={(e) => form.setData('StokMinimum', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label nama="StokMaksimum">Stok Maksimum</Label>
                <Input
                  type="number"
                  min={0}
                  value={form.data.StokMaksimum}
                  onChange={(e) => form.setData('StokMaksimum', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label nama="TitikPesanUlang">Titik Pesan Ulang</Label>
                <Input
                  type="number"
                  min={0}
                  value={form.data.TitikPesanUlang}
                  onChange={(e) => form.setData('TitikPesanUlang', e.target.value)}
                />
              </div>
            </div>
            <div className="flex gap-6">
              <label className="flex items-center gap-2 text-sm">
                <Checkbox
                  checked={form.data.MemakaiBatch}
                  onCheckedChange={(v) => form.setData('MemakaiBatch', v === true)}
                />
                Memakai Batch
              </label>
              <label className="flex items-center gap-2 text-sm">
                <Checkbox
                  checked={form.data.MemakaiKadaluarsa}
                  onCheckedChange={(v) => form.setData('MemakaiKadaluarsa', v === true)}
                />
                Memakai Kadaluarsa
              </label>
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

export default function SukuCadangIndex({
  sukuCadang,
  kategoriSukuCadang,
  filter,
  jumlahDibawahMinimum,
  wajib,
}: Props) {
  const columns = useMemo<ColumnDef<SukuCadang>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <Link href={ruteSukuCadang.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </Link>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'NamaKategori',
        accessorFn: (row) => row.NamaKategori ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => row.original.NamaKategori ?? '—',
        // Turunan relasi, bukan kolom tabel: server tidak dapat mengurutkannya.
        enableSorting: false,
        meta: { label: 'Kategori' },
      },
      {
        id: 'StokTersedia',
        accessorFn: (row) => row.JumlahTersediaBersih ?? 0,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Stok Tersedia" />,
        cell: ({ row }) => {
          const bersih = row.original.JumlahTersediaBersih ?? 0;
          const minimum = parseFloat(row.original.StokMinimum);
          const dibawahMinimum = bersih <= minimum;
          return (
            <div className="flex items-center gap-2">
              <span className={dibawahMinimum ? 'font-semibold text-destructive' : 'text-foreground'}>
                {bersih}
              </span>
              <span className="text-xs text-muted-foreground">{row.original.SatuanDasar}</span>
              {dibawahMinimum && <Badge variant="bahaya">Di bawah minimum</Badge>}
            </div>
          );
        },
        // Saldo dijumlahkan per halaman, jadi pengurutannya tidak akan konsisten lintas halaman.
        enableSorting: false,
        meta: { label: 'Stok Tersedia' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={VARIAN_BADGE_STATUS_SUKU_CADANG[row.original.Status]}>{row.original.Status}</Badge>
        ),
        meta: { label: 'Status' },
      },
    ],
    [wajib],
  );

  // Keadaan kosong menyembunyikan kotak cari, jadi ia hanya boleh muncul saat memang belum ada isinya.
  const belumAdaIsi = sukuCadang.meta.total === 0 && !adaPenyaringAktif(filter);

  return (
    <KerangkaAplikasi>
      <Head title="Suku Cadang" />
      <KepalaHalaman
        judul="Suku Cadang"
        deskripsi="Master data suku cadang beserta saldo stok bersih lintas gudang."
        aksi={
          <>
            <DialogFormSukuCadang kategoriSukuCadang={kategoriSukuCadang} wajib={wajib.sukuCadang} />
          </>
        }
        className="mb-6"
      />

      {jumlahDibawahMinimum > 0 && (
        <div className="mb-4 rounded-[9px] border border-bahaya-600/25 bg-bahaya-600/10 px-4 py-3 text-sm text-bahaya-600">
          {jumlahDibawahMinimum} suku cadang berada di bawah atau sama dengan stok minimum.
        </div>
      )}

      {belumAdaIsi ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada suku cadang."
          deskripsi="Tambahkan suku cadang pertama untuk mulai mencatat stok."
        />
      ) : (
        <DataTable
          columns={columns}
          data={sukuCadang.data}
          server={{ meta: sukuCadang.meta, filter }}
          facetedFilters={[
            {
              columnId: 'KategoriSukuCadangId',
              title: 'Kategori',
              options: kategoriSukuCadang.map((k) => ({ label: k.Nama, value: k.Id })),
            },
          ]}
          pencarianPlaceholder="Cari nama atau kode suku cadang..."
          pesanKosong="Tidak ada suku cadang yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
